#!/bin/bash
# /opt/panel/scripts/mail_user_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
ACTION=$(echo "$PAYLOAD" | jq -r '.action')
EMAIL=$(echo "$PAYLOAD" | jq -r '.email')
PASSWORD=$(echo "$PAYLOAD" | jq -r '.password // empty')

# Extract the domain and the local user part
DOMAIN=$(echo "$EMAIL" | awk -F'@' '{print $2}')
USER_PART=$(echo "$EMAIL" | awk -F'@' '{print $1}')

if [ -z "$DOMAIN" ] || [ -z "$USER_PART" ]; then
    echo "Error: Invalid email format."
    exit 1
fi

# 1. Safety Check: Ensure the domain is authorized to receive mail
mysql panel_core -e "INSERT IGNORE INTO mail_domains (name) VALUES ('$DOMAIN');"

# 2. Process the Action
if [ "$ACTION" == "add" ]; then
    if [ -z "$PASSWORD" ]; then echo "Error: Password required."; exit 1; fi
    
    # Generate the highly secure Dovecot hash
    HASH=$(doveadm pw -s SHA512-CRYPT -p "$PASSWORD")
    
    # Inject into MariaDB (Updates the password if the user somehow already exists)
    mysql panel_core -e "INSERT INTO mail_users (email, domain, password) VALUES ('$EMAIL', '$DOMAIN', '$HASH') ON DUPLICATE KEY UPDATE password='$HASH';"
    echo "Success: Email account $EMAIL created successfully."

elif [ "$ACTION" == "delete" ]; then
    # Remove the user from the database
    mysql panel_core -e "DELETE FROM mail_users WHERE email='$EMAIL';"
    
    # SRE Disk Cleanup: Delete the physical mailbox files to instantly free up disk space
    rm -rf "/var/vmail/$DOMAIN/$USER_PART"
    echo "Success: Email account $EMAIL and physical files deleted."

elif [ "$ACTION" == "passwd" ]; then
    if [ -z "$PASSWORD" ]; then echo "Error: Password required."; exit 1; fi
    
    HASH=$(doveadm pw -s SHA512-CRYPT -p "$PASSWORD")
    mysql panel_core -e "UPDATE mail_users SET password='$HASH' WHERE email='$EMAIL';"
    echo "Success: Password updated for $EMAIL."

else
    echo "Error: Unknown action '$ACTION'."
    exit 1
fi