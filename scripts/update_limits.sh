#!/bin/bash
# /opt/panel/scripts/update_limits.sh

PAYLOAD=$1
UPLOAD_SIZE=$(echo "$PAYLOAD" | jq -r '.upload_size')
MAX_TIME=$(echo "$PAYLOAD" | jq -r '.max_time')

# 1. Update Nginx Globally (Fixes 413 Request Entity Too Large)
cat <<EOF > /etc/nginx/conf.d/panel_upload_limits.conf
# Managed by oPanel
client_max_body_size ${UPLOAD_SIZE}M;
EOF

# 2. Update PHP Globally (Fixes PHP post/upload limits)
# Note: We apply this to all PHP versions you might have installed
for v in 8.1 8.2 8.3; do
    if [ -d "/etc/php/$v/fpm/conf.d" ]; then
        cat <<EOF > /etc/php/$v/fpm/conf.d/99-panel-limits.ini
; Managed by oPanel
upload_max_filesize = ${UPLOAD_SIZE}M
post_max_size = ${UPLOAD_SIZE}M
max_execution_time = ${MAX_TIME}
max_input_time = ${MAX_TIME}
EOF
        systemctl restart php${v}-fpm
    fi
done

# 3. Reload Nginx to apply changes
systemctl reload nginx

echo "Success: Server limits updated to ${UPLOAD_SIZE}MB."
exit 0