#!/bin/bash
# /opt/panel/scripts/sync_firewall.sh
# Forces the database to perfectly mirror the real Linux UFW rules

# 1. Clear the old, out-of-sync database table
mysql -e "TRUNCATE TABLE panel_core.firewall_rules;"

# 2. Parse the real UFW status and insert the actual rules
ufw status | grep ALLOW | grep -v "(v6)" | awk '{print $1}' | while read -r rule; do
    
    # Split the rule (e.g., "80/tcp") into port and protocol
    PORT=$(echo "$rule" | cut -d'/' -f1)
    PROTO=$(echo "$rule" | cut -d'/' -f2)

    # If no protocol is specified by UFW, default it to tcp for the UI
    if [ "$PORT" == "$PROTO" ]; then
        PROTO="tcp"
    fi

    # Security check: Only sync standard single ports (ignores ranges like 40000:50000 to protect FTP)
    if [[ "$PORT" =~ ^[0-9]+$ ]]; then
        mysql -e "INSERT IGNORE INTO panel_core.firewall_rules (port, protocol) VALUES ($PORT, '$PROTO');"
    fi
done