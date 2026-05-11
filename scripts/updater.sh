#!/bin/bash
# /opt/panel/scripts/updater.sh

DOWNLOAD_URL=$1
CHANNEL=$2
STATUS_FILE="/opt/panel/www/config/update_status.json"
TEMP_DIR="/tmp/stackrium_update"

set_progress() {
    echo "{\"progress\": $1, \"step\": \"$2\", \"status\": \"running\"}" > "$STATUS_FILE"
    chown www-data:www-data "$STATUS_FILE"
}

# 1. INITIALIZE (10%)
set_progress 10 "Initializing Update Engine..."
mkdir -p "$TEMP_DIR"
rm -rf "${TEMP_DIR:?}/"*

# 2. BACKUP CURRENT STATE (20%)
set_progress 20 "Backing up current panel state..."
mkdir -p /opt/panel/backups/system
tar -czf /opt/panel/backups/system/panel_core_$(date +%F).tar.gz /opt/panel/www/ /opt/panel/scripts/ /opt/panel/daemon/
mysqldump panel_core > /opt/panel/backups/system/db_core_$(date +%F).sql

# 3. DOWNLOAD & EXTRACT (40%)
set_progress 40 "Downloading $CHANNEL release..."
wget -q -O "$TEMP_DIR/update.zip" "$DOWNLOAD_URL"

if [ ! -s "$TEMP_DIR/update.zip" ]; then
    echo "{\"progress\": 40, \"step\": \"Download Failed!\", \"status\": \"error\"}" > "$STATUS_FILE"
    exit 1
fi

set_progress 60 "Extracting payload..."
unzip -q -o "$TEMP_DIR/update.zip" -d "$TEMP_DIR/extracted"

# Ensure the zip actually contained a payload folder!
if [ ! -d "$TEMP_DIR/extracted/payload" ]; then
    echo "{\"progress\": 60, \"step\": \"Invalid Zip Structure (No payload folder)!\", \"status\": \"error\"}" > "$STATUS_FILE"
    exit 1
fi

systemctl stop panel-daemon

# 4. THE RSYNC SWAP (80%)
set_progress 80 "Applying new core files..."
rsync -av --delete --exclude 'config/' --exclude 'logs/' --exclude 'backups/' --exclude 'license.key' --exclude 'www/assets/custom/' "$TEMP_DIR/extracted/payload/" "/opt/panel/" > /dev/null 2>&1

# 5. RESTORE PASSWORDS & PERMISSIONS (90%)
set_progress 90 "Configuring permissions & credentials..."

# Dynamically pull the password from the protected config file
DB_PASS=$(grep "'DB_PASS'" /opt/panel/www/config/database.php | cut -d"'" -f4)

# Inject the password back into the fresh Python daemons
sed -i "s/YOUR_SECURE_PASSWORD/$DB_PASS/g" /opt/panel/daemon/worker.py
sed -i "s/YOUR_DB_PASSWORD/$DB_PASS/g" /opt/panel/daemon/scheduler.py

chown -R www-data:www-data /opt/panel/www
chown -R root:root /opt/panel/scripts /opt/panel/daemon
chmod +x /opt/panel/scripts/*.sh
chmod +x /opt/panel/daemon/*.py

# 6. FINISH (100%)
# We MUST write the success message before restarting PHP!
echo "{\"progress\": 100, \"step\": \"Update Complete! Rebooting panel...\", \"status\": \"complete\"}" > "$STATUS_FILE"
chown www-data:www-data "$STATUS_FILE"

# 7. RESTART EVERYTHING (This will safely kill the script)
rm -rf "$TEMP_DIR"
systemctl start panel-daemon
systemctl restart nginx
systemctl restart php8.3-fpm