#!/bin/bash
# /opt/panel/scripts/firewall_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1

if [ -z "$PAYLOAD" ]; then
    echo "Error: No JSON payload provided."
    exit 1
fi

ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')
PORT=$(echo "$PAYLOAD" | jq -r '.port')
PROTOCOL=$(echo "$PAYLOAD" | jq -r '.protocol // "tcp"')

# Security validation: Ensure port is a number between 1 and 65535
if ! [[ "$PORT" =~ ^[0-9]+$ ]] || [ "$PORT" -lt 1 ] || [ "$PORT" -gt 65535 ]; then
    echo "Error: Invalid port number."
    exit 1
fi

# ==========================================
# ACTION: ALLOW PORT
# ==========================================
if [ "$ACTION" == "allow" ]; then
    
    ufw allow "$PORT/$PROTOCOL"
    
    if [ $? -eq 0 ]; then
        # ---> UPGRADE: Trigger the Auto-Sync Engine <---
        /opt/panel/scripts/sync_firewall.sh

        echo "Success: Port $PORT/$PROTOCOL has been opened."
        exit 0
    else
        echo "Error: Failed to open port."
        exit 1
    fi

# ==========================================
# ACTION: DELETE RULE (CLOSE PORT)
# ==========================================
elif [ "$ACTION" == "delete" ]; then
    
    ufw delete allow "$PORT/$PROTOCOL"
    
    if [ $? -eq 0 ]; then
        # ---> UPGRADE: Trigger the Auto-Sync Engine <---
        /opt/panel/scripts/sync_firewall.sh
        
        echo "Success: Port $PORT/$PROTOCOL has been closed."
        exit 0
    else
        echo "Error: Failed to close port."
        exit 1
    fi

else
    echo "Error: Unknown sub_action '$ACTION'."
    exit 1
fi