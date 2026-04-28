#!/bin/bash
# /opt/panel/scripts/dns_record_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
ACTION=$(echo "$PAYLOAD" | jq -r '.action')
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
TYPE=$(echo "$PAYLOAD" | jq -r '.type')
NAME=$(echo "$PAYLOAD" | jq -r '.name')
VALUE=$(echo "$PAYLOAD" | jq -r '.value')

ZONE_FILE="/etc/bind/zones/db.$DOMAIN"

if [ ! -f "$ZONE_FILE" ]; then
    echo "Error: Zone file for $DOMAIN does not exist."
    exit 1
fi

# Function to increment the BIND Serial Number safely (Format: YYYYMMDDNN)
update_serial() {
    TODAY=$(date +%Y%m%d)
    CURRENT_SERIAL=$(grep -oE '[0-9]{10}[[:space:]]*; Serial' "$ZONE_FILE" | awk '{print $1}')
    
    if [[ $CURRENT_SERIAL == $TODAY* ]]; then
        # If edited today, increment the last two digits
        NEW_SERIAL=$((CURRENT_SERIAL + 1))
    else
        # If edited on a new day, reset to TODAY + 01
        NEW_SERIAL="${TODAY}01"
    fi
    
    # Replace the old serial with the new one
    sed -i -E "s/[0-9]{10}([[:space:]]*; Serial)/$NEW_SERIAL\1/" "$ZONE_FILE"
}

# Process the Action
if [ "$ACTION" == "add" ]; then
    # Format the record. Note: TXT records need quotes around the value.
    if [ "$TYPE" == "TXT" ]; then
        RECORD="$NAME IN $TYPE \"$VALUE\""
    else
        RECORD="$NAME IN $TYPE $VALUE"
    fi
    
    echo "$RECORD" >> "$ZONE_FILE"
    echo "Success: Added $TYPE record for $NAME."

elif [ "$ACTION" == "delete" ]; then
    # Use sed to delete the line matching Name, Type, and Value
    if [ "$TYPE" == "TXT" ]; then
        sed -i "/^$NAME[[:space:]]\+IN[[:space:]]\+$TYPE[[:space:]]\+\"$VALUE\"/d" "$ZONE_FILE"
    else
        sed -i "/^$NAME[[:space:]]\+IN[[:space:]]\+$TYPE[[:space:]]\+$VALUE/d" "$ZONE_FILE"
    fi
    echo "Success: Deleted $TYPE record for $NAME."
fi

# Update serial and reload BIND9
update_serial
named-checkconf
if [ $? -eq 0 ]; then
    systemctl reload bind9

    # ---> NEW: SOURCE OF TRUTH TRACKING <---
    if [ "$ACTION" == "add" ]; then
        mysql -e "INSERT IGNORE INTO panel_core.dns_records (domain_name, record_name, record_type, record_value) VALUES ('$DOMAIN', '$NAME', '$TYPE', '$VALUE');"
    elif [ "$ACTION" == "delete" ]; then
        mysql -e "DELETE FROM panel_core.dns_records WHERE domain_name = '$DOMAIN' AND record_name = '$NAME' AND record_type = '$TYPE';"
    fi
    exit 0
else
    echo "Error: BIND9 configuration test failed."
    exit 1
fi