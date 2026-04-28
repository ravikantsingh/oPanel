#!/bin/bash
# /opt/panel/scripts/ssl_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1

if [ -z "$PAYLOAD" ]; then
    echo "Error: No JSON payload provided."
    exit 1
fi

ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
# Use the provided email for Let's Encrypt expiry notices, or default to admin@domain
EMAIL=$(echo "$PAYLOAD" | jq -r '.email // "admin@'$DOMAIN'"')

if [ "$ACTION" == "install" ]; then
    
    echo "Starting Let's Encrypt provisioning for $DOMAIN..."

    # ---> UPGRADE 1: Removed hardcoded 'www' so subdomains succeed <---
    certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "$EMAIL" --redirect

    # Check if Certbot succeeded (Exit Code 0)
    if [ $? -eq 0 ]; then
        # ---> UPGRADE 2: Use the correct 'has_ssl' boolean column <---
        mysql -e "UPDATE panel_core.domains SET has_ssl = 1 WHERE domain_name = '$DOMAIN';"
        echo "Success: SSL installed and Nginx reconfigured for HTTPS."
        exit 0
    else
        mysql -e "UPDATE panel_core.domains SET has_ssl = 0 WHERE domain_name = '$DOMAIN';"
        echo "Error: Certbot failed. Ensure $DOMAIN points to this server's public IP address."
        exit 1
    fi

else
    echo "Error: Unknown sub_action '$ACTION'."
    exit 1
fi