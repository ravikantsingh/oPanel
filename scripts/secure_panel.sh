#!/bin/bash
# /opt/panel/scripts/secure_panel.sh
# Automates the transition from IP/Self-Signed to a Let's Encrypt Domain for the Control Panel

PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
EMAIL=$(echo "$PAYLOAD" | jq -r '.email // "admin@'$DOMAIN'"')

# 1. Generate the Let's Encrypt Certificate
echo "Securing Control Panel on $DOMAIN..."
certbot certonly --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "$EMAIL"

if [ $? -ne 0 ]; then
    echo "Error: Certbot failed to verify $DOMAIN. Ensure DNS is pointing to this server."
    exit 1
fi

# 2. Automatically Overwrite the Panel's Nginx Configuration
cat <<EOF > /etc/nginx/sites-available/default
# Master Control Panel Configuration (Port 7443 ONLY)
server {
    listen 7443 ssl http2;
    listen [::]:7443 ssl http2;
    
    server_name \$DOMAIN; 

    ssl_certificate /etc/letsencrypt/live/\$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/\$DOMAIN/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers HIGH:!aNULL:!MD5;

    root /opt/panel/www;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ =404;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock; 
    }
}
EOF

# 3. Test and Reload
if nginx -t > /dev/null 2>&1; then
    systemctl reload nginx
    echo "Success: Control Panel is now secured and locked to $DOMAIN!"
    exit 0
else
    echo "Critical Error: Nginx configuration failed. Panel SSL not updated."
    exit 1
fi