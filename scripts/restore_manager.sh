#!/bin/bash
# /opt/panel/scripts/restore_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
FILE_NAME=$(echo "$PAYLOAD" | jq -r '.file')
TYPE=$(echo "$PAYLOAD" | jq -r '.type')
TARGET=$(echo "$PAYLOAD" | jq -r '.target')

# ==========================================
# ACTION: RESTORE WEBSITE
# ==========================================
if [ "$TYPE" == "Website" ]; then
    BACKUP_PATH="/opt/panel/backups/websites/$FILE_NAME"
    
    if [ ! -f "$BACKUP_PATH" ]; then
        echo "Error: Backup archive not found."
        exit 1
    fi

    # Query the database to find the Linux user who owns this domain
    USERNAME=$(mysql -N -s -e "SELECT username FROM panel_core.domains WHERE domain_name='$TARGET';")
    
    if [ -z "$USERNAME" ]; then
        echo "Error: Domain $TARGET not found in database. Cannot determine ownership."
        exit 1
    fi

    DOC_ROOT="/home/$USERNAME/web/$TARGET/public_html"

    # 1. Wipe the current live site
    rm -rf "$DOC_ROOT"/*
    rm -rf "$DOC_ROOT"/.* 2>/dev/null # catch hidden files like .htaccess

    # 2. Extract the archive directly into the document root
    tar -xzf "$BACKUP_PATH" -C "$DOC_ROOT"

    # 3. Securely hand ownership back to the client
    chown -R "$USERNAME:$USERNAME" "$DOC_ROOT"
    find "$DOC_ROOT" -type d -exec chmod 755 {} \;
    find "$DOC_ROOT" -type f -exec chmod 644 {} \;

    echo "Success: Website $TARGET has been restored to the archived state."
    exit 0

# ==========================================
# ACTION: RESTORE DATABASE
# ==========================================
elif [ "$TYPE" == "Database" ]; then
    BACKUP_PATH="/opt/panel/backups/databases/$FILE_NAME"
    
    if [ ! -f "$BACKUP_PATH" ]; then
        echo "Error: SQL archive not found."
        exit 1
    fi

    # We use zcat to decompress the SQL stream dynamically and pipe it directly into MySQL.
    # This safely overwrites existing tables with the backed-up data.
    zcat "$BACKUP_PATH" | mysql "$TARGET"

    if [ $? -eq 0 ]; then
        echo "Success: Database $TARGET has been restored."
        exit 0
    else
        echo "Error: MySQL rejected the import. The archive may be corrupted."
        exit 1
    fi

else
    echo "Error: Unknown backup type '$TYPE'."
    exit 1
fi