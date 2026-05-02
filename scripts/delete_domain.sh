#!/bin/bash
# /opt/panel/scripts/delete_domain.sh
# Executed by Python Daemon as root

PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')

echo "Initiating Scorched Earth protocol for $DOMAIN..."

# 1. Remove Nginx Configurations
rm -f /etc/nginx/sites-available/$DOMAIN.conf
rm -f /etc/nginx/sites-enabled/$DOMAIN.conf

# 2. Clean up Let's Encrypt SSL
certbot delete --cert-name "$DOMAIN" --non-interactive 2>/dev/null

# ---> THE NEW FIX: SAFE PANEL FALLBACK <---
# If the master panel was using this certificate, gracefully revert it to self-signed keys
if grep -q "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" /etc/nginx/sites-available/default; then
    echo "Master Panel was using this domain. Reverting port 7443 to self-signed certificates..."
    sed -i "s|/etc/letsencrypt/live/${DOMAIN}/fullchain.pem|/etc/ssl/certs/mypanel-selfsigned.crt|g" /etc/nginx/sites-available/default
    sed -i "s|/etc/letsencrypt/live/${DOMAIN}/privkey.pem|/etc/ssl/private/mypanel-selfsigned.key|g" /etc/nginx/sites-available/default
fi

# 3. Destroy the Web Root
WEB_ROOT="/home/$USERNAME/web/$DOMAIN"
if [ -d "$WEB_ROOT" ]; then
    rm -rf "$WEB_ROOT"
fi

# 4. Clean up BIND9 DNS Zone
ZONE_FILE="/etc/bind/zones/db.$DOMAIN"
if [ -f "$ZONE_FILE" ]; then
    rm -f "$ZONE_FILE"
    sed -i "/zone \"$DOMAIN\"/d" /etc/bind/named.conf.local
    systemctl reload bind9
fi

# 5. Clean up Pure-FTPd Virtual Users
for FTP_USER in $(mysql -N -B -e "SELECT ftp_user FROM panel_core.ftp_accounts WHERE domain_name='$DOMAIN';"); do
    pure-pw userdel "$FTP_USER"
done
pure-pw mkdb

# 6. Source of Truth Cleanup
mysql -e "DELETE FROM panel_core.dns_records WHERE domain_name='$DOMAIN';"
mysql -e "DELETE FROM panel_core.ftp_accounts WHERE domain_name='$DOMAIN';"
mysql -e "DELETE FROM panel_core.domains WHERE domain_name='$DOMAIN';"

# 7. Safely Reload Nginx
if nginx -t > /dev/null 2>&1; then
    systemctl reload nginx
    echo "Success: Domain $DOMAIN and all associated files/accounts have been permanently deleted."
    exit 0
else
    echo "Critical Warning: Domain deleted, but Nginx failed to reload. Please check server logs."
    exit 1
fi