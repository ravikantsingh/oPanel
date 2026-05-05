#!/bin/bash
# Executed by Python Daemon as root
# Purpose: Destroys Python/Laravel environments and restores FastCGI PHP

PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')

VHOST="/etc/nginx/sites-available/$DOMAIN.conf"
DB_PASS=$(grep DB_PASS /opt/panel/www/config/database.php | cut -d"'" -f4)
MYSQL_CMD="mysql -B -N -upanel_user -p${DB_PASS} panel_core -e"

# 1. Look up current app state
PM2_PROCESS=$($MYSQL_CMD "SELECT pm2_process FROM domains WHERE domain_name='$DOMAIN';")
APP_TYPE=$($MYSQL_CMD "SELECT app_type FROM domains WHERE domain_name='$DOMAIN';")

echo "Reverting $APP_TYPE environment for $DOMAIN back to standard PHP..."

# 2. Kill Background Workers (If any)
if [ "$PM2_PROCESS" != "NULL" ] && [ -n "$PM2_PROCESS" ]; then
    echo "Stopping and deleting PM2 process: $PM2_PROCESS"
    su - "$USERNAME" -c "pm2 delete $PM2_PROCESS"
    su - "$USERNAME" -c "pm2 save --force"
fi

# 3. Clean Nginx VHOST
# Revert Laravel Document Root
sed -i "s|root /home/$USERNAME/web/$DOMAIN/public_html/public;|root /home/$USERNAME/web/$DOMAIN/public_html;|g" "$VHOST"

# Strip Python/Node Reverse Proxy Rules
sed -i '/proxy_pass http/d' "$VHOST"
sed -i '/proxy_set_header Host/d' "$VHOST"
sed -i '/proxy_set_header X-Real-IP/d' "$VHOST"
sed -i '/proxy_set_header X-Forwarded-For/d' "$VHOST"

# Restore try_files if missing
if ! grep -q "try_files" "$VHOST"; then
    sed -i 's|location / {|location / {\n        try_files $uri $uri/ /index.php?$query_string;|g' "$VHOST"
fi

# 4. File System Safety Net (Run BEFORE Nginx Reload)
# If no index file exists, copy the default template
if [ ! -f "/home/$USERNAME/web/$DOMAIN/public_html/index.php" ] && [ ! -f "/home/$USERNAME/web/$DOMAIN/public_html/index.html" ]; then
    
    # Copy the template
    cp /opt/panel/templates/index.html "/home/$USERNAME/web/$DOMAIN/public_html/index.html"
    
    # Secure the permissions
    chown $USERNAME:$USERNAME "/home/$USERNAME/web/$DOMAIN/public_html/index.html"
fi

# 5. Commit the Changes to Nginx
systemctl reload nginx

# 6. Update Database Source of Truth
$MYSQL_CMD "UPDATE domains SET app_type='php', document_root='public_html', app_port=NULL, pm2_process=NULL WHERE domain_name='$DOMAIN';"

echo "Reversion complete. Domain is now a standard PHP environment."
exit 0