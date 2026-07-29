#!/bin/bash
# /opt/panel/scripts/update_cloudflare_ips.sh
# Runs weekly via cron to keep Cloudflare Real-IP lists updated.

CF_CONF_FILE="/etc/nginx/conf.d/cloudflare-ips.conf"
TEMP_FILE="/tmp/cloudflare-ips.tmp"

echo "# Stackrium Automated Cloudflare IP Ranges" > "$TEMP_FILE"
echo "# Updated: $(date)" >> "$TEMP_FILE"

# Fetch IPv4 and IPv6 ranges securely with a timeout
curl -s -m 10 https://www.cloudflare.com/ips-v4 | while read ip; do
    echo "set_real_ip_from $ip;" >> "$TEMP_FILE"
done

curl -s -m 10 https://www.cloudflare.com/ips-v6 | while read ip; do
    echo "set_real_ip_from $ip;" >> "$TEMP_FILE"
done

# Safety Check: Did we actually download IPs? (File should be > 5 lines)
LINE_COUNT=$(wc -l < "$TEMP_FILE")

if [ "$LINE_COUNT" -gt 5 ]; then
    # Move the temp file into production
    mv "$TEMP_FILE" "$CF_CONF_FILE"
    
    # Reload Nginx silently to apply
    if nginx -t >/dev/null 2>&1; then
        systemctl reload nginx
    fi
else
    # Network failed. Delete temp file and keep the old IPs working.
    rm -f "$TEMP_FILE"
    echo "Failed to fetch Cloudflare IPs. Retaining previous list."
    exit 1
fi