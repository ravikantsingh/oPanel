#!/bin/bash
# /opt/panel/scripts/node_action.sh

PAYLOAD=$1
ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')
APP_ROOT=$(echo "$PAYLOAD" | jq -r '.app_root // "app"')

APP_DIR="/home/$USERNAME/web/$DOMAIN/$APP_ROOT"

# Bulletproof PM2 execution function
run_pm2() {
    sudo -H -u "$USERNAME" bash -c "export PM2_HOME=/home/$USERNAME/.pm2 && pm2 $1"
}

if [ "$ACTION" == "restart" ]; then
    run_pm2 "restart $DOMAIN"
    run_pm2 "save"
    echo "Success: PM2 gracefully restarted the application for $DOMAIN."
    exit 0

elif [ "$ACTION" == "stop" ]; then
    run_pm2 "stop $DOMAIN"
    run_pm2 "save"
    echo "Success: PM2 stopped the application for $DOMAIN."
    exit 0

elif [ "$ACTION" == "npm_install" ]; then
    if [ ! -d "$APP_DIR" ]; then
        echo "Error: Application directory does not exist yet."
        exit 1
    fi
    
    echo "Running npm install in $APP_DIR..."
    sudo -H -u "$USERNAME" bash -c "cd $APP_DIR && npm install"
    
    run_pm2 "restart $DOMAIN"
    run_pm2 "save"
    echo "Success: NPM dependencies installed and app restarted for $DOMAIN."
    exit 0

else
    echo "Error: Unknown PM2 action '$ACTION'."
    exit 1
fi