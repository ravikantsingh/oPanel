#!/bin/bash
# /opt/panel/scripts/ssh_key_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')

SSH_DIR="/home/$USERNAME/.ssh"
KEY_FILE="$SSH_DIR/id_ed25519"

# 1. Create the .ssh directory safely
sudo -u "$USERNAME" mkdir -p "$SSH_DIR"
sudo -u "$USERNAME" chmod 700 "$SSH_DIR"

# 2. Generate the keypair ONLY if it doesn't already exist
if [ ! -f "$KEY_FILE" ]; then
    # Generate an ed25519 key with no password (-N "") silently (-q)
    sudo -u "$USERNAME" ssh-keygen -t ed25519 -C "$USERNAME@panel" -f "$KEY_FILE" -N "" -q
fi

# 3. Read the public key
PUB_KEY=$(cat "${KEY_FILE}.pub")

# 4. Save it to the database for the UI to read
mysql -e "UPDATE panel_core.users SET ssh_pub_key = '$PUB_KEY' WHERE username = '$USERNAME';"

echo "Success: SSH Deploy Key generated and saved for $USERNAME."
exit 0