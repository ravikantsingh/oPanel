#!/bin/bash
# /opt/panel/scripts/user_manager.sh
# Executed by Python Daemon as root

# 1. Read the JSON payload passed as the first argument
PAYLOAD=$1

if [ -z "$PAYLOAD" ]; then
    echo "Error: No JSON payload provided."
    exit 1
fi

# 2. Extract data using jq
ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')
PASSWORD=$(echo "$PAYLOAD" | jq -r '.password // empty')

# 3. Security check: Ensure username is valid
if [[ ! "$USERNAME" =~ ^[a-z0-9]+$ ]]; then
    echo "Error: Invalid username format. Use only lowercase letters and numbers."
    exit 1
fi

# ==========================================
# ACTION: CREATE USER
# ==========================================
if [ "$ACTION" == "create" ]; then
    
    # 1. OS-Level Safety Check
    if id "$USERNAME" &>/dev/null || getent group "$USERNAME" &>/dev/null; then
        echo "Error: The username or group '$USERNAME' is already reserved by Linux."
        exit 1
    fi

    # 2. Safely Create User
    if ! useradd -m -s /bin/bash "$USERNAME"; then
        echo "Error: Linux rejected the useradd command."
        exit 1
    fi

    # 3. Safely Set Password
    if ! echo "$USERNAME:$PASSWORD" | chpasswd; then
        echo "Error: Password policy rejected. Rolling back..."
        userdel -r "$USERNAME"
        exit 1
    fi

    # 4. Safely configure web directories
    if ! usermod -a -G "$USERNAME" www-data; then
        echo "Warning: Could not add www-data to user group."
    fi
    
    systemctl restart nginx

    mkdir -p /home/$USERNAME/web
    mkdir -p /home/$USERNAME/.ssh

    # Lock down permissions
    chown -R $USERNAME:$USERNAME /home/$USERNAME
    chmod 750 /home/$USERNAME
    chmod 700 /home/$USERNAME/.ssh

    # ---> THE SOURCE OF TRUTH GATEWAY <---
    SECRET_TOKEN=$(openssl rand -hex 16)
    
    # Only if ALL the above OS commands succeeded, we write to the database!
    if mysql -e "INSERT IGNORE INTO panel_core.users (username, password_hash, email, webhook_token, status) VALUES ('$USERNAME', 'managed_by_os', '$USERNAME@localhost', '$SECRET_TOKEN', 'active');"; then
        echo "Success: User $USERNAME created and fully synced to the database."
        exit 0
    else
        echo "Critical Error: OS user created, but Database sync failed! Rolling back OS user..."
        userdel -r "$USERNAME"
        exit 1
    fi

# ==========================================
# ACTION: DELETE USER
# ==========================================
elif [ "$ACTION" == "delete" ]; then
    
    if ! id "$USERNAME" &>/dev/null; then
        echo "Error: Linux user $USERNAME does not exist."
        exit 1
    fi

    # Delete the Linux user and their files
    userdel -r "$USERNAME"

    # ---> THE MISSING SOURCE OF TRUTH <---
    # Remove the user from the oPanel Database
    mysql -e "DELETE FROM panel_core.users WHERE username = '$USERNAME';"
    # -------------------------------------

    echo "Success: User $USERNAME cleanly deleted from OS and Database."
    exit 0

else
    echo "Error: Unknown sub_action '$ACTION'."
    exit 1
fi