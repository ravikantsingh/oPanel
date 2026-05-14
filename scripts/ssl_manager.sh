#!/bin/bash
# /opt/panel/scripts/ssl_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1

if [ -z "$PAYLOAD" ]; then
    echo "Error: No JSON payload provided."
    exit 1
fi

ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
VHOST="/etc/nginx/sites-available/$DOMAIN.conf"

if [ ! -f "$VHOST" ]; then
    echo "Error: Nginx configuration for $DOMAIN not found."
    exit 1
fi

# ==========================================
# 1. LET'S ENCRYPT PROVISIONING
# ==========================================
if [ "$ACTION" == "letsencrypt" ]; then
    EMAIL=$(echo "$PAYLOAD" | jq -r '.email // "admin@'$DOMAIN'"')
    echo "Starting Let's Encrypt provisioning for $DOMAIN..."

    certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "$EMAIL" --redirect

    if [ $? -eq 0 ]; then
        mysql -e "UPDATE panel_core.domains SET has_ssl = 1 WHERE domain_name = '$DOMAIN';"
        echo "Success: Let's Encrypt SSL installed."
        exit 0
    else
        echo "Error: Certbot failed. Ensure DNS points to this server."
        exit 1
    fi

# ==========================================
# 2. CUSTOM CERTIFICATE INJECTION
# ==========================================
elif [ "$ACTION" == "custom" ]; then
    echo "Processing custom SSL certificate for $DOMAIN..."
    
    # 1. Create a secure, isolated directory for this domain's custom certs
    CERT_DIR="/etc/nginx/ssl/custom/$DOMAIN"
    mkdir -p "$CERT_DIR"
    chmod 750 "$CERT_DIR"

    # 2. Extract the cert and key from JSON payload and write to disk
    echo "$PAYLOAD" | jq -r '.custom_cert' > "$CERT_DIR/fullchain.pem"
    echo "$PAYLOAD" | jq -r '.custom_key' > "$CERT_DIR/privkey.pem"
    
    chmod 640 "$CERT_DIR/fullchain.pem" "$CERT_DIR/privkey.pem"
    
    # 3. Swap the Nginx config paths dynamically using sed
    # We look for the ssl_certificate directive and replace the entire line
    cp "$VHOST" "${VHOST}.bak" # Safety backup
    
    sed -i "s|ssl_certificate .*|ssl_certificate $CERT_DIR/fullchain.pem;|g" "$VHOST"
    sed -i "s|ssl_certificate_key .*|ssl_certificate_key $CERT_DIR/privkey.pem;|g" "$VHOST"
    
    # Optional: If the vhost currently listens on 80 only, we'd need to uncomment the 443 blocks.
    # (Assuming your vhost_manager already sets up the 443 blocks commented out, we uncomment them):
    sed -i 's/#listen 443 ssl/listen 443 ssl/g' "$VHOST"
    sed -i 's/#listen \[::\]:443 ssl/listen \[::\]:443 ssl/g' "$VHOST"

    # 4. Safely test and reload Nginx
    if nginx -t > /dev/null 2>&1; then
        systemctl reload nginx
        mysql -e "UPDATE panel_core.domains SET has_ssl = 1 WHERE domain_name = '$DOMAIN';"
        rm "${VHOST}.bak"
        echo "Success: Custom SSL applied and Nginx reloaded."
        exit 0
    else
        echo "Error: Invalid Nginx syntax after applying custom SSL. Rolling back."
        mv "${VHOST}.bak" "$VHOST"
        systemctl reload nginx
        exit 1
    fi
# ==========================================
# 3. TOGGLE AUTO RENEWAL
# ==========================================
elif [ "$ACTION" == "toggle_renewal" ]; then
    STATUS=$(echo "$PAYLOAD" | jq -r '.status')
    CONF="/etc/letsencrypt/renewal/$DOMAIN.conf"
    DISABLED_CONF="/etc/letsencrypt/renewal/$DOMAIN.conf.disabled"

    if [ "$STATUS" == "disable" ]; then
        if [ -f "$CONF" ]; then
            mv "$CONF" "$DISABLED_CONF"
            echo "Success: Auto-renewal disabled for $DOMAIN."
        else
            echo "Notice: Renewal config already disabled or not found."
        fi
        exit 0
    elif [ "$STATUS" == "enable" ]; then
        if [ -f "$DISABLED_CONF" ]; then
            mv "$DISABLED_CONF" "$CONF"
            echo "Success: Auto-renewal enabled for $DOMAIN."
        else
            echo "Notice: Disabled config not found. Already enabled?"
        fi
        exit 0
    fi
    
else
    echo "Error: Unknown sub_action '$ACTION'."
    exit 1
fi