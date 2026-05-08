#!/bin/bash
# /opt/panel/scripts/secure_panel.sh
# Swaps the Master Panel SSL certificates based on the UI flag

PAYLOAD=$1
ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain // empty')

if [ "$ACTION" == "bind" ]; then
    echo "Binding oPanel to ${DOMAIN}..."
    CERT="/etc/letsencrypt/live/${DOMAIN}/fullchain.pem"
    KEY="/etc/letsencrypt/live/${DOMAIN}/privkey.pem"
elif [ "$ACTION" == "unbind" ]; then
    echo "Unbinding oPanel. Reverting to Server IP..."
    CERT="/etc/ssl/certs/mypanel-selfsigned.crt"
    KEY="/etc/ssl/private/mypanel-selfsigned.key"
    DOMAIN="_" # Nginx catch-all for IP
else
    echo "Error: Unknown action."
    exit 1
fi

cat <<EOF > /etc/nginx/sites-available/default
# Master oPanel Configuration (Port 7443 ONLY)
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
    modsecurity_rules_file /etc/nginx/waf/opanel-master.conf;

    root /opt/panel/www;
    index index.php index.html;

    include /etc/nginx/snippets/opanel-errors.conf;

    location ~ /\. { deny all; }
    location ^~ /classes/ { deny all; }
    location ^~ /config/ { deny all; }

    location /ajax/ {
        try_files \$uri \$uri/ =404;
    }

    if (\$request_uri ~ ^/(?!(ajax|classes|config))(.*)\.php(\?|\$)) {
        return 301 /\$1\$2\$is_args\$args;
    }

    location / {
        try_files \$uri \$uri/ \$uri.php\$is_args\$args;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock; 
    }
}
EOF

# ---> NEW: Ensure Master WAF file exists before testing <---
mkdir -p /etc/nginx/waf/
touch /etc/nginx/waf/opanel-master.conf

if nginx -t > /dev/null 2>&1; then
    systemctl reload nginx
    echo "Success: Panel Nginx block updated."
    exit 0
else
    echo "Critical Error: Nginx configuration failed."
    exit 1
fi