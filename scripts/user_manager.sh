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
PASSWORD=$(echo "$PAYLOAD" | jq -r '.password // empty') # // empty means it's optional for deletion

# 3. Security check: Ensure username is valid (alphanumeric, no spaces or special chars)
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

    # Create the user with a home directory and no shell access by default (for security)
    useradd -m -s /bin/bash "$USERNAME"

    # Set the password
    echo "$USERNAME:$PASSWORD" | chpasswd

    # Build the panel's directory structure inside their home folder
    mkdir -p /home/$USERNAME/web
    mkdir -p /home/$USERNAME/.ssh

    # Lock down permissions (User owns their stuff, others cannot read it)
    chown -R $USERNAME:$USERNAME /home/$USERNAME
    chmod 750 /home/$USERNAME
    chmod 700 /home/$USERNAME/.ssh

    echo "Success: User $USERNAME created and directories provisioned."
    exit 0

# ==========================================
# ACTION: DELETE USER
# ==========================================
elif [ "$ACTION" == "delete" ]; then
    
    # Check if user exists
    if ! id "$USERNAME" &>/dev/null; then
        echo "Error: Linux user $USERNAME does not exist."
        exit 1
    fi

    # Delete the user and forcefully remove their home directory (-r)
    userdel -r "$USERNAME"

    echo "Success: User $USERNAME and all associated files have been deleted."
    exit 0

else
    echo "Error: Unknown sub_action '$ACTION'."
    exit 1
fi