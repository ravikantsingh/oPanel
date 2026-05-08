#!/bin/bash
# Executed by Python Daemon as root
# Purpose: Initialize a Python Environment (VirtualEnv, PM2, and Nginx Reverse Proxy)

PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')

APP_DIR="/home/$USERNAME/web/$DOMAIN/public_html"
VHOST="/etc/nginx/sites-available/$DOMAIN.conf"

if [ ! -d "$APP_DIR" ]; then
    echo "Error: Application directory not found."
    exit 1
fi

echo "Initializing Python Environment for $DOMAIN..."

# 1. Database Connection & Port Allocation
DB_PASS=$(grep DB_PASS /opt/panel/www/config/database.php | cut -d"'" -f4)
MYSQL_CMD="mysql -B -N -upanel_user -p${DB_PASS} panel_core -e"

# Find the highest used port, default to 8000 if none exist
MAX_PORT=$($MYSQL_CMD "SELECT MAX(app_port) FROM domains;")
if [ "$MAX_PORT" == "NULL" ] || [ -z "$MAX_PORT" ]; then
    APP_PORT=8000
else
    APP_PORT=$((MAX_PORT + 1))
fi

echo "Allocated Internal Port: $APP_PORT"

# 2. Build the Virtual Environment
su - "$USERNAME" -c "cd $APP_DIR && python3 -m venv venv"
su - "$USERNAME" -c "cd $APP_DIR && venv/bin/pip install --upgrade pip"

# 3. Smart Scaffolding & Dependency Installation
if [ -f "$APP_DIR/requirements.txt" ]; then
    echo "Found requirements.txt. Installing dependencies..."
    su - "$USERNAME" -c "cd $APP_DIR && venv/bin/pip install -r requirements.txt gunicorn"
    ENTRYPOINT="venv/bin/gunicorn --workers 3 --bind 127.0.0.1:$APP_PORT app:app"
else
    echo "No requirements.txt found. Scaffolding default Flask App..."
    su - "$USERNAME" -c "cd $APP_DIR && venv/bin/pip install flask gunicorn"
    
cat << 'EOF' > "$APP_DIR/app.py"
from flask import Flask
app = Flask(__name__)

@app.route("/")
def hello():
    return "<div style='font-family: sans-serif; text-align: center; margin-top: 100px;'><h1>[ OK ] Python Engine Active</h1><p>Your Python application is successfully running on oPanel.</p></div>"

if __name__ == "__main__":
    app.run()
EOF
    chown "$USERNAME:$USERNAME" "$APP_DIR/app.py"
    ENTRYPOINT="venv/bin/gunicorn --workers 3 --bind 127.0.0.1:$APP_PORT app:app"
fi

# 4. Generate the PM2 Boot Script
WORKER_NAME="python-$DOMAIN"
cat << EOF > "$APP_DIR/start.sh"
#!/bin/bash
cd $APP_DIR
exec $ENTRYPOINT
EOF
chmod +x "$APP_DIR/start.sh"
chown "$USERNAME:$USERNAME" "$APP_DIR/start.sh"

# 5. Spin up the Background Process via PM2
if su - "$USERNAME" -c "pm2 describe $WORKER_NAME" > /dev/null 2>&1; then
    su - "$USERNAME" -c "pm2 restart $WORKER_NAME"
else
    su - "$USERNAME" -c "pm2 start $APP_DIR/start.sh --name '$WORKER_NAME'"
    su - "$USERNAME" -c "pm2 save"
fi

# 6. Reconfigure Nginx (Convert from FastCGI to Reverse Proxy)
# We safely comment out the PHP block to prevent execution leaks
sed -i 's/location \~ \\.php\$/#location \~ \\.php\$/g' "$VHOST"
sed -i 's/include snippets\/fastcgi-php.conf;/#include snippets\/fastcgi-php.conf;/g' "$VHOST"
sed -i 's/fastcgi_pass unix:/#fastcgi_pass unix:/g' "$VHOST"

# We replace the try_files logic with proxy routing
PROXY_LOGIC="proxy_pass http:\/\/127.0.0.1:$APP_PORT;\n        proxy_set_header Host \$host;\n        proxy_set_header X-Real-IP \$remote_addr;\n        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;"
sed -i "s|try_files \$uri \$uri/ /index.php?\$query_string;|$PROXY_LOGIC|g" "$VHOST"

systemctl reload nginx

# 7. Update Database
$MYSQL_CMD "UPDATE domains SET app_type='python', app_port=$APP_PORT, pm2_process='$WORKER_NAME' WHERE domain_name='$DOMAIN';"

echo "Python Deployment Complete! App is live on Port $APP_PORT."
exit 0