#!/bin/bash
# /opt/panel/scripts/fail2ban_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')
JAIL_CONF="/etc/fail2ban/jail.local"

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

# ==========================================
# ACTION: UPDATE GLOBAL SETTINGS
# ==========================================
elif [ "$ACTION" == "update_settings" ]; then
    BANTIME=$(echo "$PAYLOAD" | jq -r '.bantime')
    FINDTIME=$(echo "$PAYLOAD" | jq -r '.findtime')
    MAXRETRY=$(echo "$PAYLOAD" | jq -r '.maxretry')

    if [ ! -f "$JAIL_CONF" ]; then
        echo "Error: Configuration file $JAIL_CONF not found."
        exit 1
    fi

    # Use sed to safely replace the existing values in the [DEFAULT] block
    sed -i -E "s/^bantime\s*=.*/bantime  = $BANTIME/" "$JAIL_CONF"
    sed -i -E "s/^findtime\s*=.*/findtime  = $FINDTIME/" "$JAIL_CONF"
    sed -i -E "s/^maxretry\s*=.*/maxretry = $MAXRETRY/" "$JAIL_CONF"

    # Test the configuration before restarting
    fail2ban-client -t > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        systemctl restart fail2ban
        echo "Success: Fail2ban global settings updated to $BANTIME ban, $FINDTIME find, $MAXRETRY retries."
        exit 0
    else
        echo "Error: Invalid Fail2ban configuration syntax. Aborting restart to prevent failure."
        exit 1
    fi

else
    echo "Error: Unknown sub_action '$ACTION'."
    exit 1
fi