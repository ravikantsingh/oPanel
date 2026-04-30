#!/bin/bash
# /opt/panel/scripts/vhost_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1

if [ -z "$PAYLOAD" ]; then
    echo "Error: No JSON payload provided."
    exit 1
fi

# Extract data
ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')
PHP_VERSION=$(echo "$PAYLOAD" | jq -r '.php_version // "8.3"') # Default to 8.3 if not passed

# Define Paths
NGINX_AVAILABLE="/etc/nginx/sites-available"
NGINX_ENABLED="/etc/nginx/sites-enabled"
WEB_ROOT="/home/$USERNAME/web/$DOMAIN/public_html"
LOG_DIR="/home/$USERNAME/web/$DOMAIN/logs"
VHOST_CONF="$NGINX_AVAILABLE/$DOMAIN.conf"

# ==========================================
# SAFETY: Verify User Exists on the OS
# ==========================================
if ! id "$USERNAME" >/dev/null 2>&1; then
    echo "Error: The Linux user '$USERNAME' does not exist on the OS level. Sync failed."
    exit 1
fi

# ==========================================
# ACTION: CREATE DOMAIN
# ==========================================
if [ "$ACTION" == "create" ]; then

    if [ -f "$VHOST_CONF" ]; then
        echo "Error: Nginx config for $DOMAIN already exists."
        exit 1
    fi

    # 1. Create Web Directories
    mkdir -p "$WEB_ROOT"
    mkdir -p "$LOG_DIR"

    # Create a default index.php file to prove it works
    cat > "$WEB_ROOT/index.php" <<EOF
<?php
echo "<h1>Welcome to $DOMAIN!</h1>";
echo "<p>Hosted securely on your custom oPanel.</p>";
phpinfo();
?>
EOF

    # Fix permissions (User owns their files, www-data can read them)
    chown -R $USERNAME:$USERNAME "/home/$USERNAME/web/$DOMAIN"
    chmod -R 755 "/home/$USERNAME/web/$DOMAIN"

    # 2. Generate Nginx Configuration (HEREDOC)
    cat > "$VHOST_CONF" <<EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;
    root $WEB_ROOT;
    index index.php index.html index.htm;

    access_log $LOG_DIR/access.log;
    error_log $LOG_DIR/error.log;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        # Maps to the standard Ubuntu/Debian PHP-FPM socket structure
        fastcgi_pass unix:/run/php/php$PHP_VERSION-fpm-$USERNAME.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF
    # Auto-Generate the PHP Pool on Creation
    POOL_CONF="/etc/php/$PHP_VERSION/fpm/pool.d/$USERNAME.conf"
    if [ ! -f "$POOL_CONF" ]; then
        cat <<EOF > "$POOL_CONF"
[$USERNAME]
user = $USERNAME
group = $USERNAME
listen = /run/php/php$PHP_VERSION-fpm-$USERNAME.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
EOF
        # Test PHP syntax before restarting <---
        if php-fpm$PHP_VERSION -t > /dev/null 2>&1; then
            systemctl restart php$PHP_VERSION-fpm
        else
            # Rollback the toxic file so the server stays online
            rm -f "$POOL_CONF"
            echo "Error: PHP-FPM pool generation failed syntax check. Rolled back."
            exit 1
        fi
    fi

    # 3. Enable the site and test Nginx
    ln -s "$VHOST_CONF" "$NGINX_ENABLED/"
    
    if nginx -t; then
        systemctl reload nginx
        # ---> NEW: SOURCE OF TRUTH TRACKING <---
        mysql -e "INSERT IGNORE INTO panel_core.domains (domain_name, username, php_version) VALUES ('$DOMAIN', '$USERNAME', '$PHP_VERSION');"

        echo "Success: Domain $DOMAIN created and Nginx reloaded."
        exit 0
    else
        # Rollback if Nginx config is invalid
        rm -f "$VHOST_CONF"
        rm -f "$NGINX_ENABLED/$DOMAIN.conf"
        echo "Error: Invalid Nginx configuration generated. Rolled back."
        exit 1
    fi
