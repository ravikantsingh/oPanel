#!/bin/bash
# /opt/panel/scripts/git_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
REPO_URL=$(echo "$PAYLOAD" | jq -r '.repo_url')
BRANCH=$(echo "$PAYLOAD" | jq -r '.branch // "main"')
TARGET_DIR="/home/$USERNAME/web/$DOMAIN/public_html"

# 1. Security Validation
if [ ! -d "$TARGET_DIR" ]; then
    echo "Error: The target directory for $DOMAIN does not exist."
    exit 1
fi

# 2. Data Protection Check: Ensure we do not auto-delete existing files
# Git requires an empty directory to clone directly into the root folder.
if [ "$(ls -A $TARGET_DIR)" ]; then
    echo "Error: The public_html directory is not empty. Please clear it via the File Manager first to prevent accidental overwrites."
    exit 1
fi

# ---> THE FIX: PERMISSION HANDOFF <---
# We must ensure the user actually owns their web directory before git tries to write the .git folder
chown -R "$USERNAME:$USERNAME" "/home/$USERNAME/web/$DOMAIN"

# 3. Execute the Git Clone AS THE CLIENT 
# We explicitly point it to the ed25519 key and disable host checking
sudo -u "$USERNAME" bash -c "GIT_SSH_COMMAND='ssh -i /home/$USERNAME/.ssh/id_ed25519 -o IdentitiesOnly=yes -o StrictHostKeyChecking=no' git clone -b '$BRANCH' '$REPO_URL' '$TARGET_DIR'"

if [ $? -eq 0 ]; then
    # Format: Hash (Tab) Date (Tab) Message. JQ parses it flawlessly.
    COMMITS_JSON=$(sudo -u "$USERNAME" git -C "$TARGET_DIR" log -n 5 --pretty=format:'%h%x09%cd%x09%s' --date=format:'%Y-%m-%d %H:%M' | \
    jq -R -s -c '[split("\n") | .[] | select(length > 0) | split("\t") | {commit: .[0], date: .[1], message: .[2]}]')
    
    # Safely escape single quotes for the MySQL query
    SAFE_JSON="${COMMITS_JSON//\'/\\\'}"
    
    # ---> THE MISSING SOURCE OF TRUTH FIX <---
    mysql -e "UPDATE panel_core.domains SET git_repo = '$REPO_URL', git_branch = '$BRANCH', latest_commits = '$SAFE_JSON' WHERE domain_name = '$DOMAIN';"
    # ----------------------------------------

    echo "Success: Git operation completed and commits logged for $DOMAIN!"
    exit 0
else
    echo "Error: Git operation failed."
    exit 1
fi