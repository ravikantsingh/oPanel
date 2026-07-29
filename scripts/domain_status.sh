#!/bin/bash
# Executed by Python Daemon as root
# Purpose: Suspend or Unsuspend a domain using an Nginx 503 Intercept

PAYLOAD=$1
TASK_ID=$2
ACTION=$(echo "$PAYLOAD" | jq -r '.action')     # 'suspend' or 'unsuspend'
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')

VHOST_CONF="/etc/nginx/sites-available/$DOMAIN.conf"

DB_PASS=$(grep DB_PASS /opt/panel/www/config/database.php | cut -d"'" -f4)
MYSQL_CMD="mysql -upanel_user -p${DB_PASS} panel_core -e"

if [ ! -f "$VHOST_CONF" ]; then
    echo "Error: Nginx configuration for $DOMAIN not found."
    exit 1
fi

if [ "$ACTION" == "suspend" ]; then
    # Check if it's already suspended to prevent duplicate lines
    if grep -q "OPANEL_SUSPEND_FLAG" "$VHOST_CONF"; then
        echo "Domain is already suspended."
        exit 0
    fi

    # This ensures HTTPS still works, but traffic is immediately dropped with a 503
    sed -i '/server_name/a \    include /etc/nginx/snippets/domain-suspended.conf; # OPANEL_SUSPEND_FLAG' "$VHOST_CONF"

    # Update the Source of Truth
    $MYSQL_CMD "UPDATE domains SET status = 'suspended' WHERE domain_name = '$DOMAIN';"
    
    echo "Success: $DOMAIN has been suspended."

elif [ "$ACTION" == "unsuspend" ]; then
    # Delete the modular snippet
    sed -i '/include \/etc\/nginx\/snippets\/domain-suspended.conf; # OPANEL_SUSPEND_FLAG/d' "$VHOST_CONF"

    # Update the Source of Truth
    $MYSQL_CMD "UPDATE domains SET status = 'active' WHERE domain_name = '$DOMAIN';"
    
    echo "Success: $DOMAIN has been unsuspended."

else
    echo "Error: Invalid action. Must be 'suspend' or 'unsuspend'."
    exit 1
fi

# Reload Nginx to apply the proxy intercept instantly
/opt/panel/scripts/nginx_reload_callback.sh "$TASK_ID" > /dev/null 2>&1 &
exit 0