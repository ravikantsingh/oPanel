#!/bin/bash
# /opt/panel/scripts/backup_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
ACTION=$(echo "$PAYLOAD" | jq -r '.action')
TARGET=$(echo "$PAYLOAD" | jq -r '.target') # Domain name OR Database name
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")

if [ "$ACTION" == "backup_db" ]; then
    
    BACKUP_FILE="/opt/panel/backups/databases/${TARGET}_${TIMESTAMP}.sql.gz"
    
    # Check if database exists
    if ! mysql -e "USE \`${TARGET}\`;" 2>/dev/null; then
        echo "Error: Database $TARGET does not exist."
        exit 1
    fi
    
    # Dump and compress on the fly
    mysqldump "${TARGET}" | gzip > "$BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        echo "Success: Database $TARGET backed up to $BACKUP_FILE"
        exit 0
    else
        echo "Error: Failed to backup database $TARGET"
        exit 1
    fi

elif [ "$ACTION" == "backup_web" ]; then
    
    # We query the database to find the owner so we know where the files are
    OWNER=$(mysql -N -B -e "SELECT username FROM panel_core.domains WHERE domain_name='$TARGET';")
    
    if [ -z "$OWNER" ]; then
        echo "Error: Could not find owner for domain $TARGET in the control panel."
        exit 1
    fi
    
    WEB_ROOT="/home/${OWNER}/web/${TARGET}/public_html"
    BACKUP_FILE="/opt/panel/backups/websites/${TARGET}_${TIMESTAMP}.tar.gz"
    
    if [ ! -d "$WEB_ROOT" ]; then
        echo "Error: Directory $WEB_ROOT does not exist."
        exit 1
    fi
    
    # Compress the public_html folder
    tar -czf "$BACKUP_FILE" -C "/home/${OWNER}/web/${TARGET}" public_html

    chgrp www-data "$BACKUP_FILE"
    chmod 640 "$BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        echo "Success: Website $TARGET backed up to $BACKUP_FILE"
        exit 0
    else
        echo "Error: Failed to compress website $TARGET"
        exit 1
    fi

else
    echo "Error: Unknown action."
    exit 1
fi