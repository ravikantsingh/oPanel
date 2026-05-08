#!/bin/bash
# /opt/panel/scripts/heartbeat.sh

KEY_FILE="/opt/panel/license.key"
STATUS_FILE="/opt/panel/www/config/license_status.json"
API_ENDPOINT="https://stackrium.com/api/heartbeat.php"
PANEL_VERSION="1.0.0"

if [ ! -f "$KEY_FILE" ]; then
    echo "Error: license.key missing."
    exit 1
fi

# 1. Vigorously strip hidden characters natively on the VPS
LICENSE_KEY=$(grep -oE 'STRM-[A-Za-z0-9]+' "$KEY_FILE")

DOMAINS=$(ls -1 /etc/nginx/sites-enabled/ 2>/dev/null | grep -v default | wc -l)
USERS=$(ls -1 /home/ 2>/dev/null | wc -l)
OS_VERSION=$(lsb_release -d -s | tr -d '"')
DBS=$(mysql -N -s -e "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE schema_name NOT IN ('information_schema', 'mysql', 'performance_schema', 'sys', 'panel_core');" 2>/dev/null || echo 0)

# 2. Use --data-urlencode for OS, and -L to follow potential Nginx redirects safely
RESPONSE=$(curl -s -L -X POST "$API_ENDPOINT" -d "license_key=$LICENSE_KEY" -d "domains=$DOMAINS" -d "dbs=$DBS" -d "users=$USERS" --data-urlencode "os=$OS_VERSION" -d "panel_version=$PANEL_VERSION" --max-time 15)

STATUS="active"
EXPIRY="Unknown"
IP=$(curl -s ifconfig.me)

# 3. Parse the Response safely
if [ -n "$RESPONSE" ]; then
    EXTRACTED_STATUS=$(echo "$RESPONSE" | jq -r '.status // empty' 2>/dev/null)
    EXTRACTED_EXPIRY=$(echo "$RESPONSE" | jq -r '.expiry // empty' 2>/dev/null)
    EXTRACTED_IP=$(echo "$RESPONSE" | jq -r '.authorized_ip // empty' 2>/dev/null)
    
    if [ -n "$EXTRACTED_STATUS" ] && [ "$EXTRACTED_STATUS" != "null" ]; then
        STATUS="$EXTRACTED_STATUS"
    fi
    if [ -n "$EXTRACTED_EXPIRY" ] && [ "$EXTRACTED_EXPIRY" != "null" ]; then
        EXPIRY="$EXTRACTED_EXPIRY"
    fi
    if [ -n "$EXTRACTED_IP" ] && [ "$EXTRACTED_IP" != "null" ]; then
        IP="$EXTRACTED_IP"
    fi
fi

# 4. Save to JSON for the panel UI
mkdir -p /opt/panel/www/config
CURRENT_TIME=$(date +%s)

echo "{\"status\": \"$STATUS\", \"expiry\": \"$EXPIRY\", \"authorized_ip\": \"$IP\", \"last_checked\": $CURRENT_TIME}" > "$STATUS_FILE"

chown root:www-data "$STATUS_FILE"
chmod 640 "$STATUS_FILE"