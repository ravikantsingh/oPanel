#!/bin/bash
# /opt/panel/scripts/updater.sh
# Usage: ./updater.sh "https://stackrium.com/downloads/file.zip" "stable"

DOWNLOAD_URL=$1
CHANNEL=$2
STATUS_FILE="/opt/panel/www/config/update_status.json"
TEMP_DIR="/tmp/stackrium_update"

# Helper function to update the UI Progress Bar
set_progress() {
    echo "{\"progress\": $1, \"step\": \"$2\", \"status\": \"running\"}" > "$STATUS_FILE"
    chown www-data:www-data "$STATUS_FILE"
}

# 1. INITIALIZE (0%)
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

# 4. PRE-FLIGHT (Stop Services)
systemctl stop panel-daemon

# 5. THE RSYNC SWAP (80%)
# This safely mirrors the new files, deletes orphaned files, but EXCLUDES configs and logs!
set_progress 80 "Applying new core files..."
rsync -av --delete --exclude 'config/' --exclude 'logs/' --exclude 'backups/' --exclude 'license.key' "$TEMP_DIR/extracted/payload/" "/opt/panel/" > /dev/null 2>&1

# 6. POST-FLIGHT (Permissions & Migrations)
set_progress 90 "Configuring permissions and restarting services..."
chown -R www-data:www-data /opt/panel/www
chown -R root:root /opt/panel/scripts /opt/panel/daemon
chmod +x /opt/panel/scripts/*.sh
chmod +x /opt/panel/daemon/*.py

# Restart everything
systemctl restart nginx
systemctl restart php8.3-fpm
systemctl start panel-daemon

# Clean up
rm -rf "$TEMP_DIR"

# 7. FINISH (100%)
echo "{\"progress\": 100, \"step\": \"Update Complete! Rebooting panel...\", \"status\": \"complete\"}" > "$STATUS_FILE"