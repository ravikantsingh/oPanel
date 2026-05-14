#!/bin/bash
# /opt/panel/scripts/nginx_reload_callback.sh
# Purpose: Safely reloads Nginx in the background and updates the DB if it fails.

TASK_ID=$1

# Perform the reload
if ! systemctl reload nginx > /dev/null 2>&1; then
    
    # If the reload fails, wait 3 seconds to ensure worker.py has finished its initial 'success' database write
    sleep 3
    
    # Extract DB Password and force the task status to FAILED
    DB_PASS=$(grep DB_PASS /opt/panel/www/config/database.php | cut -d"'" -f4)
    MYSQL_CMD="mysql -upanel_user -p${DB_PASS} panel_core -e"
    
    ERROR_MSG="\n\n[CRITICAL WARNING]: The background Nginx reload failed! The configuration was rejected by the kernel."
    
    $MYSQL_CMD "UPDATE tasks_queue SET status='failed', output_log=CONCAT(output_log, '$ERROR_MSG') WHERE id='$TASK_ID';"
fi