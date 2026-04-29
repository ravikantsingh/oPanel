#!/bin/bash
# /opt/panel/scripts/backup_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
ACTION=$(echo "$PAYLOAD" | jq -r '.action')
TARGET=$(echo "$PAYLOAD" | jq -r '.target') # Domain name OR Database name
IS_AUTO=$(echo "$PAYLOAD" | jq -r '.is_auto // "false"') # 'true' or 'false'
RETENTION=$(echo "$PAYLOAD" | jq -r '.retention // "0"') # Number of days to keep

TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")

# Determine File Prefix
if [ "$IS_AUTO" == "true" ]; then
    PREFIX="auto_"
else
    PREFIX="manual_"
fi

if [ "$ACTION" == "backup_db" ]; then
    DIR="/opt/panel/backups/databases"
    BACKUP_FILE="${DIR}/${PREFIX}${TARGET}_${TIMESTAMP}.sql.gz"
    
    # Check if database exists
    if ! mysql -e "USE \`${TARGET}\`;" 2>/dev/null; then
        echo "Error: Database $TARGET does not exist."
        exit 1
    fi
    
    # Dump and compress
    mysqldump "${TARGET}" | gzip > "$BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        chgrp www-data "$BACKUP_FILE"
        chmod 640 "$BACKUP_FILE"
        echo "Success: Database $TARGET backed up to $BACKUP_FILE"
        
        # --- AUTO CLEANUP LOGIC ---
        if [ "$IS_AUTO" == "true" ] && [ "$RETENTION" -gt 0 ]; then
            echo "Running retention policy: Deleting 'auto_' DB backups older than $RETENTION days..."
            find "$DIR" -name "auto_${TARGET}_*.sql.gz" -type f -mtime +$RETENTION -exec rm -f {} \;
        fi
        exit 0
    else
        echo "Error: Failed to backup database $TARGET"
        exit 1
    fi

elif [ "$ACTION" == "backup_web" ]; then
    
    OWNER=$(mysql -N -B -e "SELECT username FROM panel_core.domains WHERE domain_name='$TARGET';")
    
    if [ -z "$OWNER" ]; then
        echo "Error: Could not find owner for domain $TARGET."
        exit 1
    fi
    
    WEB_ROOT="/home/${OWNER}/web/${TARGET}/public_html"
    DIR="/opt/panel/backups/websites"
    BACKUP_FILE="${DIR}/${PREFIX}${TARGET}_${TIMESTAMP}.tar.gz"
    
    if [ ! -d "$WEB_ROOT" ]; then
        echo "Error: Directory $WEB_ROOT does not exist."
        exit 1
    fi
    
    # Compress the public_html folder
    tar -czf "$BACKUP_FILE" -C "/home/${OWNER}/web/${TARGET}" public_html

    if [ $? -eq 0 ]; then
        chgrp www-data "$BACKUP_FILE"
        chmod 640 "$BACKUP_FILE"
        echo "Success: Website $TARGET backed up to $BACKUP_FILE"
        
        # --- AUTO CLEANUP LOGIC ---
        if [ "$IS_AUTO" == "true" ] && [ "$RETENTION" -gt 0 ]; then
            echo "Running retention policy: Deleting 'auto_' Web backups older than $RETENTION days..."
            find "$DIR" -name "auto_${TARGET}_*.tar.gz" -type f -mtime +$RETENTION -exec rm -f {} \;
        fi
        exit 0
    else
        echo "Error: Failed to compress website $TARGET"
        exit 1
    fi

else
    echo "Error: Unknown action."
    exit 1
fi