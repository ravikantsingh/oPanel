#!/bin/bash
# /opt/panel/scripts/proxy_manager.sh

PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
TYPE=$(echo "$PAYLOAD" | jq -r '.proxy_type')
CUSTOM_IPS=$(echo "$PAYLOAD" | jq -r '.custom_ips')
HEADER=$(echo "$PAYLOAD" | jq -r '.custom_header')

PROXY_FILE="/etc/nginx/conf.d/domains/${DOMAIN}-proxy.conf"

if [ "$TYPE" == "direct" ]; then
    rm -f "$PROXY_FILE"
    echo "Success: Direct routing enabled. Proxy settings removed."

elif [[ "$TYPE" =~ ^(cloudflare|fastly|cloudfront|sucuri)$ ]]; then
    # Dynamic handler for the "Big Players"
    echo "# Stackrium Automated $TYPE Proxy Config for $DOMAIN" > "$PROXY_FILE"
    echo "include /etc/nginx/conf.d/cdn-$TYPE.conf;" >> "$PROXY_FILE"
    
    # Map the correct authoritative header for each specific CDN to prevent spoofing
    case "$TYPE" in
        cloudflare) echo "real_ip_header CF-Connecting-IP;" >> "$PROXY_FILE" ;;
        fastly)     echo "real_ip_header Fastly-Client-IP;" >> "$PROXY_FILE" ;;
        sucuri)     echo "real_ip_header X-Sucuri-ClientIP;" >> "$PROXY_FILE" ;;
        cloudfront) echo "real_ip_header X-Forwarded-For;" >> "$PROXY_FILE" ;;
    esac
    
    echo "Success: $TYPE routing enabled."

elif [ "$TYPE" == "custom" ]; then
    echo "# Stackrium Custom Proxy Config for $DOMAIN" > "$PROXY_FILE"
    echo "$CUSTOM_IPS" | tr ',' '\n' | while read ip; do
        ip=$(echo "$ip" | xargs)
        if [ ! -z "$ip" ]; then echo "set_real_ip_from $ip;" >> "$PROXY_FILE"; fi
    done
    
    echo "real_ip_header $HEADER;" >> "$PROXY_FILE"
    echo "real_ip_recursive on;" >> "$PROXY_FILE"
    echo "Success: Custom proxy routing enabled."
else
    echo "Error: Unknown proxy type."
    exit 1
fi

# SAFETY CHECK: Never reload if Nginx syntax is broken!
if nginx -t &>/dev/null; then
    systemctl reload nginx
    exit 0
else
    rm -f "$PROXY_FILE"
    systemctl reload nginx
    echo "Error: Invalid syntax. Nginx rejected the configuration. Rolling back."
    exit 1
fi