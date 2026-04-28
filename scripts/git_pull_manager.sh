#!/bin/bash
# /opt/panel/scripts/git_pull_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
BRANCH=$(echo "$PAYLOAD" | jq -r '.branch // "main"')
TARGET_DIR="/home/$USERNAME/web/$DOMAIN/public_html"

# 1. Security Validation
if [ ! -d "$TARGET_DIR/.git" ]; then
    echo "Error: No Git repository found in $DOMAIN. Please clone a repository first."
    exit 1
fi

# 2. Execute Git Pull AS THE CLIENT using the enterprise ed25519 key
sudo -u "$USERNAME" bash -c "cd '$TARGET_DIR' && GIT_SSH_COMMAND='ssh -i /home/$USERNAME/.ssh/id_ed25519 -o IdentitiesOnly=yes -o StrictHostKeyChecking=no' git pull origin '$BRANCH'"

if [ $? -eq 0 ]; then
    # ---> NEW: THE JSON EXTRACTION TRICK <---
    # Format: Hash (Tab) Date (Tab) Message. JQ parses it flawlessly.
    COMMITS_JSON=$(sudo -u "$USERNAME" git -C "$TARGET_DIR" log -n 5 --pretty=format:'%h%x09%cd%x09%s' --date=format:'%Y-%m-%d %H:%M' | \
    jq -R -s -c '[split("\n") | .[] | select(length > 0) | split("\t") | {commit: .[0], date: .[1], message: .[2]}]')
    
    # Safely escape single quotes for the MySQL query
    SAFE_JSON="${COMMITS_JSON//\'/\\\'}"
    
    # Update the database
    mysql -e "UPDATE panel_core.domains SET latest_commits = '$SAFE_JSON' WHERE domain_name = '$DOMAIN';"
    # ----------------------------------------

    echo "Success: Git operation completed and commits logged for $DOMAIN!"
    exit 0
else
    echo "Error: Git operation failed."
    exit 1
fi