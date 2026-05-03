# OPANEL PROJECT MANIFEST & ARCHITECTURE RULES
**Read this entirely before generating any code, file paths, or architecture suggestions.**

## 1. Core Architecture
*   **Project:** oPanel (Custom Linux web hosting control panel).
*   **Web Stack:** Nginx, PHP 8.x (FPM), MariaDB.
*   **Backend Paradigm:** PHP acts as an API/Gatekeeper. It does NOT execute heavy tasks directly. Instead, it dispatches JSON payloads to a Database Queue (`tasks_queue`), which is picked up by a Python/Bash background daemon `worker.py` running as `root`.
*   **Security Model (SRE Bridge):** The PHP web user (`www-data`) is strictly unprivileged.
*   **Paradigm:** PHP (API/Gatekeeper) -> MariaDB (tasks_queue) -> Python Worker (Root Execution).
*   **Security:** PHP `www-data` is unprivileged. Actions must be whitelisted in `worker.py`.

## 2. File Path Map
*   **Web Root (UI):** `/opt/panel/www/index.php`
*   **API/AJAX Controllers:** `/opt/panel/www/ajax/`
*   **PHP Classes/Logic:** `/opt/panel/www/classes/`
*   **Frontend JS/CSS:** `/opt/panel/www/js/panel.js`
*   **Bash Worker Scripts:** `/opt/panel/scripts/`
*   **Python Daemon:** `/opt/panel/daemon/`
*   **Nginx vHosts:** `/etc/nginx/sites-available/` (Symlinked to `sites-enabled/`) 

## 3. Frontend & UI Guidelines
*   **Frameworks:** Bootstrap 5, pure jQuery, vanilla CSS. (DO NOT use React, Vue, or Tailwind).
*   **Navigation:** Single Page Application (SPA) feel. Use Bootstrap Tabs for navigation. NO full-page reloads.
*   **Modals:** Bootstrap Modals must auto-close upon successful AJAX completion using `$('#modalId').modal('hide');` and the form must be reset.
*   **Notifications:** DO NOT use Bootstrap Toasts or generic `alert()`. We use a custom, pure jQuery/CSS floating Toast system injected at the bottom of the DOM. Trigger it using: `showToast("Your message here");`.
*   **Clipboard/Copy:** Use the hidden `<textarea>` fallback to bypass Modal Focus Traps and self-signed SSL restrictions.

## 4. PHP Coding Standards
*   **Gatekeeper:** EVERY file in the `/ajax/` directory must require `security.php` on line 2 and accept `POST` method only.
*   **CSRF:** Mandatory `HTTP_X_CSRF_TOKEN` validation in all AJAX requests via `security.php`
*   **Sessions:** Strict CSRF validation is required. Sessions must immediately call `session_write_close();` after validation to prevent locking.
*   **Database:** Use the Singleton pattern `Database::getInstance()->getConnection()` with strict PDO Prepared Statements. No raw queries.
*   **Output:** All AJAX endpoints must return strict JSON: `header('Content-Type: application/json');` with `['success' => true/false]`.

## 5. Current Directory Structure
/opt/panel
├── backups
│   ├── databases
│   └── websites
├── daemon
│   ├── scheduler.py
│   └── worker.py
├── logs
│   ├── daemon.log
│   └── scheduler.log
├── scripts
│   ├── backup_manager.sh
│   ├── cron_manager.sh
│   ├── db_manager.sh
│   ├── delete_backup_manager.sh
│   ├── delete_domain.sh
│   ├── dns_manager.sh
│   ├── dns_record_manager.sh
│   ├── firewall_manager.sh
│   ├── fm_manager.sh
│   ├── ftp_manager.sh
│   ├── git_manager.sh
│   ├── git_pull_manager.sh
│   ├── https_routing_manager.sh
│   ├── install_mail_engine.sh
│   ├── mail_dns_manager.sh
│   ├── mail_user_manager.sh
│   ├── node_action.sh
│   ├── node_manager.sh
│   ├── php_installer.sh
│   ├── php_manager.sh
│   ├── restore_manager.sh
│   ├── rotate_fm.sh
│   ├── secure_panel.sh
│   ├── security_manager.sh
│   ├── set_timezone.sh
│   ├── ssh_key_manager.sh
│   ├── ssl_manager.sh
│   ├── sync_firewall.sh
│   ├── uninstall_mail_engine.sh
│   ├── update_limits.sh
│   ├── user_manager.sh
│   ├── vhost_manager.sh
│   ├── waf_updater.sh
│   └── wp_manager.sh
├── templates
│   └── index.html
└── www
    ├── ajax
    │   ├── change_admin_password.php
    │   ├── change_db_password.php
    │   ├── change_php.php
    │   ├── clone_repo.php
    │   ├── create_backup.php
    │   ├── create_db.php
    │   ├── create_dns.php
    │   ├── create_domain.php
    │   ├── create_user.php
    │   ├── delete_backup.php
    │   ├── delete_db.php
    │   ├── delete_domain.php
    │   ├── delete_schedule.php
    │   ├── delete_user.php
    │   ├── deploy_node.php
    │   ├── download_backup.php
    │   ├── get_backups.php
    │   ├── get_connection_info.php
    │   ├── get_cron.php
    │   ├── get_databases.php
    │   ├── get_dns.php
    │   ├── get_domains.php
    │   ├── get_firewall.php
    │   ├── get_fm_sso.php
    │   ├── get_logs.php
    │   ├── get_mail_engine_status.php
    │   ├── get_mail_users.php
    │   ├── get_php_versions.php
    │   ├── get_schedules.php
    │   ├── get_security_status.php
    │   ├── get_ssh_key.php
    │   ├── get_ssl_info.php
    │   ├── get_task_log.php
    │   ├── get_tasks.php
    │   ├── get_users.php
    │   ├── install_mail_engine.php
    │   ├── install_php.php
    │   ├── install_ssl.php
    │   ├── install_wp.php
    │   ├── manage_cron.php
    │   ├── manage_dns_records.php
    │   ├── manage_firewall.php
    │   ├── manage_fm.php
    │   ├── manage_ftp.php
    │   ├── manage_https_routing.php
    │   ├── manage_mail_user.php
    │   ├── manage_php.php
    │   ├── manage_schedule.php
    │   ├── manage_waf.php
    │   ├── manage_waf_rules.php
    │   ├── manual_git_pull.php
    │   ├── node_action.php
    │   ├── restore_backup.php
    │   ├── rotate_fm_password.php
    │   ├── secure_panel.php
    │   ├── security.php
    │   ├── set_timezone.php
    │   ├── system_stats.php
    │   ├── toggle_2fa.php
    │   ├── unban_ip.php
    │   ├── uninstall_mail_engine.php
    │   ├── update_server_limits.php
    │   ├── upload_backup.php
    │   └── webhook.php
    ├── autologin.php
    ├── classes
    │   ├── Database.php
    │   ├── TOTP.php
    │   └── TaskQueue.php
    ├── config
    │   └── database.php
    ├── errors
    │   ├── opanel_403.html
    │   ├── opanel_404.html
    │   └── opanel_50x.html
    ├── index.php
    ├── js
    │   └── panel.js
    ├── login.php
    ├── logout.php
    ├── pma
    │   ├── config.inc.php
    │   └── phpMyAdmin-5.2.3-all-languages
    └── views
        ├── components
        ├── footer.php
        ├── header.php
        └── modals