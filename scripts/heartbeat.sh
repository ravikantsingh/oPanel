#!/bin/bash
# /opt/panel/scripts/heartbeat.sh
# Runs daily via root Cron Job to sync with stackrium.com

KEY_FILE="/opt/panel/license.key"
STATUS_FILE="/opt/panel/www/config/license_status.json"
API_ENDPOINT="https://stackrium.com/api/heartbeat.php"
PANEL_VERSION="1.0.0"

# 1. Verify License File Exists
if [ ! -f "$KEY_FILE" ]; then
    echo "Error: license.key missing."
    exit 1
fi

# Clean the key (remove any accidental whitespace/newlines)
LICENSE_KEY=$(cat "$KEY_FILE" | tr -d '[:space:]')

# 2. Gather Telemetry Data natively
DOMAINS=$(ls -1 /etc/nginx/sites-enabled/ 2>/dev/null | grep -v default | wc -l)
USERS=$(ls -1 /home/ 2>/dev/null | wc -l)
OS_VERSION=$(lsb_release -d -s | tr -d '"')

# Query MariaDB safely (returns 0 if the query fails)
DBS=$(mysql -N -s -e "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE schema_name NOT IN ('information_schema', 'mysql', 'performance_schema', 'sys', 'panel_core');" 2>/dev/null || echo 0)

# 3. Send the Heartbeat to Stackrium Central via cURL
RESPONSE=$(curl -s -X POST "$API_ENDPOINT" \
    -d "license_key=$LICENSE_KEY" \
    -d "domains=$DOMAINS" \
    -d "dbs=$DBS" \
    -d "users=$USERS" \
    -d "os=$OS_VERSION" \
    -d "panel_version=$PANEL_VERSION" \
    --max-time 10)

# 4. Process the Response using jq
STATUS="active" # Default to active to grant a grace period if the network is down

if [ -n "$RESPONSE" ]; then
    # Extract the status safely, fallback to 'active' if JSON is malformed
    EXTRACTED_STATUS=$(echo "$RESPONSE" | jq -r '.status // "active"')
    
    # Ensure jq actually returned a valid string, not just an empty variable
    if [ -n "$EXTRACTED_STATUS" ] && [ "$EXTRACTED_STATUS" != "null" ]; then
        STATUS="$EXTRACTED_STATUS"
    fi
fi

# 5. Save the state securely for the PHP Gatekeeper to read
mkdir -p /opt/panel/www/config
CURRENT_TIME=$(date +%s)

echo "{\"status\": \"$STATUS\", \"last_checked\": $CURRENT_TIME}" > "$STATUS_FILE"

# 6. Fix permissions so www-data (PHP) can read it, but only root can write it
chown root:www-data "$STATUS_FILE"
chmod 640 "$STATUS_FILE"

echo "Heartbeat completed. Status: $STATUS"