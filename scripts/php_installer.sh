#!/bin/bash
# /opt/panel/scripts/php_installer.sh
# Executed by Python Daemon as root

PAYLOAD=$1
ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')
VERSION=$(echo "$PAYLOAD" | jq -r '.version')

if [[ ! "$VERSION" =~ ^[78]\.[0-9]$ ]]; then
    echo "Error: Invalid PHP version format."
    exit 1
fi

# ---> THE ULTIMATE HEADLESS OVERRIDES <---
export DEBIAN_FRONTEND=noninteractive
export NEEDRESTART_MODE=a
export NEEDRESTART_SUSPEND=1
export UCF_FORCE_CONFOLD=1

# Use a temporary file to completely detach the OS text pipe from Python
LOG_FILE="/tmp/php_mgr_v${VERSION}.log"
> "$LOG_FILE"

# ==========================================
# ACTION: INSTALL PHP
# ==========================================
if [ "$ACTION" == "install" ]; then
    echo "Initiating unattended installation of PHP $VERSION..." > "$LOG_FILE"

    # Force the PPA to register before updating the cache
    add-apt-repository ppa:ondrej/php -y >> "$LOG_FILE" 2>&1
    
    # Run apt-get completely detached, logging to the file
    apt-get update -qq >> "$LOG_FILE" 2>&1
    apt-get install -y -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold" php$VERSION-fpm php$VERSION-mysql php$VERSION-cli php$VERSION-curl php$VERSION-mbstring php$VERSION-xml php$VERSION-zip >> "$LOG_FILE" 2>&1
    
    EXIT_CODE=$?
    
    systemctl enable php$VERSION-fpm >> "$LOG_FILE" 2>&1
    systemctl start php$VERSION-fpm >> "$LOG_FILE" 2>&1
    
    # Output the entire log to Python at the very end, then delete the file
    cat "$LOG_FILE"
    rm -f "$LOG_FILE"
    
    if [ $EXIT_CODE -eq 0 ]; then
        echo "Success: PHP $VERSION has been successfully installed and activated."
        exit 0
    else
        echo "Error: Package manager failed. Review the log above."
        exit 1
    fi

# ==========================================
# ACTION: REMOVE PHP
# ==========================================
elif [ "$ACTION" == "remove" ]; then
    IN_USE=$(mysql -N -s -e "SELECT COUNT(*) FROM panel_core.domains WHERE php_version='$VERSION';")
    if [ "$IN_USE" -gt 0 ]; then
        echo "Error: Cannot remove PHP $VERSION because $IN_USE domain(s) are using it!"
        exit 1
    fi

    echo "Purging PHP $VERSION..." > "$LOG_FILE"
    
    apt-get purge -y -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold" php$VERSION-fpm php$VERSION-mysql php$VERSION-cli php$VERSION-curl php$VERSION-mbstring php$VERSION-xml php$VERSION-zip >> "$LOG_FILE" 2>&1
    apt-get autoremove -y >> "$LOG_FILE" 2>&1
    
    cat "$LOG_FILE"
    rm -f "$LOG_FILE"
    
    echo "Success: PHP $VERSION has been completely removed from the server."
    exit 0
fi