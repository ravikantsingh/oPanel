#!/bin/bash
# Executed by Python Daemon as root
# Purpose: Initialize a Laravel Environment, configure Nginx, and spin up Queue Workers

PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')

APP_DIR="/home/$USERNAME/web/$DOMAIN/public_html"
VHOST="/etc/nginx/sites-available/$DOMAIN.conf"

if [ ! -d "$APP_DIR" ]; then
    echo "Error: Application directory not found."
    exit 1
fi

echo "Initializing Laravel Environment for $DOMAIN..."

# 1. Reconfigure Nginx Document Root to /public
# This securely hides the .env file and application logic from the public web
if grep -q "root /home/$USERNAME/web/$DOMAIN/public_html;" "$VHOST"; then
    sed -i "s|root /home/$USERNAME/web/$DOMAIN/public_html;|root /home/$USERNAME/web/$DOMAIN/public_html/public;|g" "$VHOST"
    systemctl reload nginx
fi

# 2. Extract DB Password to update the oPanel Database
DB_PASS=$(grep DB_PASS /opt/panel/www/config/database.php | cut -d"'" -f4)
MYSQL_CMD="mysql -B -N -upanel_user -p${DB_PASS} panel_core -e"

# 3. Setup the Laravel Application (Running strictly as the client user to prevent root-ownership lockouts)
cd "$APP_DIR"

# Check if it's actually a Laravel app by looking for artisan or composer.json
if [ -f "artisan" ] || [ -f "composer.json" ]; then

    # Install Composer Dependencies
    if [ -f "composer.json" ]; then
        echo "Running Composer Install..."
        su - "$USERNAME" -c "cd $APP_DIR && composer install --no-interaction --prefer-dist --optimize-autoloader"
    fi

    # Scaffold the .env file if missing
    if [ ! -f ".env" ] && [ -f ".env.example" ]; then
        echo "Generating .env file and App Key..."
        su - "$USERNAME" -c "cd $APP_DIR && cp .env.example .env"
        su - "$USERNAME" -c "cd $APP_DIR && php artisan key:generate"
    fi

    # Create the Storage Symlink
    su - "$USERNAME" -c "cd $APP_DIR && php artisan storage:link"

    # Clear and Cache Optimizations
    su - "$USERNAME" -c "cd $APP_DIR && php artisan optimize:clear"

    # 4. Enforce Strict Laravel Permissions
    echo "Applying security permissions to storage and cache..."
    chown -R "$USERNAME:www-data" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
    chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

    # 5. Spin up the Laravel Queue Worker in the background using PM2
    WORKER_NAME="laravel-worker-$DOMAIN"
    echo "Starting background queue worker: $WORKER_NAME"
    
    # Check if worker already exists, restart if it does, start if it doesn't
    if su - "$USERNAME" -c "pm2 describe $WORKER_NAME" > /dev/null 2>&1; then
        su - "$USERNAME" -c "pm2 restart $WORKER_NAME"
    else
        su - "$USERNAME" -c "pm2 start $APP_DIR/artisan --name '$WORKER_NAME' --interpreter php -- queue:work --tries=3"
        su - "$USERNAME" -c "pm2 save"
    fi

    # 6. Officially Register the App Environment in the oPanel Database
    $MYSQL_CMD "UPDATE domains SET app_type='laravel', document_root='public_html/public', pm2_process='$WORKER_NAME' WHERE domain_name='$DOMAIN';"

    echo "Laravel Deployment Complete! App is live."
    exit 0

else
    echo "Error: No 'artisan' or 'composer.json' file found. Please upload your Laravel code first."
    # Revert database if they tried to deploy an empty folder
    $MYSQL_CMD "UPDATE domains SET app_type='php', document_root='public_html', pm2_process=NULL WHERE domain_name='$DOMAIN';"
    exit 1
fi