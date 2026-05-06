# OPANEL PROJECT MANIFEST & COMPLETE ARCHITECTURE MAP

**Read this entirely before generating any code, file paths, or architecture suggestions.**

## 1. Core Architectural Paradigm & Security Model
* **Project:** oPanel (Custom Linux web hosting control panel).
* **Web Stack:** Nginx, MariaDB, PHP 8.x (FPM), Node.js, PM2, Redis, Pure-FTPd, ModSecurity.
* **Separation of Concerns (SRE Bridge):** PHP acts strictly as an unprivileged API/Gatekeeper (`www-data`). It does **not** execute heavy tasks or root-level server modifications directly.
* **The Execution Bridge:** PHP dispatches formatted JSON payloads to a MariaDB `tasks_queue` using the `TaskQueue->dispatch()` method.
* **The Execution Engine:** A Python background daemon (`worker.py`) running as `root` polls this queue every 3 seconds. It securely maps the action to a whitelisted Bash script in `/opt/panel/scripts/`, passes the JSON as `$1`, and logs the physical output.
* **Real-Time State (Sudoers Bridges):** To manage real-time UI states without queue delays, specific Sudoers bridges are configured for `www-data` (e.g., toggling the Master WAF, reading SSL certificates via `openssl`, checking Fail2ban statuses via `shell_exec`).

## 2. Directory Path Map
* **Web Root (UI):** `/opt/panel/www/`
* **Main Entry:** `/opt/panel/www/index.php`
* **API/AJAX Controllers:** `/opt/panel/www/ajax/*.php`
* **PHP Classes/Logic:** `/opt/panel/www/classes/*.php` (e.g., `Database.php`, `TaskQueue.php`, `TOTP.php`)
* **Frontend JS/CSS:** `/opt/panel/www/js/panel.js`
* **Frontend Views & Modals:** `/opt/panel/www/views/components/` and `/opt/panel/www/views/modals/`
* **Bash Worker Scripts:** `/opt/panel/scripts/*.sh`
* **Python Daemon:** `/opt/panel/daemon/worker.py` and `scheduler.py`
* **Nginx vHosts:** `/etc/nginx/sites-available/` (Symlinked to `sites-enabled/`)
* **System Logs:** `/opt/panel/logs/`
* **Backup Vault:** `/opt/panel/backups/websites/` and `/opt/panel/backups/databases/`

## 3. Frontend & UI Strictures
* **Frameworks:** Bootstrap 5, pure jQuery, vanilla CSS. **(DO NOT use React, Vue, or Tailwind)**.
* **Design aesthetic:** Clean, professional, and functional UI/UX design with brighter tones to ensure high visibility (avoid dark mode unreadable contrast issues). Utilize icons, hover effects, and playful transitions where appropriate.
* **Navigation:** Single Page Application (SPA) feel managed by `panel-tabs.php`. Uses Bootstrap Tabs with URL hash persistence (`#domains`, `#security`, etc.) to prevent full-page reloads.
* **Modals:** Bootstrap Modals must auto-close upon successful AJAX completion using `$('#modalId').modal('hide');` and the form must be reset using `$('#formId')[0].reset();`.
* **Notifications:** DO NOT use Bootstrap Toasts or generic `alert()`. We use a custom, pure jQuery/CSS floating Toast system injected at the bottom of the DOM. Trigger it using: `showToast("Your message here");`.
* **Clipboard/Copy:** Use the hidden `<textarea>` fallback to bypass Modal Focus Traps and self-signed SSL restrictions.
* **Data Polling:** Live telemetry (CPU/RAM/Disk, Redis, Fail2ban, Tasks) is updated via periodic AJAX polling loops in `panel.js`.

## 4. PHP Backend Coding Standards
* **Gatekeeper:** EVERY file in the `/ajax/` directory must require `security.php` on line 2.
* **Methods:** AJAX controllers accept `POST` method only.
* **CSRF:** Mandatory `HTTP_X_CSRF_TOKEN` validation in all AJAX requests via `security.php`.
* **Sessions:** Strict CSRF validation is required. Sessions must immediately call `session_write_close();` after validation to prevent locking.
* **Database:** Use the Singleton pattern `Database::getInstance()->getConnection()` with strict PDO Prepared Statements. No raw, unescaped queries.
* **Output:** All AJAX endpoints must return strict JSON: `header('Content-Type: application/json');` with `['success' => true/false, 'error' => 'message']`.

## 5. Bash Worker Script Guidelines
* **Execution:** All worker scripts (`/opt/panel/scripts/*.sh`) are executed by `worker.py` as `root`.
* **Inputs:** They rely heavily on `jq` to parse the provided JSON payload from the database queue (`PAYLOAD=$1`).
* **Outputs:** They must return standard exit codes (`exit 0` for success, `exit 1` for failure). The output text (echo) is captured by the Python daemon and written back to the database.
* **Source of Truth Synchronization:** State changes (creating a user, adding a DNS record, changing a firewall port) MUST be actively synchronized back to the MariaDB `panel_core` database to ensure the UI remains the absolute source of truth.
* **Data Protection:** Be extremely careful to prevent accidental data deletion (e.g., using scorch-earth wipes). Fail securely.

## 6. Server Infrastructure & Application Deployments
* **Master Panel:** Runs strictly on Port `7443` over HTTPS. It uses self-signed certificates fallback but can bind to a domain via Let's Encrypt.
* **Standard PHP:** Uses FastCGI with isolated, self-healing FPM pools generated per user (`/etc/php/8.x/fpm/pool.d/user.conf`).
* **Laravel:** Shifts Document Root to `public_html/public` and deploys background queue workers via PM2 strictly as the client Linux user.
* **Python/Node.js:** Dynamically converts Nginx from FastCGI into a WebSocket-compatible Reverse Proxy, allocating specific ports internally and managing the app lifecycle through PM2.
* **Git Integrations:** Uses `sudo -u $USERNAME` to securely impersonate the client user utilizing their `id_ed25519` deploy keys. Parses commits via `jq`.
* **Mail Engine:** Integrated with Postfix/Dovecot directly hooked into MariaDB (`panel_core.mail_users`). Fast 1-Click DNS routing templates for Google Workspace and Microsoft 365.
* **DNS:** Managed via BIND9. Dynamic serial generation (`YYYYMMDDNN`) for zones.