# ==========================================
# ACTION: UPDATE PHP VERSION
# ==========================================
elif [ "$ACTION" == "update_php" ]; then
    
    if [ ! -f "$VHOST_CONF" ]; then
        echo "Error: Nginx config for $DOMAIN does not exist."
        exit 1
    fi

    # 1. Use 'sed' to search for the old fastcgi_pass line and replace it with the new version
    sed -i -E "s/fastcgi_pass unix:\/run\/php\/php[0-9\.]+-fpm.*\.sock;/fastcgi_pass unix:\/run\/php\/php$PHP_VERSION-fpm-$USERNAME.sock;/" "$VHOST_CONF"

    # 2. Test and reload Nginx FIRST
    if nginx -t; then
        systemctl reload nginx
        
        # 3. ---> MOVED: SOURCE OF TRUTH TRACKING <---
        # Only update the UI database if the server successfully applied the change
        mysql -e "UPDATE panel_core.domains SET php_version = '$PHP_VERSION' WHERE domain_name = '$DOMAIN';"

        echo "Success: $DOMAIN is now running PHP $PHP_VERSION."
        exit 0
    else
        echo "Error: Nginx failed to reload after PHP version change."
        exit 1
    fi
# ==========================================
# ACTION: TOGGLE WAF (MODSECURITY)
# ==========================================
elif [ "$ACTION" == "update_waf" ]; then
    
    STATUS=$(echo "$PAYLOAD" | jq -r '.status')
    WAF_CONF="/etc/nginx/waf/$DOMAIN.conf"
    
    if [ ! -f "$VHOST_CONF" ]; then
        echo "Error: Nginx config for $DOMAIN does not exist."
        exit 1
    fi

    # ---> FIX: Ensure the master WAF directory exists <---
    mkdir -p /etc/nginx/waf/

    # Ensure the custom rules file exists so Nginx doesn't crash on Include
    touch "$WAF_CONF"

    if [ "$STATUS" == "on" ]; then
        if ! grep -q "modsecurity on;" "$VHOST_CONF"; then
            sed -i "/server_name/a \    modsecurity on;\n    modsecurity_rules_file \/etc\/modsecurity\/modsecurity.conf;\n    modsecurity_rules_file $WAF_CONF;" "$VHOST_CONF"
        fi
        DB_VAL=1
    else
        sed -i '/modsecurity on;/d' "$VHOST_CONF"
        sed -i '/modsecurity_rules_file/d' "$VHOST_CONF"
        DB_VAL=0
    fi

    if nginx -t; then
        systemctl reload nginx
        mysql -e "UPDATE panel_core.domains SET waf_enabled = $DB_VAL WHERE domain_name = '$DOMAIN';"
        echo "Success: ModSecurity WAF for $DOMAIN is now $STATUS."
        exit 0
    else
        echo "Error: Nginx failed to reload after WAF toggle."
        exit 1
    fi

# ==========================================
# ACTION: UPDATE CUSTOM WAF RULES
# ==========================================
elif [ "$ACTION" == "update_waf_rules" ]; then

    CUSTOM_RULES=$(echo "$PAYLOAD" | jq -r '.custom_rules')
    WAF_CONF="/etc/nginx/waf/$DOMAIN.conf"
    
    # ---> FIX: Ensure the master WAF directory exists <---
    mkdir -p /etc/nginx/waf/
    
    # Backup existing rules in case the new ones break Nginx
    cp "$WAF_CONF" "$WAF_CONF.bak" 2>/dev/null || touch "$WAF_CONF.bak"

    # Write the new rules
    echo "$CUSTOM_RULES" > "$WAF_CONF"

    # Source of Truth: Validate syntax before saving to database
    if nginx -t; then
        systemctl reload nginx
        # Escape single quotes so it doesn't break our MySQL INSERT query
        SAFE_RULES=$(echo "$CUSTOM_RULES" | sed "s/'/''/g")
        mysql -e "UPDATE panel_core.domains SET waf_custom_rules = '$SAFE_RULES' WHERE domain_name = '$DOMAIN';"
        rm "$WAF_CONF.bak"
        echo "Success: Custom WAF rules applied for $DOMAIN."
        exit 0
    else
        # Syntax was bad! Rollback the file immediately to keep the server online
        mv "$WAF_CONF.bak" "$WAF_CONF"
        echo "Error: Invalid ModSecurity syntax provided. Changes rolled back safely."
        exit 1
    fi
else
    echo "Error: Unknown sub_action '$ACTION'."
    exit 1
fi