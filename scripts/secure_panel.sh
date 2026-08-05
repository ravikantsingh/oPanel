#!/bin/bash
# /opt/panel/scripts/secure_panel.sh
# Swaps the Master Panel SSL certificates based on the UI flag

PAYLOAD=$1
TASK_ID=$2
ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain // empty')

if [ "$ACTION" == "bind" ]; then
    echo "Binding Stackrium to ${DOMAIN}..."
    CERT="/etc/letsencrypt/live/${DOMAIN}/fullchain.pem"
    KEY="/etc/letsencrypt/live/${DOMAIN}/privkey.pem"
    # Automate Pure-FTPd SSL Integration
    echo "Merging certificates for Pure-FTPd TLS..."
    cat "$KEY" "$CERT" > /etc/ssl/private/pure-ftpd.pem
    chmod 600 /etc/ssl/private/pure-ftpd.pem
    systemctl restart pure-ftpd
elif [ "$ACTION" == "unbind" ]; then
    echo "Unbinding Stackrium. Reverting to Server IP..."
    CERT="/etc/ssl/certs/mypanel-selfsigned.crt"
    KEY="/etc/ssl/private/mypanel-selfsigned.key"
    DOMAIN="_" # Nginx catch-all for IP
else
    echo "Error: Unknown action."
    exit 1
fi

# Write BOTH the Panel config and the Catch-All config
cat <<EOF > /etc/nginx/sites-available/default
# ==========================================
# Stackrium Master Configuration (Port 7443)
# ==========================================
server {
    listen 7443 ssl http2;
    listen [::]:7443 ssl http2;
    
    server_name ${DOMAIN}; 

    ssl_certificate ${CERT};
    ssl_certificate_key ${KEY};

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Master Panel WAF
    modsecurity on;
    modsecurity_rules_file /etc/modsecurity/modsecurity.conf;
    modsecurity_rules_file /etc/nginx/waf/stackrium-master.conf;

    include /etc/nginx/conf.d/panel-proxy*.conf;

    root /opt/panel/www;
    index index.php index.html;

    include /etc/nginx/snippets/stackrium-errors.conf;
    include /etc/nginx/snippets/block-bots.conf;

    location ~ /\. { deny all; }
    location ^~ /classes/ { deny all; }
    location ^~ /config/ { deny all; }

    location /ajax/ {
        try_files \$uri \$uri/ =404;
    }

    if (\$request_uri ~ ^/(?!(ajax|classes|config|pma))(.*)\.php(\?|\$)) {
        return 301 /\$1\$2\$is_args\$args;
    }

    location / {
        try_files \$uri \$uri/ \$uri.php\$is_args\$args;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock; 
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf)\$ {
        expires 365d;
        add_header Cache-Control "public, no-transform";
        access_log off; 
    }
}

# ==========================================
# Stackrium Temporary IP Routing (Catch-All)
# ==========================================
server {
    listen 80 default_server;
    listen [::]:80 default_server;

    # ADD HTTPS SUPPORT FOR THE FALLBACK ROUTE
    listen 443 ssl default_server;
    listen [::]:443 ssl default_server;
 
    ssl_certificate /etc/ssl/certs/mypanel-selfsigned.crt;
    ssl_certificate_key /etc/ssl/private/mypanel-selfsigned.key;

    server_name _;
    root /opt/panel/www;

    location = / { return 444; }

    location ~ ^/~([^/]+)/(.*)\$ {
        rewrite ^/~([^/]+)/(.*)\$ /ajax/fm_proxy.php?domain=\$1&path=\$2 last;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock; 
    }
}
EOF

# ---> NEW: Ensure Master WAF file exists before testing <---
mkdir -p /etc/nginx/waf/
touch /etc/nginx/waf/stackrium-master.conf

if nginx -t > /dev/null 2>&1; then
    /opt/panel/scripts/nginx_reload_callback.sh "$TASK_ID" > /dev/null 2>&1 &
    echo "Success: Panel Nginx block updated."
    exit 0
else
    echo "Critical Error: Nginx configuration failed."
    exit 1
fi
