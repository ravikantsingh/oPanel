#!/bin/bash
# /opt/panel/scripts/service_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')
SERVICE=$(echo "$PAYLOAD" | jq -r '.service')

# 1. The Master Whitelist
VALID_SERVICES=("nginx" "mariadb" "php8.3-fpm" "pure-ftpd" "bind9" "fail2ban" "postfix" "dovecot" "panel-daemon" "redis-server")

if [[ ! " ${VALID_SERVICES[@]} " =~ " ${SERVICE} " ]]; then
    echo "Error: Service '$SERVICE' is not whitelisted for management."
    exit 1
fi

if [[ ! "$ACTION" =~ ^(start|stop|restart)$ ]]; then
    echo "Error: Invalid action '$ACTION'."
    exit 1
fi

# 2. The SRE Guardrail (Anti-Lockout Protection)
CORE_SERVICES=("nginx" "mariadb" "panel-daemon")

if [[ " ${CORE_SERVICES[@]} " =~ " ${SERVICE} " ]] && [ "$ACTION" == "stop" ]; then
    echo "CRITICAL: You cannot STOP core infrastructure ($SERVICE). oPanel will go offline. Action blocked."
    exit 1
fi

# 3. Execution
echo "Executing 'systemctl $ACTION $SERVICE'..."
systemctl $ACTION $SERVICE

if [ $? -eq 0 ]; then
    echo "Success: $SERVICE has been successfully $ACTION-ed."
    exit 0
else
    echo "Error: Failed to $ACTION $SERVICE. Check syslog for details."
    exit 1
fi