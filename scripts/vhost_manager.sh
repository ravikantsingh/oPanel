#!/bin/bash
# /opt/panel/scripts/vhost_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
TASK_ID=$2

if [ -z "$PAYLOAD" ]; then
    echo "Error: No JSON payload provided."
    exit 1
fi

# Extract data
ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')
PHP_VERSION=$(echo "$PAYLOAD" | jq -r '.php_version // "8.3"') # Default to 8.3 if not passed

# Auto-discover missing usernames from the Source of Truth
if [ "$USERNAME" == "null" ] || [ -z "$USERNAME" ]; then
    USERNAME=$(mysql -N -s -e "SELECT username FROM panel_core.domains WHERE domain_name='$DOMAIN' LIMIT 1;")
fi

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
    mkdir -p "/home/$USERNAME/web/$DOMAIN/tmp"

    # Copy the Stackrium default page into the new domain
    if [ -f /opt/panel/templates/index.html ]; then
        cp /opt/panel/templates/index.html "$WEB_ROOT/index.html"
    else
        # Fallback just in case the template goes missing
        echo "<h1>Welcome to $DOMAIN</h1><p>Powered by Stackrium</p>" > "$WEB_ROOT/index.html"
    fi

    # Fix permissions (User owns their files, www-data can read them)
    chown -R $USERNAME:$USERNAME "/home/$USERNAME/web/$DOMAIN"
    chown -R www-data:www-data "$LOG_DIR"
    chmod -R 755 "/home/$USERNAME/web/$DOMAIN"

    # 2. Generate Nginx Configuration (HEREDOC)
    cat > "$VHOST_CONF" <<EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;

    # Load Proxy/CDN Real-IP settings if they exist
    include /etc/nginx/conf.d/domains/$DOMAIN-proxy*.conf;
    
    # MIME Types safety net to prevent files from downloading
    include /etc/nginx/mime.types;
    
    root $WEB_ROOT;
    index index.php index.html index.htm;
    
    # Custom Stackrium Error Pages
    include /etc/nginx/snippets/stackrium-errors.conf;

    access_log $LOG_DIR/access.log json_access;
    error_log $LOG_DIR/error.log;

    location / {
        try_files \$uri \$uri/ /index.php?\$args;
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
; Stackrium Ondemand Architecture
pm = ondemand
pm.max_children = 5
pm.process_idle_timeout = 10s
pm.max_requests = 200
EOF
        # Test PHP syntax before restarting
        if php-fpm$PHP_VERSION -t > /dev/null 2>&1; then
            systemctl restart php$PHP_VERSION-fpm
        else
            # Rollback the toxic file so the server stays online
            rm -f "$POOL_CONF"
            echo "Error: PHP-FPM pool generation failed syntax check. Rolled back."
            exit 1
        fi
    fi
    
    # ==========================================
    # Auto-Generate BIND9 DNS & Sync Source of Truth
    # ==========================================
    SERVER_IP=$(curl -s ifconfig.me)
    SERIAL=$(date +%Y%m%d01)
    
    IS_SUBDOMAIN=$(echo "$PAYLOAD" | jq -r '.is_subdomain // "false"')

    if [ "$IS_SUBDOMAIN" == "true" ]; then
        # ---> SUBDOMAIN DNS LOGIC (Inject into Parent) <---
        PARENT_DOMAIN=$(echo "$PAYLOAD" | jq -r '.parent_domain')
        PREFIX=$(echo "$PAYLOAD" | jq -r '.prefix')
        PARENT_ZONE_FILE="/etc/bind/zones/db.$PARENT_DOMAIN"

        if [ -f "$PARENT_ZONE_FILE" ]; then
            # Inject the A record for the subdomain and its 'www' alias
            echo "$PREFIX       IN      A       $SERVER_IP" >> "$PARENT_ZONE_FILE"
            echo "www.$PREFIX   IN      A       $SERVER_IP" >> "$PARENT_ZONE_FILE"
            
            # Increment the Parent's Serial Number safely
            CURRENT_SERIAL=$(grep -oE '[0-9]{10}' "$PARENT_ZONE_FILE" | head -1)
            if [ -n "$CURRENT_SERIAL" ]; then
                NEW_SERIAL=$((CURRENT_SERIAL + 1))
                sed -i "s/$CURRENT_SERIAL/$NEW_SERIAL/g" "$PARENT_ZONE_FILE"
            fi
            
            systemctl reload bind9

            # Sync the Database Source of Truth
            mysql -e "INSERT IGNORE INTO panel_core.dns_records (domain_name, record_name, record_type, record_value) VALUES 
            ('$PARENT_DOMAIN', '$PREFIX', 'A', '$SERVER_IP'),
            ('$PARENT_DOMAIN', 'www.$PREFIX', 'A', '$SERVER_IP');"
        fi

    else
        # ---> PRIMARY DOMAIN DNS LOGIC (Create New SOA Zone) <---
        ZONE_FILE="/etc/bind/zones/db.$DOMAIN"
        CONF_FILE="/etc/bind/named.conf.local"

        if [ ! -f "$ZONE_FILE" ]; then
            cat <<EOF > "$ZONE_FILE"
\$TTL    86400
@       IN      SOA     ns1.$DOMAIN. admin.$DOMAIN. (
                     $SERIAL         ; Serial
                     3600            ; Refresh
                     1800            ; Retry
                     604800          ; Expire
                     86400 )         ; Minimum TTL
@       IN      NS      ns1.$DOMAIN.
@       IN      NS      ns2.$DOMAIN.
@       IN      A       $SERVER_IP
ns1     IN      A       $SERVER_IP
ns2     IN      A       $SERVER_IP
www     IN      A       $SERVER_IP
mail    IN      A       $SERVER_IP
ftp     IN      CNAME   $DOMAIN.
@       IN      MX      10 mail.$DOMAIN.
@       IN      TXT     "v=spf1 a mx ip4:$SERVER_IP ~all"
EOF
            chown bind:bind "$ZONE_FILE"
            chmod 644 "$ZONE_FILE"

            # Inject into BIND9 config
            if ! grep -q "zone \"$DOMAIN\"" "$CONF_FILE"; then
                echo "zone \"$DOMAIN\" { type master; file \"$ZONE_FILE\"; };" >> "$CONF_FILE"
            fi

            # Sync the Database Source of Truth
            mysql -e "INSERT IGNORE INTO panel_core.dns_records (domain_name, record_name, record_type, record_value) VALUES 
            ('$DOMAIN', '@', 'A', '$SERVER_IP'), 
            ('$DOMAIN', 'ns1', 'A', '$SERVER_IP'), 
            ('$DOMAIN', 'ns2', 'A', '$SERVER_IP'), 
            ('$DOMAIN', 'www', 'A', '$SERVER_IP'), 
            ('$DOMAIN', 'mail', 'A', '$SERVER_IP'),
            ('$DOMAIN', 'ftp', 'CNAME', '$DOMAIN.'), 
            ('$DOMAIN', '@', 'MX', '10 mail.$DOMAIN.'), 
            ('$DOMAIN', '@', 'TXT', 'v=spf1 a mx ip4:$SERVER_IP ~all');"
            
            systemctl reload bind9
        fi
    fi

    # 3. Enable the site and test Nginx
    ln -s "$VHOST_CONF" "$NGINX_ENABLED/"
    
    if nginx -t; then
        /opt/panel/scripts/nginx_reload_callback.sh "$TASK_ID" > /dev/null 2>&1 &
        # ---> SOURCE OF TRUTH TRACKING <---
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
        /opt/panel/scripts/nginx_reload_callback.sh "$TASK_ID" > /dev/null 2>&1 &
        
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
        /opt/panel/scripts/nginx_reload_callback.sh "$TASK_ID" > /dev/null 2>&1 &
        mysql -e "UPDATE panel_core.domains SET waf_enabled = $DB_VAL WHERE domain_name = '$DOMAIN';"
        echo "Success: ModSecurity WAF for $DOMAIN is now $STATUS."
        exit 0
    else
        echo "Error: Nginx failed to reload after WAF toggle."
        exit 1
    fi
# ==========================================
# ACTION: ADVANCED HTTPS & HSTS ROUTING
# ==========================================
elif [ "$ACTION" == "update_routing" ]; then
    
    FORCE_HTTPS=$(echo "$PAYLOAD" | jq -r '.force_https')
    HSTS_ENABLED=$(echo "$PAYLOAD" | jq -r '.hsts_enabled')
    HSTS_MAX_AGE=$(echo "$PAYLOAD" | jq -r '.hsts_max_age')
    HSTS_SUBDOMAINS=$(echo "$PAYLOAD" | jq -r '.hsts_subdomains')
    HSTS_PRELOAD=$(echo "$PAYLOAD" | jq -r '.hsts_preload')

    if [ ! -f "$VHOST_CONF" ]; then
        echo "Error: Nginx vhost for $DOMAIN not found."
        exit 1
    fi

    echo "Applying routing rules for $DOMAIN..."
    cp "$VHOST_CONF" "$VHOST_CONF.bak"

    # 1. Strip out any old routing rules so we start clean
    sed -i '/#force_https/d' "$VHOST_CONF"
    sed -i '/#hsts/d' "$VHOST_CONF"

    # 2. Inject Force HTTPS (Only inside the Port 80 Block)
    if [ "$FORCE_HTTPS" == "true" ]; then
        # We use awk to find the 'listen 80' block, and inject the redirect immediately after the 'server_name'
        awk '/listen 80/ {in_80=1} /listen 443/ {in_80=0} in_80 && /server_name/ && !done_80 { print $0; print "    return 301 https://$host$request_uri; #force_https"; done_80=1; next } 1' "$VHOST_CONF" > "${VHOST_CONF}.tmp" && mv "${VHOST_CONF}.tmp" "$VHOST_CONF"
    fi

    # 3. Inject HSTS Header (Only inside the Port 443 SSL Block)
    if [ "$HSTS_ENABLED" == "true" ]; then
        HSTS_STRING="max-age=$HSTS_MAX_AGE"
        if [ "$HSTS_SUBDOMAINS" == "true" ]; then HSTS_STRING="$HSTS_STRING; includeSubDomains"; fi
        if [ "$HSTS_PRELOAD" == "true" ]; then HSTS_STRING="$HSTS_STRING; preload"; fi

        # We use awk to find the 'listen 443' block, and inject the header immediately after the SSL certificate definitions
        awk -v hsts="$HSTS_STRING" '/listen 443/ {in_443=1} /listen 80/ {in_443=0} in_443 && /ssl_certificate_key/ && !done_443 { print $0; print "    add_header Strict-Transport-Security \"" hsts "\" always; #hsts"; done_443=1; next } 1' "$VHOST_CONF" > "${VHOST_CONF}.tmp" && mv "${VHOST_CONF}.tmp" "$VHOST_CONF"
    fi

    # 4. Safely test and reload
    if nginx -t > /dev/null 2>&1; then
        /opt/panel/scripts/nginx_reload_callback.sh "$TASK_ID" > /dev/null 2>&1 &
        rm "$VHOST_CONF.bak"
        echo "Success: Advanced routing and HSTS applied."
        exit 0
    else
        echo "Error: Syntax error generated. Rolling back changes."
        mv "$VHOST_CONF.bak" "$VHOST_CONF"
        /opt/panel/scripts/nginx_reload_callback.sh "$TASK_ID" > /dev/null 2>&1 &
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
        /opt/panel/scripts/nginx_reload_callback.sh "$TASK_ID" > /dev/null 2>&1 &
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