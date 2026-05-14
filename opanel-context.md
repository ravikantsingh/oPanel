# OPANEL PROJECT MANIFEST & COMPLETE ARCHITECTURE MAP

**Read this entirely before generating any code, file paths, or architecture suggestions.**

## 1. Core Architectural Paradigm & Security Model
* **Project:** Stackrium (Custom Linux web hosting control panel).
* **Web Stack:** Nginx, MariaDB, PHP 8.x (FPM), Node.js, PM2, Redis, Pure-FTPd, ModSecurity.
* **Separation of Concerns (SRE Bridge):** PHP acts strictly as an unprivileged API/Gatekeeper (`www-data`). It does **not** execute heavy tasks or root-level server modifications directly.
* **The Execution Bridge:** PHP dispatches formatted JSON payloads to a MariaDB `tasks_queue` using the `TaskQueue->dispatch()` method.
* **The Execution Engine:** A Python background daemon (`worker.py`) running as `root` polls this queue every 3 seconds. It securely maps the action to a whitelisted Bash script in `/opt/panel/scripts/`, passes the JSON as `$1`, and logs the physical bash output back to the database.
* **Real-Time State (Sudoers Bridges):** To manage real-time UI states without queue delays, specific Sudoers bridges are configured for `www-data` (e.g., toggling the Master WAF, reading SSL certificates via `openssl`, checking Fail2ban statuses via `shell_exec`).
* **Universal Log Router:** Logging is strictly separated into "System Context" (Daemon, Fail2ban, System Journal) and "Tenant Context" (Nginx Error/Access). The PHP gatekeeper dynamically routes `tail` commands to absolute system paths or restricted `/home/$USER/web/$DOMAIN/` paths based on UI entry points.

## 2. Directory Path Map
* **Web Root (UI):** `/opt/panel/www/`
* **Main Entry:** `/opt/panel/www/index.php`
* **API/AJAX Controllers:** `/opt/panel/www/ajax/*.php`
* **PHP Classes/Logic:** `/opt/panel/www/classes/*.php` (e.g., `Database.php`, `TaskQueue.php`, `TOTP.php`)
* **Frontend JS/CSS:** `/opt/panel/www/js/modules/*.js` (e.g., `system.js`, `web.js`)
* **Frontend Views & Modals:** `/opt/panel/www/views/components/` and `/opt/panel/www/views/modals/`
* **JSON State Configurations:** `/opt/panel/www/config/*.json` (e.g., `waf_settings.json`, `settings.json`)
* **Bash Worker Scripts:** `/opt/panel/scripts/*.sh`
* **Python Daemon:** `/opt/panel/daemon/worker.py` and `scheduler.py`
* **Nginx vHosts:** `/etc/nginx/sites-available/` (Symlinked to `sites-enabled/`)
* **System Logs:** `/opt/panel/logs/`
* **Backup Vault:** `/opt/panel/backups/websites/` and `/opt/panel/backups/databases/`

## 3. Frontend & UI Strictures
* **Frameworks:** Bootstrap 5, pure jQuery, vanilla CSS. **(DO NOT use React, Vue, or Tailwind)**.
* **Design aesthetic:** Clean, professional, and functional UI/UX design. Utilize modern Flexbox Cards (`list-group-item`) over rigid HTML `<table>` structures for data-dense, mobile-responsive grids.
* **Navigation:** Single Page Application (SPA) feel managed by `panel-tabs.php`. Uses Bootstrap Tabs with URL hash persistence (`#domains`, `#security`, etc.) to prevent full-page reloads.
* **Modals & Context-Aware UI:** Modals must auto-close upon successful AJAX. Contextual buttons inside data cards (e.g., "Website Logs" inside a domain card) must trigger JavaScript to open the modal and *automatically pre-select* the relevant dropdowns (Domain, User, Log Type) to eliminate repetitive user clicks.
* **Dictionary-Driven Rendering:** Raw backend database strings (like `update_waf`) and JSON payloads must never be exposed to the user. JavaScript must map these actions to human-readable dictionaries (Icons, Titles, Descriptions) before injecting them into the DOM.
* **Notifications:** DO NOT use generic `alert()`. Use the custom floating Toast system: `showToast("Your message here");`.
* **Data Polling:** Live telemetry (CPU/RAM/Logs, Tasks) is updated via periodic `setInterval` AJAX loops. Background intervals MUST use the exact same UI identifiers as manual fetch buttons to prevent conflict overwrites.

## 4. PHP Backend Coding Standards
* **Gatekeeper:** EVERY file in the `/ajax/` directory must require `security.php` on line 2.
* **Methods:** AJAX controllers accept `POST` method only.
* **CSRF & Sessions:** Mandatory `HTTP_X_CSRF_TOKEN` validation. Sessions must immediately call `session_write_close();` after validation to unlock the session file and allow concurrent AJAX polling.
* **Database:** Use the Singleton pattern `Database::getInstance()->getConnection()` with strict PDO Prepared Statements.
* **JSON Payload Parsing:** When PHP pulls JSON payloads from the database queue, it must natively decode and extract the primary target (e.g., Domain Name, Database Name) into a safe, human-readable string before handing it to the UI.
* **JSON File Integrity:** When saving configuration states via `file_put_contents`, strictly use `JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES` to prevent PHP from breaking file paths (`/` into `\/`). Always enforce `chown www-data` post-creation.

## 5. Bash Worker Script Guidelines
* **Execution:** All worker scripts (`/opt/panel/scripts/*.sh`) are executed by `worker.py` as `root`.
* **Inputs:** They rely heavily on `jq` to parse the provided JSON payload from the database queue (`PAYLOAD=$1`).
* **Outputs:** They must return standard exit codes (`exit 0` for success, `exit 1` for failure).
* **Source of Truth Synchronization:** State changes must be actively synchronized back to the MariaDB `panel_core` database to ensure the UI remains the absolute source of truth.
* **Data Protection:** Be extremely careful to prevent accidental data deletion. Scripts cloning from Git must use temporary directories (`/tmp/`) and only move compiled binaries or rulesets to production paths upon success.

## 6. Server Infrastructure & Application Deployments
* **Master Panel:** Runs strictly on Port `7443` over HTTPS. It uses self-signed certificates fallback but can bind to a domain via Let's Encrypt.
* **Standard PHP:** Uses FastCGI with isolated, self-healing FPM pools generated per user (`/etc/php/8.x/fpm/pool.d/user.conf`).
* **Laravel:** Shifts Document Root to `public_html/public` and deploys background queue workers via PM2 strictly as the client Linux user.
* **Python/Node.js:** Dynamically converts Nginx from FastCGI into a WebSocket-compatible Reverse Proxy, allocating specific ports internally and managing the app lifecycle through PM2.
* **Git Integrations:** Uses `sudo -u $USERNAME` to securely impersonate the client user utilizing their `id_ed25519` deploy keys. Parses commits via `jq`.
* **ModSecurity (WAF):** Employs a dynamic branch architecture capable of hot-swapping between v3.3 (Legacy) and v4.25 LTS (Long-Term Support) via background Git compilation.
* **Mail Engine:** Integrated with Postfix/Dovecot directly hooked into MariaDB (`panel_core.mail_users`). Fast 1-Click DNS routing templates for Google Workspace and Microsoft 365.
* **DNS:** Managed via BIND9. Dynamic serial generation (`YYYYMMDDNN`) for zones.