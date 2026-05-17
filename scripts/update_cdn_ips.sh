#!/bin/bash
# /opt/panel/scripts/update_cdn_ips.sh
# Runs weekly via cron to keep ALL major CDN Real-IP lists updated.

CONF_DIR="/etc/nginx/conf.d"
mkdir -p "$CONF_DIR"

# 1. CLOUDFLARE
echo "Updating Cloudflare IPs..."
CF_TMP="/tmp/cf-ips.tmp"
> "$CF_TMP"
curl -s -m 10 https://www.cloudflare.com/ips-v4 | while read ip; do echo "set_real_ip_from $ip;" >> "$CF_TMP"; done
curl -s -m 10 https://www.cloudflare.com/ips-v6 | while read ip; do echo "set_real_ip_from $ip;" >> "$CF_TMP"; done
if [ $(wc -l < "$CF_TMP") -gt 5 ]; then mv "$CF_TMP" "$CONF_DIR/cdn-cloudflare.conf"; fi

# 2. FASTLY
echo "Updating Fastly IPs..."
FASTLY_TMP="/tmp/fastly-ips.tmp"
> "$FASTLY_TMP"
# Fastly provides a JSON endpoint. We use jq to extract both IPv4 and IPv6 arrays.
curl -s -m 10 https://api.fastly.com/public-ip-list | jq -r '.addresses[], .ipv6_addresses[]' 2>/dev/null | while read ip; do
    echo "set_real_ip_from $ip;" >> "$FASTLY_TMP"
done
if [ $(wc -l < "$FASTLY_TMP") -gt 5 ]; then mv "$FASTLY_TMP" "$CONF_DIR/cdn-fastly.conf"; fi

# 3. AWS CLOUDFRONT
echo "Updating CloudFront IPs..."
CF_AWS_TMP="/tmp/aws-ips.tmp"
> "$CF_AWS_TMP"
# AWS provides a massive JSON file. We filter specifically for the CLOUDFRONT service.
curl -s -m 10 https://ip-ranges.amazonaws.com/ip-ranges.json | jq -r '.prefixes[] | select(.service=="CLOUDFRONT") | .ip_prefix' 2>/dev/null | while read ip; do echo "set_real_ip_from $ip;" >> "$CF_AWS_TMP"; done
curl -s -m 10 https://ip-ranges.amazonaws.com/ip-ranges.json | jq -r '.ipv6_prefixes[] | select(.service=="CLOUDFRONT") | .ipv6_prefix' 2>/dev/null | while read ip; do echo "set_real_ip_from $ip;" >> "$CF_AWS_TMP"; done
if [ $(wc -l < "$CF_AWS_TMP") -gt 5 ]; then mv "$CF_AWS_TMP" "$CONF_DIR/cdn-cloudfront.conf"; fi

# 4. SUCURI WAF
echo "Updating Sucuri IPs..."
SUCURI_TMP="/tmp/sucuri-ips.tmp"
> "$SUCURI_TMP"
# Sucuri provides a direct text list API
curl -s -m 10 https://waf.sucuri.net/api?v2=ips | while read ip; do echo "set_real_ip_from $ip;" >> "$SUCURI_TMP"; done
if [ $(wc -l < "$SUCURI_TMP") -gt 5 ]; then mv "$SUCURI_TMP" "$CONF_DIR/cdn-sucuri.conf"; fi

# Cleanup and Reload Nginx
rm -f /tmp/*.tmp
if nginx -t >/dev/null 2>&1; then
    systemctl reload nginx
fi