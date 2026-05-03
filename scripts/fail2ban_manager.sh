#!/bin/bash
# /opt/panel/scripts/fail2ban_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')

# ==========================================
# ACTION: UNBAN IP
# ==========================================
if [ "$ACTION" == "unban" ]; then
    IP=$(echo "$PAYLOAD" | jq -r '.ip')
    JAIL=$(echo "$PAYLOAD" | jq -r '.jail')

    if [ -z "$IP" ] || [ -z "$JAIL" ]; then
        echo "Error: IP and Jail are required to unban."
        exit 1
    fi

    # Unban using fail2ban-client
    fail2ban-client set "$JAIL" unbanip "$IP"

    if [ $? -eq 0 ]; then
        echo "Success: IP $IP has been unbanned from $JAIL."
        exit 0
    else
        echo "Error: Failed to unban IP $IP from $JAIL."
        exit 1
    fi
else
    echo "Error: Unknown sub_action '$ACTION'."
    exit 1
fi