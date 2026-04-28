#!/bin/bash
# /opt/panel/scripts/delete_backup_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
FILE_NAME=$(echo "$PAYLOAD" | jq -r '.file')
TYPE=$(echo "$PAYLOAD" | jq -r '.type')

# Determine the correct secure vault directory based on the type
if [ "$TYPE" == "Website" ]; then
    BACKUP_PATH="/opt/panel/backups/websites/$FILE_NAME"
elif [ "$TYPE" == "Database" ]; then
    BACKUP_PATH="/opt/panel/backups/databases/$FILE_NAME"
else
    echo "Error: Unknown backup type '$TYPE'."
    exit 1
fi

# Ensure the file actually exists before trying to delete it
if [ ! -f "$BACKUP_PATH" ]; then
    echo "Error: Backup archive not found on disk."
    exit 1
fi

# Permanently delete the file
rm -f "$BACKUP_PATH"

if [ $? -eq 0 ]; then
    echo "Success: Backup archive '$FILE_NAME' has been securely deleted."
    exit 0
else
    echo "Error: Failed to delete the backup archive."
    exit 1
fi