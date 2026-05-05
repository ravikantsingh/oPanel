#!/bin/bash
# Executed by Python Daemon as root
PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')

DB_PASS=$(grep DB_PASS /opt/panel/www/config/database.php | cut -d"'" -f4)
MYSQL_CMD="mysql -B -N -upanel_user -p${DB_PASS} panel_core -e"

PM2_PROCESS=$($MYSQL_CMD "SELECT pm2_process FROM domains WHERE domain_name='$DOMAIN';")

if [ "$PM2_PROCESS" != "NULL" ] && [ -n "$PM2_PROCESS" ]; then
    echo "Restarting application process: $PM2_PROCESS"
    su - "$USERNAME" -c "pm2 restart $PM2_PROCESS"
    echo "App restarted successfully."
    exit 0
else
    echo "Error: No background process found for this domain."
    exit 1
fi