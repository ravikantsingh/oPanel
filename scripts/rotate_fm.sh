#!/bin/bash
# /opt/panel/scripts/rotate_fm.sh

PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')
NEW_PASS=$(echo "$PAYLOAD" | jq -r '.new_password')

FM_DIR="/home/$USERNAME/web/$DOMAIN/filemanager"
DOC_ROOT="/home/$USERNAME/web/$DOMAIN/public_html"

if [ ! -d "$FM_DIR" ]; then
    echo "Error: File manager not deployed for $DOMAIN."
    exit 1
fi

# Generate the new hash
HASH=$(php -r "echo password_hash('$NEW_PASS', PASSWORD_DEFAULT);")

# Safely overwrite the config
cat <<EOF > "$FM_DIR/config.php"
<?php
\$auth_users = array('$USERNAME' => '$HASH');
\$readonly_users = array();
\$use_auth = true;
\$theme = 'dark';
\$root_path = '$DOC_ROOT';
\$root_url = '';
\$is_https = true;
?>
EOF

# Ensure permissions stay locked down
chown $USERNAME:$USERNAME "$FM_DIR/config.php"

echo "Success: File Manager password rotated for $DOMAIN."
exit 0