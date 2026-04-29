#!/bin/bash
# /opt/panel/scripts/wp_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')
WP_TITLE=$(echo "$PAYLOAD" | jq -r '.wp_title')
WP_ADMIN=$(echo "$PAYLOAD" | jq -r '.wp_admin')
WP_PASS=$(echo "$PAYLOAD" | jq -r '.wp_pass')
WP_EMAIL=$(echo "$PAYLOAD" | jq -r '.wp_email // "admin@'$DOMAIN'"')

DOC_ROOT="/home/$USERNAME/web/$DOMAIN/public_html"

# 1. Security & Directory Checks
if [ ! -d "$DOC_ROOT" ]; then
    echo "Error: Document root does not exist."
    exit 1
fi

if [ "$(ls -A $DOC_ROOT | grep -v 'index.php')" ]; then
    echo "Error: The public_html directory is not empty. Please clear it before installing WordPress."
    exit 1
fi

# Clean the default oPanel index.php if it exists
rm -f "$DOC_ROOT/index.php"

# 2. Generate Database Credentials dynamically
DB_NAME="${USERNAME}_wp_$(openssl rand -hex 3)"
DB_USER="${USERNAME}_usr_$(openssl rand -hex 3)"
DB_PASS=$(openssl rand -base64 18 | tr -dc 'a-zA-Z0-9!@#%^&*')

# 3. Create the WordPress Database
mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`;"
mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Source of Truth: Log the DB in the panel
mysql -e "INSERT IGNORE INTO panel_core.databases (db_name, db_user, owner_username) VALUES ('$DB_NAME', '$DB_USER', '$USERNAME');"

# 4. Download WP-CLI temporarily
wget -qO /tmp/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x /tmp/wp-cli.phar

# ---> PERMISSION HANDOFF <---
# We execute WP-CLI strictly as the Linux user so files are owned by them, not root!
WP_CMD="sudo -u $USERNAME /tmp/wp-cli.phar --path=$DOC_ROOT"

# 5. Install WordPress Core
$WP_CMD core download
if [ $? -ne 0 ]; then
    echo "Error: Failed to download WordPress."
    rm -f /tmp/wp-cli.phar
    exit 1
fi

# 6. Generate wp-config.php
$WP_CMD config create --dbname="$DB_NAME" --dbuser="$DB_USER" --dbpass="$DB_PASS" --dbhost="localhost" --extra-php <<PHP
define( 'FS_METHOD', 'direct' );
PHP

# 7. Execute the Installation
$WP_CMD core install --url="https://$DOMAIN" --title="$WP_TITLE" --admin_user="$WP_ADMIN" --admin_password="$WP_PASS" --admin_email="$WP_EMAIL"

# 8. Cleanup & Security Lock
rm -f /tmp/wp-cli.phar
chown -R $USERNAME:$USERNAME "$DOC_ROOT"
find "$DOC_ROOT" -type d -exec chmod 755 {} \;
find "$DOC_ROOT" -type f -exec chmod 644 {} \;
chmod 600 "$DOC_ROOT/wp-config.php"

echo "Success: WordPress successfully installed on $DOMAIN!"
exit 0