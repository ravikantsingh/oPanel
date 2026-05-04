#!/bin/bash
# Executed by Python Daemon as root
# Purpose: Suspend or Unsuspend a domain using an Nginx 503 Intercept

PAYLOAD=$1
ACTION=$(echo "$PAYLOAD" | jq -r '.action')     # 'suspend' or 'unsuspend'
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')

VHOST_CONF="/etc/nginx/sites-available/$DOMAIN.conf"

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

    # Inject the intercept flag right below the SSL configuration block
    # This ensures HTTPS still works, but traffic is immediately dropped with a 503
    # Inject the modular snippet
    sed -i '/server_name/a \    include /etc/nginx/snippets/domain-suspended.conf; # OPANEL_SUSPEND_FLAG' "$VHOST_CONF"
    
    echo "Success: $DOMAIN has been suspended."

elif [ "$ACTION" == "unsuspend" ]; then
    # Delete the modular snippet
    sed -i '/include \/etc\/nginx\/snippets\/domain-suspended.conf; # OPANEL_SUSPEND_FLAG/d' "$VHOST_CONF"
    
    echo "Success: $DOMAIN has been unsuspended."

else
    echo "Error: Invalid action. Must be 'suspend' or 'unsuspend'."
    exit 1
fi

# Reload Nginx to apply the proxy intercept instantly
systemctl reload nginx
exit 0