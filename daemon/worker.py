#!/usr/bin/env python3
# /opt/panel/daemon/worker.py

import mysql.connector
import time
import subprocess
import json
import logging

# Configure Logging
logging.basicConfig(
    filename='/opt/panel/logs/daemon.log',
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

# Database Credentials (Must match your Phase 1.1 setup)
DB_CONFIG = {
    'host': '127.0.0.1',
    'user': 'panel_user',
    'password': 'YOUR_SECURE_PASSWORD',
    'database': 'panel_core'
}

# Security Whitelist: Map database actions to specific bash scripts
ALLOWED_ACTIONS = {
    'create_user': '/opt/panel/scripts/user_manager.sh',
    'create_vhost': '/opt/panel/scripts/vhost_manager.sh',
    'create_db': '/opt/panel/scripts/db_manager.sh',
    'delete_db': '/opt/panel/scripts/db_manager.sh',
    'install_wp': '/opt/panel/scripts/wp_manager.sh',
    'deploy_node': '/opt/panel/scripts/node_manager.sh',
    'node_action': '/opt/panel/scripts/node_action.sh',     
    'manage_db': '/opt/panel/scripts/db_manager.sh',     
    'install_ssl': '/opt/panel/scripts/ssl_manager.sh',
    'manage_firewall': '/opt/panel/scripts/firewall_manager.sh',
    'git_clone': '/opt/panel/scripts/git_manager.sh',
    'generate_ssh_key': '/opt/panel/scripts/ssh_key_manager.sh',
    'git_pull': '/opt/panel/scripts/git_pull_manager.sh',
    'create_dns': '/opt/panel/scripts/dns_manager.sh',
    'manage_dns_record': '/opt/panel/scripts/dns_record_manager.sh',
    'manage_cron': '/opt/panel/scripts/cron_manager.sh',
    'manage_php': '/opt/panel/scripts/php_manager.sh',
    'manage_ftp': '/opt/panel/scripts/ftp_manager.sh',
    'manage_backup': '/opt/panel/scripts/backup_manager.sh',
    'rotate_fm': '/opt/panel/scripts/rotate_fm.sh',
    'manage_fm': '/opt/panel/scripts/fm_manager.sh',
    'update_limits': '/opt/panel/scripts/update_limits.sh',
    'restore_backup': '/opt/panel/scripts/restore_manager.sh',
    'delete_backup': '/opt/panel/scripts/delete_backup_manager.sh',
    'secure_panel': '/opt/panel/scripts/secure_panel.sh',
    'delete_domain': '/opt/panel/scripts/delete_domain.sh',
    'set_timezone': '/opt/panel/scripts/set_timezone.sh',
    'delete_user': '/opt/panel/scripts/user_manager.sh',
    'install_php': '/opt/panel/scripts/php_installer.sh',
}

def get_db_connection():
    try:
        return mysql.connector.connect(**DB_CONFIG)
    except mysql.connector.Error as err:
        logging.error(f"Database connection failed: {err}")
        return None

def process_tasks():
    db = get_db_connection()
    if not db:
        return

    cursor = db.cursor(dictionary=True)

    # 1. Look for ONE pending task (oldest first)
    cursor.execute("SELECT id, action, payload FROM tasks_queue WHERE status = 'pending' ORDER BY id ASC LIMIT 1")
    task = cursor.fetchone()

    if task:
        task_id = task['id']
        action = task['action']
        payload_json = task['payload']

        logging.info(f"Picked up Task #{task_id}: {action}")

        # 2. Mark task as processing
        cursor.execute("UPDATE tasks_queue SET status = 'processing' WHERE id = %s", (task_id,))
        db.commit()

        # 3. Security Check
        if action not in ALLOWED_ACTIONS:
            error_msg = f"Security Error: Action '{action}' is not whitelisted."
            logging.error(error_msg)
            cursor.execute("UPDATE tasks_queue SET status = 'failed', output_log = %s WHERE id = %s", (error_msg, task_id))
            db.commit()
            return

        script_path = ALLOWED_ACTIONS[action]

        # 4. Execute the Bash Script
        try:
            # We pass the JSON payload directly as the first argument to the bash script
            # 5-Minute Timeout Protection
            result = subprocess.run(
                [script_path, payload_json],
                capture_output=True,
                text=True,
                check=False,
                timeout=300 # Kills the bash script if it hangs for more than 5 minutes
            )

            stdout = result.stdout.strip()
            stderr = result.stderr.strip()
            exit_code = result.returncode

            # Combine output for the database log
            full_output = f"EXIT CODE: {exit_code}\nSTDOUT:\n{stdout}\nSTDERR:\n{stderr}"

            # 5. Update Database with Results
            if exit_code == 0:
                cursor.execute("UPDATE tasks_queue SET status = 'completed', output_log = %s WHERE id = %s", (full_output, task_id))
                logging.info(f"Task #{task_id} completed successfully.")
            else:
                cursor.execute("UPDATE tasks_queue SET status = 'failed', output_log = %s WHERE id = %s", (full_output, task_id))
                logging.error(f"Task #{task_id} failed with exit code {exit_code}.")

        # Explicit Timeout Handler
        except subprocess.TimeoutExpired:
            error_log = "CRITICAL: Task timed out after 300 seconds and was forcefully killed to prevent queue blockage."
            logging.error(error_log)
            cursor.execute("UPDATE tasks_queue SET status = 'failed', output_log = %s WHERE id = %s", (error_log, task_id))
            db.commit()

        except Exception as e:
            error_log = f"Python Execution Exception: {str(e)}"
            logging.error(error_log)
            cursor.execute("UPDATE tasks_queue SET status = 'failed', output_log = %s WHERE id = %s", (error_log, task_id))
            db.commit()

    cursor.close()
    db.close()

if __name__ == '__main__':
    logging.info("oPanel Daemon Started.")
    print("Daemon running. Press Ctrl+C to stop.")
    
    # ---> NEW: GHOST TASK CLEANSER <---
    # Automatically heal orphaned tasks if the daemon is restarted
    try:
        db_clean = get_db_connection()
        if db_clean:
            clean_cursor = db_clean.cursor()
            clean_cursor.execute("UPDATE tasks_queue SET status='failed', output_log='Task orphaned due to daemon restart or memory spike.' WHERE status='processing'")
            db_clean.commit()
            clean_cursor.close()
            db_clean.close()
            logging.info("Ghost tasks cleared.")
    except Exception as e:
        logging.error(f"Failed to clean ghost tasks: {e}")
        
    # The Infinite Loop
    while True:
        process_tasks()
        time.sleep(3) # Check the database every 3 seconds to save CPU