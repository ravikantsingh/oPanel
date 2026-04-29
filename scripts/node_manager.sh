#!/bin/bash
# /opt/panel/scripts/node_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')
APP_ROOT=$(echo "$PAYLOAD" | jq -r '.app_root')
STARTUP_FILE=$(echo "$PAYLOAD" | jq -r '.startup_file')
APP_PORT=$(echo "$PAYLOAD" | jq -r '.app_port')
APP_MODE=$(echo "$PAYLOAD" | jq -r '.app_mode')
ENV_B64=$(echo "$PAYLOAD" | jq -r '.env_vars')

# Decode the environment variables
ENV_VARS=$(echo "$ENV_B64" | base64 --decode)
APP_DIR="/home/$USERNAME/web/$DOMAIN/$APP_ROOT"
VHOST_CONF="/etc/nginx/sites-available/$DOMAIN.conf"

# 1. Directory & Security Checks
if [ ! -f "$VHOST_CONF" ]; then
    echo "Error: Nginx configuration for $DOMAIN not found."
    exit 1
fi

mkdir -p "$APP_DIR"

# 2. Auto-Generate a fallback app if the startup file is missing
if [ ! -f "$APP_DIR/$STARTUP_FILE" ]; then
    cat <<EOF > "$APP_DIR/$STARTUP_FILE"
const http = require('http');
const port = process.env.PORT || $APP_PORT;
const server = http.createServer((req, res) => {
  res.statusCode = 200;
  res.setHeader('Content-Type', 'text/plain');
  res.end('oPanel Node.js Engine: Successfully Deployed! Replace this file with your actual app.\\n');
});
server.listen(port, () => {
  console.log(\`Server running at port \${port}\`);
});
EOF
    chown $USERNAME:$USERNAME "$APP_DIR/$STARTUP_FILE"
fi

# 3. Provision Environment Variables
echo "$ENV_VARS" > "$APP_DIR/.env"
echo "PORT=$APP_PORT" >> "$APP_DIR/.env"
echo "NODE_ENV=$APP_MODE" >> "$APP_DIR/.env"
chown -R $USERNAME:$USERNAME "$APP_DIR"

# 4. Boot PM2 as the Client User (Strict Process Isolation)
sudo -H -u "$USERNAME" bash -c "export PM2_HOME=/home/$USERNAME/.pm2 && pm2 start $APP_DIR/$STARTUP_FILE --name $DOMAIN --cwd $APP_DIR"
sudo -H -u "$USERNAME" bash -c "export PM2_HOME=/home/$USERNAME/.pm2 && pm2 save"

# 5. Surgically Reconfigure Nginx as a Reverse Proxy

# Create the proxy block in a temporary file
cat <<EOF > /tmp/proxy_block.conf
    location / {
        proxy_pass http://127.0.0.1:$APP_PORT;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_cache_bypass \$http_upgrade;
    }
EOF

# Remove the standard PHP try_files location block
sed -i '/location \/ {/,/}/d' "$VHOST_CONF"

# Inject the new proxy block right after the error_log line
sed -i '/error_log/r /tmp/proxy_block.conf' "$VHOST_CONF"

# Disable the PHP processing block to secure the app
sed -i 's/location \~ \\.php\$/location ~ \\.php.disabled/' "$VHOST_CONF"

# Clean up
rm -f /tmp/proxy_block.conf

# 6. Source of Truth: Track the App in the Database
mysql -e "CREATE TABLE IF NOT EXISTS panel_core.node_apps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain_name VARCHAR(255) UNIQUE,
    username VARCHAR(255),
    app_root VARCHAR(255),
    app_port INT,
    status VARCHAR(50) DEFAULT 'active'
);"
mysql -e "REPLACE INTO panel_core.node_apps (domain_name, username, app_root, app_port) VALUES ('$DOMAIN', '$USERNAME', '$APP_ROOT', $APP_PORT);"

# 7. Reload & Finalize
if nginx -t > /dev/null 2>&1; then
    systemctl reload nginx
    echo "Success: Node.js App deployed on PM2 and Nginx proxy routed to port $APP_PORT!"
    exit 0
else
    echo "Error: Nginx configuration failed after proxy injection."
    exit 1
fi