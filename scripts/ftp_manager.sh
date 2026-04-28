#!/bin/bash
# /opt/panel/scripts/ftp_manager.sh

PAYLOAD=$1
ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
SYS_USER=$(echo "$PAYLOAD" | jq -r '.username')
FTP_USER=$(echo "$PAYLOAD" | jq -r '.ftp_user')
FTP_PASS=$(echo "$PAYLOAD" | jq -r '.ftp_pass')

DOC_ROOT="/home/$SYS_USER/web/$DOMAIN/public_html"

if [ "$ACTION" == "create" ]; then
    # Create the virtual user, map to system user (-u / -g), and jail to DOC_ROOT (-d)
    (echo "$FTP_PASS"; echo "$FTP_PASS") | pure-pw useradd "$FTP_USER" -u "$SYS_USER" -g "$SYS_USER" -d "$DOC_ROOT"
    pure-pw mkdb
    mysql -e "INSERT IGNORE INTO panel_core.ftp_accounts (domain_name, ftp_user) VALUES ('$DOMAIN', '$FTP_USER');"
    echo "Success: FTP User $FTP_USER created."
    exit 0

elif [ "$ACTION" == "update" ]; then
    # Change password
    (echo "$FTP_PASS"; echo "$FTP_PASS") | pure-pw passwd "$FTP_USER"
    pure-pw mkdb
    echo "Success: Password updated for $FTP_USER."
    exit 0

elif [ "$ACTION" == "delete" ]; then
    pure-pw userdel "$FTP_USER"
    pure-pw mkdb
    mysql -e "DELETE FROM panel_core.ftp_accounts WHERE ftp_user = '$FTP_USER';"
    echo "Success: FTP User $FTP_USER deleted."
    exit 0
else
    echo "Error: Unknown FTP action."
    exit 1
fi