#!/bin/bash
# Executed by Python Daemon as root
# Purpose: Retrofit an existing WordPress site with Redis Object Cache

PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')

DOC_ROOT="/home/$USERNAME/web/$DOMAIN/public_html"

echo "Checking WordPress installation for $DOMAIN..."

# 1. SRE Safety Checks
if [ ! -d "$DOC_ROOT" ]; then
    echo "Error: Document root does not exist."
    exit 1
fi

if [ ! -f "$DOC_ROOT/wp-config.php" ]; then
    echo "Error: No wp-config.php found. Please ensure WordPress is actually installed here first."
    exit 1
fi

# ---> THE FIX: Download WP-CLI dynamically <---
wget -qO /tmp/wp-cli-redis.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x /tmp/wp-cli-redis.phar

# Execute WP-CLI strictly as the Linux user
WP_CMD="sudo -u $USERNAME /tmp/wp-cli-redis.phar --path=$DOC_ROOT"

# 2. Extract the Master Redis Password safely
REDIS_PASS=$(grep REDIS_PASS /opt/panel/www/config/redis.php | cut -d"'" -f4)

# 3. Generate the Multi-Tenant Prefix (Prevents data bleeding)
CLEAN_PREFIX=$(echo "$DOMAIN" | tr -cd 'a-zA-Z0-9' | awk '{print $1"_"}')

echo "Injecting Secure Redis configurations..."

# 4. Inject configs using WP-CLI
$WP_CMD config set WP_REDIS_HOST '127.0.0.1' --type=constant
$WP_CMD config set WP_REDIS_PORT 6379 --type=constant --raw
$WP_CMD config set WP_REDIS_PASSWORD "$REDIS_PASS" --type=constant
$WP_CMD config set WP_REDIS_PREFIX "$CLEAN_PREFIX" --type=constant

echo "Installing and Activating Object Cache Drop-in..."

# 5. Install and Enable the Plugin
$WP_CMD plugin install redis-cache --activate
$WP_CMD redis enable
EXIT_CODE=$?

# 6. SRE Cleanup
rm -f /tmp/wp-cli-redis.phar

if [ $EXIT_CODE -eq 0 ]; then
    echo "Success: Redis Object Cache has been retrofitted and activated for $DOMAIN!"
    exit 0
else
    echo "Error: Failed to enable the Redis cache drop-in."
    exit 1
fi