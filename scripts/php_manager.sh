#!/bin/bash
# /opt/panel/scripts/php_manager.sh

PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')
PHP_VER=$(echo "$PAYLOAD" | jq -r '.php_version')

POOL_CONF="/etc/php/$PHP_VER/fpm/pool.d/$USERNAME.conf"

# ---> NEW: SELF-HEALING FPM POOL GENERATOR <---
if [ ! -f "$POOL_CONF" ]; then
    # 1. Ensure that specific PHP version is actually installed on the server
    if [ ! -d "/etc/php/$PHP_VER/fpm/pool.d" ]; then
        echo "Error: PHP $PHP_VER FPM is not installed on this server."
        exit 1
    fi
    
    # 2. Generate a secure, isolated baseline pool for the user
    cat <<EOF > "$POOL_CONF"
[$USERNAME]
user = $USERNAME
group = $USERNAME
listen = /run/php/php$PHP_VER-fpm-$USERNAME.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
EOF
    
    # Optional: Remove the default www.conf if it exists so it doesn't waste RAM
    # rm -f "/etc/php/$PHP_VER/fpm/pool.d/www.conf"
fi
# ----------------------------------------------

# Helper function to safely update FPM directives
# Helper function to safely update FPM directives
update_fpm_setting() {
    local type=$1
    local key=$2
    local val=$3
    
    # Skip if empty
    if [ -z "$val" ]; then return; fi
    
    # Translate Plesk UI variables into absolute Linux paths
    val=$(echo "$val" | sed "s|{WEBSPACEROOT}|/home/$USERNAME/web/$DOMAIN/public_html|g")
    val=$(echo "$val" | sed "s|{DOCROOT}|/home/$USERNAME/web/$DOMAIN/public_html|g")
    val=$(echo "$val" | sed "s|{TMP}|/tmp|g")
    val=$(echo "$val" | sed "s|{/}|/|g")
    val=$(echo "$val" | sed "s|{:}|:|g")

    # Format based on type (value, flag, or raw pm setting)
    local search_str=""
    local replace_str=""
    
    if [ "$type" == "val" ]; then
        search_str="^;*[[:space:]]*php_admin_value\[$key\]"
        # Note: No double quotes here. PHP must evaluate constants like E_ALL
        replace_str="php_admin_value[$key] = $val"
    elif [ "$type" == "flag" ]; then
        search_str="^;*[[:space:]]*php_admin_flag\[$key\]"
        replace_str="php_admin_flag[$key] = $val"
    elif [ "$type" == "pm" ]; then
        search_str="^;*[[:space:]]*$key"
        replace_str="$key = $val"
    fi

    # ---> NEW: THE SED SAFETY LOCK <---
    # Escape the '&' and '|' characters so sed treats them as literal text
    local safe_replace=$(echo "$replace_str" | sed 's/&/\\&/g' | sed 's/|/\\|/g')

    # Replace or Append using the safe string
    if grep -qE "$search_str" "$POOL_CONF"; then
        sed -i -E "s|$search_str[[:space:]]*=.*|$safe_replace|" "$POOL_CONF"
    else
        echo "$replace_str" >> "$POOL_CONF"
    fi
}

# 1. Update Core PHP Settings (Admin Values)
update_fpm_setting "val" "memory_limit" "$(echo "$PAYLOAD" | jq -r '.php_memory_limit')"
update_fpm_setting "val" "max_execution_time" "$(echo "$PAYLOAD" | jq -r '.php_max_exec_time')"
update_fpm_setting "val" "max_input_time" "$(echo "$PAYLOAD" | jq -r '.php_max_input_time')"
update_fpm_setting "val" "post_max_size" "$(echo "$PAYLOAD" | jq -r '.php_post_max_size')"
update_fpm_setting "val" "upload_max_filesize" "$(echo "$PAYLOAD" | jq -r '.php_upload_max_filesize')"
update_fpm_setting "val" "disable_functions" "$(echo "$PAYLOAD" | jq -r '.php_disable_functions')"
update_fpm_setting "val" "include_path" "$(echo "$PAYLOAD" | jq -r '.php_include_path')"
update_fpm_setting "val" "session.save_path" "$(echo "$PAYLOAD" | jq -r '.php_session_save_path')"
update_fpm_setting "val" "mail.force_extra_parameters" "$(echo "$PAYLOAD" | jq -r '.php_mail_params')"
update_fpm_setting "val" "open_basedir" "$(echo "$PAYLOAD" | jq -r '.php_open_basedir')"
update_fpm_setting "val" "error_reporting" "$(echo "$PAYLOAD" | jq -r '.php_error_reporting')"

# 2. Update PHP Flags (On/Off)
update_fpm_setting "flag" "opcache.enable" "$(echo "$PAYLOAD" | jq -r '.php_opcache_enable')"
update_fpm_setting "flag" "display_errors" "$(echo "$PAYLOAD" | jq -r '.php_display_errors')"
update_fpm_setting "flag" "log_errors" "$(echo "$PAYLOAD" | jq -r '.php_log_errors')"
update_fpm_setting "flag" "allow_url_fopen" "$(echo "$PAYLOAD" | jq -r '.php_allow_url_fopen')"
update_fpm_setting "flag" "file_uploads" "$(echo "$PAYLOAD" | jq -r '.php_file_uploads')"
update_fpm_setting "flag" "short_open_tag" "$(echo "$PAYLOAD" | jq -r '.php_short_open_tag')"

# 3. Update FPM Pool Engine Settings
update_fpm_setting "pm" "pm" "$(echo "$PAYLOAD" | jq -r '.fpm_pm')"
update_fpm_setting "pm" "pm.max_children" "$(echo "$PAYLOAD" | jq -r '.fpm_max_children')"
update_fpm_setting "pm" "pm.max_requests" "$(echo "$PAYLOAD" | jq -r '.fpm_max_requests')"
update_fpm_setting "pm" "pm.start_servers" "$(echo "$PAYLOAD" | jq -r '.fpm_start_servers')"
update_fpm_setting "pm" "pm.min_spare_servers" "$(echo "$PAYLOAD" | jq -r '.fpm_min_spare_servers')"
update_fpm_setting "pm" "pm.max_spare_servers" "$(echo "$PAYLOAD" | jq -r '.fpm_max_spare_servers')"

# 4. Test Syntax and Reload
if php-fpm$PHP_VER -t 2>/dev/null; then
    systemctl reload php$PHP_VER-fpm
    
    # We let PHP handle the UI database update via the dispatcher to keep this script clean
    echo "Success: PHP configuration updated and FPM reloaded for $DOMAIN."
    exit 0
else
    echo "Error: PHP-FPM syntax test failed. Check the payload variables."
    exit 1
fi