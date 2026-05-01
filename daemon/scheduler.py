#!/usr/bin/env python3
# /opt/panel/daemon/scheduler.py

import mysql.connector
import json
from datetime import datetime

DB_PASS = 'YOUR_DB_PASSWORD'

def run_scheduler():
    conn = None
    cursor = None
    try:
        conn = mysql.connector.connect(
            host="localhost",
            user="panel_user",
            password=DB_PASS,
            database="panel_core"
        )
        cursor = conn.cursor(dictionary=True)

        # 1. Find jobs meant to run at this exact hour, that haven't run today
        query = """
            SELECT id, target, backup_type, frequency, retention_days 
            FROM backup_schedules 
            WHERE is_active = 1 
            AND run_hour = HOUR(NOW())
            AND (last_run IS NULL OR DATE(last_run) < CURDATE())
        """
        cursor.execute(query)
        jobs = cursor.fetchall()
        
        dispatched_count = 0

        for job in jobs:
            # 2. Check Frequency constraints
            today = datetime.now()
            # If weekly, only run on Sunday (weekday 6)
            if job['frequency'] == 'weekly' and today.weekday() != 6:
                continue
            # If monthly, only run on the 1st of the month
            if job['frequency'] == 'monthly' and today.day != 1:
                continue

            # 3. Format the JSON payload for the bash script
            action_type = 'backup_db' if job['backup_type'] == 'db' else 'backup_web'
            payload = {
                "action": action_type,
                "target": job['target'],
                "is_auto": "true",
                "retention": job['retention_days']
            }
            
            # 4. Dispatch to the Task Queue
            cursor.execute(
                "INSERT INTO tasks_queue (action, payload, status) VALUES (%s, %s, 'pending')", 
                ('manage_backup', json.dumps(payload))
            )
            
            # 5. Mark as executed for today
            cursor.execute("UPDATE backup_schedules SET last_run = NOW() WHERE id = %s", (job['id'],))
            dispatched_count += 1
            
        conn.commit()
        print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Auto-Backup Engine: Scheduled {dispatched_count} tasks.")

    except Exception as e:
        print(f"Scheduler Error: {e}")
        
    # ---> THE FIX: Guarantee connection cleanup even if it crashes <---
    finally:
        if cursor:
            cursor.close()
        if conn and conn.is_connected():
            conn.close()

if __name__ == "__main__":
    run_scheduler()