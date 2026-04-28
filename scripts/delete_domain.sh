#!/bin/bash
# /opt/panel/scripts/delete_domain.sh
# Executed by Python Daemon as root

PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')

echo "Initiating Scorched Earth protocol for $DOMAIN..."

# 1. Remove Nginx Configurations
rm -f /etc/nginx/sites-available/$DOMAIN.conf
rm -f /etc/nginx/sites-enabled/$DOMAIN.conf

# 2. Clean up Let's Encrypt SSL (Suppress errors if no cert exists)
certbot delete --cert-name "$DOMAIN" --non-interactive 2>/dev/null

# 3. Destroy the Web Root (Files, Git, TFM)
WEB_ROOT="/home/$USERNAME/web/$DOMAIN"
if [ -d "$WEB_ROOT" ]; then
    rm -rf "$WEB_ROOT"
fi

# 4. Remove the record from the Control Panel Database
mysql -e "DELETE FROM panel_core.domains WHERE domain_name='$DOMAIN';"

# 5. Safely Reload Nginx
if nginx -t > /dev/null 2>&1; then
    systemctl reload nginx
    echo "Success: Domain $DOMAIN and all associated files have been permanently deleted."
    exit 0
else
    echo "Critical Warning: Domain deleted, but Nginx failed to reload. Please check server logs."
    exit 1
fi