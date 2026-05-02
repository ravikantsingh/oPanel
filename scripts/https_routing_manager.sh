#!/bin/bash
# /opt/panel/scripts/https_routing_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
FORCE_HTTPS=$(echo "$PAYLOAD" | jq -r '.force_https')
ENABLE_HSTS=$(echo "$PAYLOAD" | jq -r '.enable_hsts')
HSTS_MAX_AGE=$(echo "$PAYLOAD" | jq -r '.hsts_max_age')
HSTS_SUBDOMAINS=$(echo "$PAYLOAD" | jq -r '.hsts_subdomains')
HSTS_PRELOAD=$(echo "$PAYLOAD" | jq -r '.hsts_preload')

VHOST_CONF="/etc/nginx/sites-available/$DOMAIN.conf"

if [ ! -f "$VHOST_CONF" ]; then
    echo "Error: Nginx configuration for $DOMAIN not found."
    exit 1
fi

# 1. Create a safe backup before doing any string manipulation
cp "$VHOST_CONF" "${VHOST_CONF}.bak"

# ==========================================
# 2. FORCE HTTPS LOGIC (Port 80 -> 443)
# ==========================================
# Scrub oPanel's custom redirect if it exists
sed -i '/# oPanel Force HTTPS/d' "$VHOST_CONF"
# Scrub Certbot's default redirect (if it was added by ssl_manager.sh)
sed -i '/return 301 https:\/\/\$host\$request_uri;/d' "$VHOST_CONF"

if [ "$FORCE_HTTPS" == "1" ]; then
    # Safely inject the redirect immediately after the 'listen 80;' directive
    sed -i '/listen 80;/a \    return 301 https://$host$request_uri; # oPanel Force HTTPS' "$VHOST_CONF"
fi

# ==========================================
# 3. HSTS LOGIC (Strict Transport Security)
# ==========================================
# Scrub any existing HSTS headers to prevent Nginx duplicate header crashes
sed -i '/Strict-Transport-Security/d' "$VHOST_CONF"

if [ "$ENABLE_HSTS" == "1" ]; then
    HEADER_STRING="max-age=$HSTS_MAX_AGE"
    if [ "$HSTS_SUBDOMAINS" == "1" ]; then HEADER_STRING="$HEADER_STRING; includeSubDomains"; fi
    if [ "$HSTS_PRELOAD" == "1" ]; then HEADER_STRING="$HEADER_STRING; preload"; fi

    # Inject the new header safely inside the SSL server block.
    # We use "ssl_certificate " as the anchor because it guarantees we are in the 443 block.
    sed -i "/ssl_certificate /a \    add_header Strict-Transport-Security \"$HEADER_STRING\" always;" "$VHOST_CONF"
fi

# ==========================================
# 4. SRE SAFETY CHECK & ROLLBACK
# ==========================================
if nginx -t > /dev/null 2>&1; then
    systemctl reload nginx
    rm -f "${VHOST_CONF}.bak"
    
    # ---> THE FIX: Source of Truth is now actively updated <---
    mysql -e "UPDATE panel_core.domains SET force_https = $FORCE_HTTPS, hsts_enabled = $ENABLE_HSTS WHERE domain_name = '$DOMAIN';"
    
    echo "Success: Advanced Routing applied to $DOMAIN seamlessly."
    exit 0
else
    # ROLLBACK PROTOCOL: If sed corrupted the file, restore the backup immediately to keep the server online
    mv "${VHOST_CONF}.bak" "$VHOST_CONF"
    systemctl reload nginx
    echo "Critical Error: Nginx syntax check failed. Changes safely rolled back."
    exit 1
fi