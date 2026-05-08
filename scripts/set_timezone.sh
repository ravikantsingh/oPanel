#!/bin/bash
# /opt/panel/scripts/set_timezone.sh
# Executed by Python Daemon as root

PAYLOAD=$1
TZ=$(echo "$PAYLOAD" | jq -r '.timezone')

if [ -z "$TZ" ]; then
    echo "Error: No timezone provided."
    exit 1
fi

echo "Shifting global server timezone to $TZ..."

# 1. Update the Linux Kernel OS Time Zone
timedatectl set-timezone "$TZ"

# 2. Update PHP-FPM and PHP-CLI
sed -i "s/^;*date.timezone =.*/date.timezone = ${TZ//\//\\/}/" /etc/php/8.3/fpm/php.ini
sed -i "s/^;*date.timezone =.*/date.timezone = ${TZ//\//\\/}/" /etc/php/8.3/cli/php.ini
systemctl restart php8.3-fpm

# 3. Update MariaDB (MySQL)
# Ensure the timezone tables are loaded
mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql mysql > /dev/null 2>&1

# Update the config file for persistence across server reboots
if grep -q "default-time-zone" /etc/mysql/mariadb.conf.d/50-server.cnf; then
    sed -i "s/^default-time-zone.*/default-time-zone = '${TZ//\//\\/}'/" /etc/mysql/mariadb.conf.d/50-server.cnf
else
    sed -i "/\[mysqld\]/a default-time-zone = '$TZ'" /etc/mysql/mariadb.conf.d/50-server.cnf
fi

# ---> THE FIX: Apply instantly WITHOUT restarting MariaDB to prevent severing the Python connection! <---
mysql -e "SET GLOBAL time_zone = '$TZ';"

# 4. Save to oPanel Database (Source of Truth)
mysql -e "INSERT INTO panel_core.settings (setting_key, setting_value) VALUES ('server_timezone', '$TZ') ON DUPLICATE KEY UPDATE setting_value = '$TZ';"

# 5. Restart the Python Daemon safely
# ---> THE FIX: Use systemd to schedule a detached restart 3 seconds in the future <---
systemd-run --on-active=3 /bin/systemctl restart panel-daemon > /dev/null 2>&1

echo "Success: Server core components successfully migrated to $TZ."
exit 0