#!/bin/bash
# Executed by Python Daemon as root
# Purpose: Master Compiler for Redirects, MIME Types, and Hotlink Protection

PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
VHOST="/etc/nginx/sites-available/$DOMAIN.conf"

if [ ! -f "$VHOST" ]; then
    echo "Error: VHOST for $DOMAIN not found."
    exit 1
fi

echo "Compiling Advanced Web Settings for $DOMAIN..."

# Safely extract DB password
DB_PASS=$(grep DB_PASS /opt/panel/www/config/database.php | cut -d"'" -f4)
MYSQL_CMD="mysql -B -N -upanel_user -p${DB_PASS} panel_core -e"

# 1. Compile Redirects
REDIRECT_FILE="/etc/nginx/opanel/redirects/$DOMAIN.conf"
> "$REDIRECT_FILE" # Clear existing
$MYSQL_CMD "SELECT source_path, target_url, redirect_type FROM domain_redirects WHERE domain_name='$DOMAIN';" | while read -r source target type; do
    typeStr="permanent"
    if [ "$type" == "302" ]; then typeStr="redirect"; fi
    # We use regex matching to handle exact paths and subdirectories
    echo "rewrite ^${source}/?\$ ${target} ${typeStr};" >> "$REDIRECT_FILE"
done

# 2. Compile Custom MIME Types
MIME_FILE="/etc/nginx/opanel/mimes/$DOMAIN.conf"
echo "types {" > "$MIME_FILE"
$MYSQL_CMD "SELECT mime_type, extension FROM domain_mimes WHERE domain_name='$DOMAIN';" | while read -r mime ext; do
    echo "    $mime $ext;" >> "$MIME_FILE"
done
echo "}" >> "$MIME_FILE"

# 3. Compile Hotlink Protection
HOTLINK_FILE="/etc/nginx/opanel/hotlink/$DOMAIN.conf"
HOTLINK_STATUS=$($MYSQL_CMD "SELECT hotlink_protection FROM domains WHERE domain_name='$DOMAIN';")

if [ "$HOTLINK_STATUS" == "1" ]; then
cat << 'EOF' > "$HOTLINK_FILE"
location ~ \.(gif|png|jpe?g|svg|mp4|webm)$ {
    valid_referers none blocked server_names;
    if ($invalid_referer) {
        return 403;
    }
}
EOF
else
    > "$HOTLINK_FILE" # Leave empty to disable
fi

# 4. Inject Includes into Main VHOST (If missing)
if ! grep -q "opanel/redirects" "$VHOST"; then
    echo "Injecting Include Hooks into Nginx config..."
    # Inject directly below the server_name declaration
    sed -i '/server_name/a \    include /etc/nginx/opanel/redirects/'$DOMAIN'.conf;\n    include /etc/nginx/opanel/mimes/'$DOMAIN'.conf;\n    include /etc/nginx/opanel/hotlink/'$DOMAIN'.conf;' "$VHOST"
fi

echo "Configuration compiled successfully. Reloading Nginx."
systemctl reload nginx
exit 0