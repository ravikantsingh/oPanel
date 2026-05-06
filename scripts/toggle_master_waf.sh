#!/bin/bash
# /opt/panel/scripts/toggle_master_waf.sh

STATUS=$1

if [ "$STATUS" == "off" ]; then
    # 1. Comment out in the live Nginx config
    sed -i 's/[ \t]*modsecurity on;/#    modsecurity on;/g' /etc/nginx/sites-available/default
    sed -i 's/[ \t]*modsecurity_rules_file /#    modsecurity_rules_file /g' /etc/nginx/sites-available/default
    
    # 2. Comment out in the secure_panel.sh blueprint
    sed -i 's/[ \t]*modsecurity on;/#    modsecurity on;/g' /opt/panel/scripts/secure_panel.sh
    sed -i 's/[ \t]*modsecurity_rules_file /#    modsecurity_rules_file /g' /opt/panel/scripts/secure_panel.sh

elif [ "$STATUS" == "on" ]; then
    # 1. Uncomment in the live Nginx config
    sed -i 's/#    modsecurity on;/    modsecurity on;/g' /etc/nginx/sites-available/default
    sed -i 's/#    modsecurity_rules_file /    modsecurity_rules_file /g' /etc/nginx/sites-available/default
    
    # 2. Uncomment in the secure_panel.sh blueprint
    sed -i 's/#    modsecurity on;/    modsecurity on;/g' /opt/panel/scripts/secure_panel.sh
    sed -i 's/#    modsecurity_rules_file /    modsecurity_rules_file /g' /opt/panel/scripts/secure_panel.sh
else
    echo "Usage: $0 [on|off]"
    exit 1
fi

# Verify Nginx syntax and reload
if nginx -t > /dev/null 2>&1; then
    systemctl reload nginx
    echo "Success"
    exit 0
else
    echo "Nginx syntax error after toggle."
    exit 1
fi