#!/bin/bash
# /opt/panel/scripts/cron_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1

if [ -z "$PAYLOAD" ]; then
    echo "Error: No JSON payload provided."
    exit 1
fi

ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')
MIN=$(echo "$PAYLOAD" | jq -r '.minute // "*"')
HOUR=$(echo "$PAYLOAD" | jq -r '.hour // "*"')
DAY=$(echo "$PAYLOAD" | jq -r '.day // "*"')
MON=$(echo "$PAYLOAD" | jq -r '.month // "*"')
WEEK=$(echo "$PAYLOAD" | jq -r '.weekday // "*"')
CMD=$(echo "$PAYLOAD" | jq -r '.command')

# Security: Ensure the user actually exists on the Linux system
if ! id "$USERNAME" &>/dev/null; then
    echo "Error: User $USERNAME does not exist."
    exit 1
fi

# Format the exact cron string
CRON_STRING="$MIN $HOUR $DAY $MON $WEEK $CMD"
TMP_CRON="/tmp/cron_$USERNAME"

# Escape single quotes in the command so it doesn't break our MySQL queries later
SAFE_CMD=$(echo "$CMD" | sed "s/'/''/g")

# ==========================================
# ACTION: ADD CRON JOB
# ==========================================
if [ "$ACTION" == "add" ]; then
    
    # 1. Export current crontab (suppressing errors if it's empty)
    crontab -u "$USERNAME" -l 2>/dev/null > "$TMP_CRON"
    
    # 2. Append the new job
    echo "$CRON_STRING" >> "$TMP_CRON"
    
    # 3. Load it back safely
    if crontab -u "$USERNAME" "$TMP_CRON"; then
        rm -f "$TMP_CRON"
        
        # ---> SOURCE OF TRUTH TRACKING <---
        mysql -e "INSERT INTO panel_core.cron_jobs (username, minute, hour, day, month, weekday, command) VALUES ('$USERNAME', '$MIN', '$HOUR', '$DAY', '$MON', '$WEEK', '$SAFE_CMD');"
        
        echo "Success: Cron job added for $USERNAME."
        exit 0
    else
        rm -f "$TMP_CRON"
        echo "Error: Failed to apply crontab for $USERNAME."
        exit 1
    fi

# ==========================================
# ACTION: DELETE CRON JOB
# ==========================================
elif [ "$ACTION" == "delete" ]; then

    # 1. Export current crontab and filter out the exact matching string
    crontab -u "$USERNAME" -l 2>/dev/null | grep -F -v "$CRON_STRING" > "$TMP_CRON"
    
    # 2. Load it back
    if crontab -u "$USERNAME" "$TMP_CRON"; then
        rm -f "$TMP_CRON"
        
        # ---> UI CLEANUP TRACKING <---
        # We use LIMIT 1 just in case the user accidentally added duplicate identical jobs
        mysql -e "DELETE FROM panel_core.cron_jobs WHERE username='$USERNAME' AND command='$SAFE_CMD' AND minute='$MIN' AND hour='$HOUR' AND day='$DAY' LIMIT 1;"
        
        echo "Success: Cron job deleted for $USERNAME."
        exit 0
    else
        rm -f "$TMP_CRON"
        echo "Error: Failed to delete crontab for $USERNAME."
        exit 1
    fi

else
    echo "Error: Unknown action '$ACTION'."
    exit 1
fi