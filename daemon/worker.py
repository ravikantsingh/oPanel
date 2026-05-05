#!/usr/bin/env python3
# /opt/panel/daemon/worker.py

import mysql.connector
import time
import subprocess
import json
import logging
import os
import signal
import tempfile

# Configure Logging
logging.basicConfig(
    filename='/opt/panel/logs/daemon.log',
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

# Database Credentials
DB_CONFIG = {
    'host': '127.0.0.1',
    'user': 'panel_user',
    'password': 'YOUR_SECURE_PASSWORD',
    'database': 'panel_core'
}

# Security Whitelist
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
    'manage_mail_dns': '/opt/panel/scripts/mail_dns_manager.sh',
    'manage_mail_user': '/opt/panel/scripts/mail_user_manager.sh',
    'install_mail_engine': '/opt/panel/scripts/install_mail_engine.sh',
    'uninstall_mail_engine': '/opt/panel/scripts/uninstall_mail_engine.sh',
    'https_routing_manager': '/opt/panel/scripts/https_routing_manager.sh',
    'manage_fail2ban': '/opt/panel/scripts/fail2ban_manager.sh',
    'manage_service': '/opt/panel/scripts/service_manager.sh',
    'wp_redis_manager': '/opt/panel/scripts/wp_redis_manager.sh',
    'domain_status': '/opt/panel/scripts/domain_status.sh',
    'adv_web_compile': '/opt/panel/scripts/advanced_web_compiler.sh',
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
    cursor.execute("SELECT id, action, payload FROM tasks_queue WHERE status = 'pending' ORDER BY id ASC LIMIT 1")
    task = cursor.fetchone()

    if task:
        task_id = task['id']
        action = task['action']
        payload_json = task['payload']

        logging.info(f"Picked up Task #{task_id}: {action}")

        cursor.execute("UPDATE tasks_queue SET status = 'processing' WHERE id = %s", (task_id,))
        db.commit()

        if action not in ALLOWED_ACTIONS:
            error_msg = f"Security Error: Action '{action}' is not whitelisted."
            cursor.execute("UPDATE tasks_queue SET status = 'failed', output_log = %s WHERE id = %s", (error_msg, task_id))
            db.commit()
            cursor.close()
            db.close()
            return

        script_path = ALLOWED_ACTIONS[action]

        try:
            # ---> THE MASTER SRE FIX: Write to a physical disk file instead of using Pipes <---
            with tempfile.TemporaryFile(mode='w+', encoding='utf-8') as temp_log:
                process = subprocess.Popen(
                    [script_path, payload_json],
                    stdout=temp_log,
                    stderr=subprocess.STDOUT, # Merge stderr into stdout
                    start_new_session=True
                )

                timeout_seconds = 300
                start_time = time.time()
                timed_out = False
                
                # Manual timeout polling
                while process.poll() is None:
                    time.sleep(1)
                    if time.time() - start_time > timeout_seconds:
                        os.killpg(os.getpgid(process.pid), signal.SIGKILL)
                        timed_out = True
                        process.wait() # Wait for the kill to register
                        break

                # The process is completely dead/finished. Safe to read the physical file.
                temp_log.seek(0)
                output_text = temp_log.read()
                exit_code = process.returncode
                
                # Database Update Logic
                if timed_out:
                    error_log = f"CRITICAL: Task timed out after 300 seconds. Process Group terminated.\nOUTPUT:\n{output_text}"
                    cursor.execute("UPDATE tasks_queue SET status = 'failed', output_log = %s WHERE id = %s", (error_log, task_id))
                elif exit_code == 0:
                    full_output = f"EXIT CODE: {exit_code}\nOUTPUT:\n{output_text}"
                    cursor.execute("UPDATE tasks_queue SET status = 'completed', output_log = %s WHERE id = %s", (full_output, task_id))
                else:
                    full_output = f"EXIT CODE: {exit_code}\nOUTPUT:\n{output_text}"
                    cursor.execute("UPDATE tasks_queue SET status = 'failed', output_log = %s WHERE id = %s", (full_output, task_id))
                
                db.commit()

        except Exception as e:
            error_log = f"Python Execution Exception: {str(e)}"
            cursor.execute("UPDATE tasks_queue SET status = 'failed', output_log = %s WHERE id = %s", (error_log, task_id))
            db.commit()

    cursor.close()
    db.close()

if __name__ == '__main__':
    logging.info("oPanel Daemon Started.")
    
    try:
        db_clean = get_db_connection()
        if db_clean:
            clean_cursor = db_clean.cursor()
            clean_cursor.execute("UPDATE tasks_queue SET status='failed', output_log='Task orphaned due to daemon restart.' WHERE status='processing'")
            db_clean.commit()
            clean_cursor.close()
            db_clean.close()
    except Exception as e:
        logging.error(f"Failed to clean ghost tasks: {e}")
        
    while True:
        process_tasks()
        time.sleep(3)