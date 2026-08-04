#!/bin/bash
# /opt/panel/scripts/ssl_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
TASK_ID=$2

if [ -z "$PAYLOAD" ]; then
    echo "Error: No JSON payload provided."
    exit 1
fi

ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')

# VHOST check ONLY for website actions (Prevents Mail SSL from crashing)
if [ "$ACTION" == "letsencrypt" ] || [ "$ACTION" == "custom" ]; then
    VHOST="/etc/nginx/sites-available/$DOMAIN.conf"
    if [ ! -f "$VHOST" ]; then
        echo "Error: Nginx configuration for $DOMAIN not found."
        exit 1
    fi
    
    # Fetch the username to know where the public_html directory is located
    USERNAME=$(mysql -N -s -e "SELECT username FROM panel_core.domains WHERE domain_name='$DOMAIN' LIMIT 1;")
    if [ -z "$USERNAME" ]; then
        echo "Error: Could not find owner for $DOMAIN in the database."
        exit 1
    fi
    WEBROOT="/home/$USERNAME/web/$DOMAIN/public_html"
fi
# ==========================================
# 1. LET'S ENCRYPT PROVISIONING (BULLETPROOF METHOD)
# ==========================================
if [ "$ACTION" == "letsencrypt" ]; then
    EMAIL=$(echo "$PAYLOAD" | jq -r '.email // "admin@'$DOMAIN'"')
    echo "Starting Let's Encrypt provisioning for $DOMAIN..."

    # 1. Use the bulletproof webroot method instead of the buggy Nginx plugin
    certbot certonly --webroot -w "$WEBROOT" -d "$DOMAIN" -d "www.$DOMAIN" --non-interactive --agree-tos -m "$EMAIL" --deploy-hook "nginx -s reload"
    CERT_EXIT_CODE=$?

    if [ $CERT_EXIT_CODE -eq 0 ]; then
        # 2. Safely backup the config
        cp "$VHOST" "${VHOST}.bak"
        
        # 3. Inject the SSL directives into Nginx (if they aren't already there)
        if ! grep -q "listen 443 ssl" "$VHOST"; then
            sed -i "s/listen 80;/listen 80;\n    listen 443 ssl http2;\n    ssl_certificate \/etc\/letsencrypt\/live\/$DOMAIN\/fullchain.pem;\n    ssl_certificate_key \/etc\/letsencrypt\/live\/$DOMAIN\/privkey.pem;/g" "$VHOST"
        fi

        # 4. Test and reload
        if nginx -t > /dev/null 2>&1; then
            /opt/panel/scripts/nginx_reload_callback.sh "$TASK_ID" > /dev/null 2>&1 &
            mysql -e "UPDATE panel_core.domains SET has_ssl = 1 WHERE domain_name = '$DOMAIN';"
            rm -f "${VHOST}.bak"
            echo "Success: Let's Encrypt SSL installed and configured."
            exit 0
        else
            mv "${VHOST}.bak" "$VHOST"
            echo "Error: Nginx syntax failed after SSL injection. Rolled back."
            exit 1
        fi
    else
        echo "Error: Certbot failed to authenticate. Ensure DNS points to this server."
        exit 1
    fi

    # ==========================================
# 2. CUSTOM CERTIFICATE INJECTION
# ==========================================
elif [ "$ACTION" == "custom" ]; then
    echo "Processing custom SSL certificate for $DOMAIN..."
    
    CERT_DIR="/etc/nginx/ssl/custom/$DOMAIN"
    mkdir -p "$CERT_DIR"
    chmod 750 "$CERT_DIR"

    echo "$PAYLOAD" | jq -r '.custom_cert' > "$CERT_DIR/fullchain.pem"
    echo "$PAYLOAD" | jq -r '.custom_key' > "$CERT_DIR/privkey.pem"
    chmod 640 "$CERT_DIR/fullchain.pem" "$CERT_DIR/privkey.pem"
    
    cp "$VHOST" "${VHOST}.bak"
    
    if grep -q "listen 443 ssl" "$VHOST"; then
        sed -i "s|ssl_certificate .*|ssl_certificate $CERT_DIR/fullchain.pem;|g" "$VHOST"
        sed -i "s|ssl_certificate_key .*|ssl_certificate_key $CERT_DIR/privkey.pem;|g" "$VHOST"
    else
        sed -i "s/listen 80;/listen 80;\n    listen 443 ssl http2;\n    ssl_certificate $CERT_DIR\/fullchain.pem;\n    ssl_certificate_key $CERT_DIR\/privkey.pem;/g" "$VHOST"
    fi

    if nginx -t > /dev/null 2>&1; then
        /opt/panel/scripts/nginx_reload_callback.sh "$TASK_ID" > /dev/null 2>&1 &
        mysql -e "UPDATE panel_core.domains SET has_ssl = 1 WHERE domain_name = '$DOMAIN';"
        rm "${VHOST}.bak"
        echo "Success: Custom SSL applied and Nginx reloaded."
        exit 0
    else
        echo "Error: Invalid Nginx syntax after applying custom SSL. Rolling back."
        mv "${VHOST}.bak" "$VHOST"
        /opt/panel/scripts/nginx_reload_callback.sh "$TASK_ID" > /dev/null 2>&1 &
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

    # ==========================================
# 4. MAIL SERVER SSL (POSTFIX & DOVECOT)
# ==========================================
elif [ "$ACTION" == "mail_letsencrypt" ]; then
    MAIL_DOMAIN=$(echo "$PAYLOAD" | jq -r '.mail_domain')
    EMAIL=$(echo "$PAYLOAD" | jq -r '.email')
    echo "Starting Let's Encrypt provisioning for Mail Server: $MAIL_DOMAIN..."

    systemctl stop nginx
    certbot certonly --standalone -d "$MAIL_DOMAIN" --non-interactive --agree-tos -m "$EMAIL"
    CERT_EXIT_CODE=$?
    systemctl start nginx

    if [ $CERT_EXIT_CODE -eq 0 ]; then
        echo "Certificate issued. Securing Postfix and Dovecot..."
        postconf -e "smtpd_tls_cert_file=/etc/letsencrypt/live/$MAIL_DOMAIN/fullchain.pem"
        postconf -e "smtpd_tls_key_file=/etc/letsencrypt/live/$MAIL_DOMAIN/privkey.pem"
        sed -i "s|^ssl_cert =.*|ssl_cert = </etc/letsencrypt/live/$MAIL_DOMAIN/fullchain.pem|" /etc/dovecot/conf.d/10-ssl.conf
        sed -i "s|^ssl_key =.*|ssl_key = </etc/letsencrypt/live/$MAIL_DOMAIN/privkey.pem|" /etc/dovecot/conf.d/10-ssl.conf
        systemctl restart postfix dovecot
        echo "Success: Mail SSL applied and services restarted."
        exit 0
    else
        echo "Error: Certbot standalone challenge failed. Verify DNS points to this server."
        exit 1
    fi
    
else
    echo "Error: Unknown sub_action '$ACTION'."
    exit 1
fi