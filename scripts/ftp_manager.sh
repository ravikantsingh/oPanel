#!/bin/bash
# /opt/panel/scripts/ftp_manager.sh

PAYLOAD=$1
ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
SYS_USER=$(echo "$PAYLOAD" | jq -r '.username')
FTP_USER=$(echo "$PAYLOAD" | jq -r '.ftp_user')
FTP_PASS=$(echo "$PAYLOAD" | jq -r '.ftp_pass')

DOC_ROOT="/home/$SYS_USER/web/$DOMAIN/public_html"
PASSWD_FILE="/etc/pure-ftpd/pureftpd.passwd"

# ==========================================
# ACTION: DELETE
# ==========================================
if [ "$ACTION" == "delete" ]; then
    sed -i "/^$FTP_USER:/d" "$PASSWD_FILE" 2>/dev/null
    pure-pw mkdb
    mysql -e "DELETE FROM panel_core.ftp_accounts WHERE ftp_user = '$FTP_USER';"
    echo "Success: FTP User $FTP_USER deleted."
    exit 0
fi

# ==========================================
# ACTION: CREATE & UPDATE
# ==========================================
# Generate a secure SHA-512 Hash using OpenSSL to bypass interactive pure-pw prompts
HASH=$(openssl passwd -6 "$FTP_PASS")
SYS_UID=$(id -u "$SYS_USER")
SYS_GID=$(id -g "$SYS_USER")

if [ "$ACTION" == "create" ]; then
    # 1. Remove existing user if there is a corrupted entry
    sed -i "/^$FTP_USER:/d" "$PASSWD_FILE" 2>/dev/null
    
    # 2. Append the new user manually. The "/./" perfectly jails them to the public_html folder.
    echo "$FTP_USER:$HASH:$SYS_UID:$SYS_GID::$DOC_ROOT/./::::::::::::" >> "$PASSWD_FILE"
    
    pure-pw mkdb
    mysql -e "INSERT IGNORE INTO panel_core.ftp_accounts (domain_name, ftp_user) VALUES ('$DOMAIN', '$FTP_USER');"
    echo "Success: FTP User $FTP_USER created and jailed."
    exit 0

elif [ "$ACTION" == "update" ]; then
    # Check if the user exists in the file
    if grep -q "^$FTP_USER:" "$PASSWD_FILE"; then
        # Safely replace just the password hash field using SED
        sed -i "s|^\($FTP_USER:\)[^:]*\(:.*\)|\1$HASH\2|" "$PASSWD_FILE"
    else
        # Fallback: If previous creation failed, append them now
        echo "$FTP_USER:$HASH:$SYS_UID:$SYS_GID::$DOC_ROOT/./::::::::::::" >> "$PASSWD_FILE"
    fi
    
    pure-pw mkdb
    echo "Success: Password updated for $FTP_USER."
    exit 0
else
    echo "Error: Unknown FTP action."
    exit 1
fi