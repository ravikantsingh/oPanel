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
    
    cp "$JAIL_CONF" "$JAIL_CONF.bak"

    # Use awk to safely replace values ONLY inside the [DEFAULT] block
    awk -v bt="$BANTIME" -v ft="$FINDTIME" -v mr="$MAXRETRY" '
        /^\[.*\]/ { in_default = ($0 == "[DEFAULT]") }
        in_default && /^bantime\s*=/ { print "bantime  = " bt; next }
        in_default && /^findtime\s*=/ { print "findtime  = " ft; next }
        in_default && /^maxretry\s*=/ { print "maxretry = " mr; next }
        { print $0 }
    ' "$JAIL_CONF.bak" > "$JAIL_CONF"

    # Test the configuration properly using fail2ban-server
    fail2ban-server -t > /dev/null 2>&1
    
    if [ $? -eq 0 ]; then
        systemctl restart fail2ban
        rm "$JAIL_CONF.bak"
        echo "Success: Fail2ban global settings updated to $BANTIME ban, $FINDTIME find, $MAXRETRY retries."
        exit 0
    else
        # Rollback if syntax is bad
        mv "$JAIL_CONF.bak" "$JAIL_CONF"
        echo "Error: Invalid Fail2ban configuration syntax. Aborting and rolling back."
        exit 1
    fi

else
    echo "Error: Unknown sub_action '$ACTION'."
    exit 1
fi