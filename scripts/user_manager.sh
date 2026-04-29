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
    
    # Check if user already exists in Linux
    if id "$USERNAME" &>/dev/null; then
        echo "Error: Linux user $USERNAME already exists."
        exit 1
    fi

    # Create the user with a home directory and no shell access
    useradd -m -s /bin/bash "$USERNAME"
    echo "$USERNAME:$PASSWORD" | chpasswd

    # Grant Nginx access to the user's group 
    usermod -a -G "$USERNAME" www-data
    systemctl restart nginx

    # Build the panel's directory structure
    mkdir -p /home/$USERNAME/web
    mkdir -p /home/$USERNAME/.ssh

    # Lock down permissions
    chown -R $USERNAME:$USERNAME /home/$USERNAME
    chmod 750 /home/$USERNAME
    chmod 700 /home/$USERNAME/.ssh

    # ---> THE MISSING SOURCE OF TRUTH <---
    # Generate a unique webhook token for Git deployments
    SECRET_TOKEN=$(openssl rand -hex 16)
    
    # Insert the user into the Control Panel Database
    mysql -e "INSERT IGNORE INTO panel_core.users (username, password_hash, email, webhook_token, status) VALUES ('$USERNAME', 'managed_by_os', '$USERNAME@localhost', '$SECRET_TOKEN', 'active');"
    # -------------------------------------

    echo "Success: User $USERNAME created and synced to database."
    exit 0

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
    # Remove the user from the Control Panel Database
    mysql -e "DELETE FROM panel_core.users WHERE username = '$USERNAME';"
    # -------------------------------------

    echo "Success: User $USERNAME cleanly deleted from OS and Database."
    exit 0

else
    echo "Error: Unknown sub_action '$ACTION'."
    exit 1
fi