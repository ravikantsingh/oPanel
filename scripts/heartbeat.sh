#!/bin/bash
# /opt/panel/scripts/heartbeat.sh
# Encrypted Heartbeat Transmitter

KEY_FILE="/opt/panel/license.key"
STATUS_FILE="/opt/panel/www/config/license_status.json"
PUBLIC_KEY="/opt/panel/config/public_key.pem"
API_ENDPOINT="https://stackrium.com/api/heartbeat.php"

if [ ! -f "$KEY_FILE" ]; then
    echo "Error: license.key missing."
    exit 1
fi

if [ ! -f "$PUBLIC_KEY" ]; then
    echo "Error: public_key.pem missing. Cannot encrypt telemetry."
    exit 1
fi

LICENSE_KEY=$(grep -oE 'STRM-[A-Za-z0-9]+' "$KEY_FILE")
PANEL_VERSION=$(grep -oP "(?<=PANEL_VERSION', ')[^']+" /opt/panel/www/version.php 2>/dev/null || echo "1.0.0")

# Telemetry gathering
DOMAINS=$(ls -1 /etc/nginx/sites-enabled/ 2>/dev/null | grep -v default | wc -l)
USERS=$(ls -1 /home/ 2>/dev/null | wc -l)
OS_VERSION=$(lsb_release -d -s | tr -d '"' 2>/dev/null || echo "Unknown Linux")

DB_PASS=$(grep "'DB_PASS'" /opt/panel/www/config/database.php | cut -d"'" -f4 2>/dev/null)
if [ -n "$DB_PASS" ]; then
    DBS=$(mysql -u panel_user -p"$DB_PASS" -e "SELECT COUNT(*) FROM panel_core.databases;" -sN 2>/dev/null || echo 0)
else
    DBS=$(mysql -N -s -e "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE schema_name NOT IN ('information_schema', 'mysql', 'performance_schema', 'sys', 'panel_core');" 2>/dev/null || echo 0)
fi

# 1. Compile data into a JSON string
JSON_PAYLOAD="{\"license_key\":\"$LICENSE_KEY\",\"domains\":$DOMAINS,\"dbs\":$DBS,\"users\":$USERS,\"os\":\"$OS_VERSION\",\"panel_version\":\"$PANEL_VERSION\"}"

# 2. Encrypt the JSON string using the Public Key and encode it in Base64 for safe HTTP transit
ENCRYPTED_PAYLOAD=$(echo -n "$JSON_PAYLOAD" | openssl pkeyutl -encrypt -pubin -inkey "$PUBLIC_KEY" | base64 -w 0)

# 3. Send ONLY the encrypted block to the Central API
RESPONSE=$(curl -s -X POST "$API_ENDPOINT" --data-urlencode "payload=$ENCRYPTED_PAYLOAD" --max-time 15)

# Parse response...
STATUS="active"
EXPIRY="Unknown"
IP=$(curl -s ifconfig.me)

if [ -n "$RESPONSE" ]; then
    EXTRACTED_STATUS=$(echo "$RESPONSE" | jq -r '.status // empty' 2>/dev/null)
    EXTRACTED_EXPIRY=$(echo "$RESPONSE" | jq -r '.expiry // empty' 2>/dev/null)
    EXTRACTED_IP=$(echo "$RESPONSE" | jq -r '.authorized_ip // empty' 2>/dev/null)
    
    if [ -n "$EXTRACTED_STATUS" ] && [ "$EXTRACTED_STATUS" != "null" ]; then STATUS="$EXTRACTED_STATUS"; fi
    if [ -n "$EXTRACTED_EXPIRY" ] && [ "$EXTRACTED_EXPIRY" != "null" ]; then EXPIRY="$EXTRACTED_EXPIRY"; fi
    if [ -n "$EXTRACTED_IP" ] && [ "$EXTRACTED_IP" != "null" ]; then IP="$EXTRACTED_IP"; fi
fi

# Save Local Status
mkdir -p /opt/panel/www/config
CURRENT_TIME=$(date +%s)
echo "{\"status\": \"$STATUS\", \"expiry\": \"$EXPIRY\", \"authorized_ip\": \"$IP\", \"last_checked\": $CURRENT_TIME}" > "$STATUS_FILE"
chown root:www-data "$STATUS_FILE"
chmod 640 "$STATUS_FILE"

echo "API Response: $RESPONSE"