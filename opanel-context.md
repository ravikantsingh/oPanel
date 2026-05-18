# STACKRIUM PROJECT MANIFEST & COMPLETE ARCHITECTURE MAP

**Read this entirely before generating any code, file paths, or architecture suggestions.**

## 1. Core Architectural Paradigm & Security Model
* **Project:** Stackrium (Custom Linux web hosting control panel).
* **Web Stack:** Nginx, MariaDB, PHP 8.x (FPM), Node.js, PM2, Redis, Pure-FTPd, ModSecurity, Fail2ban.
* **Separation of Concerns (SRE Bridge):** PHP acts strictly as an unprivileged API/Gatekeeper (`www-data`). It does **not** execute heavy tasks or root-level server modifications directly.
* **The Execution Bridge:** PHP dispatches formatted JSON payloads to a MariaDB `tasks_queue` using the `TaskQueue->dispatch()` method.
* **The Execution Engine:** A Python background daemon (`worker.py`) running as `root` polls this queue every 3 seconds. It securely maps the action to a whitelisted Bash script in `/opt/panel/scripts/`, passes the JSON as `$1`, and logs the physical bash output back to the database.
* **Defense-in-Depth (Layer-7 & Layer-4):** Nginx acts as a proactive Layer-7 shield using instant `444` Connection Drops for known botnet payloads (e.g., `.env`, `phpunit`). Fail2ban acts as the reactive Layer-4 executioner, parsing native JSON logs across wildcard tenant paths to dynamically permanently ban attackers in UFW.
* **Real-Time State (Sudoers Bridges):** To manage real-time UI states without queue delays, specific Sudoers bridges are configured for `www-data` (e.g., toggling the Master WAF, reading SSL certificates via `openssl`, checking Fail2ban statuses via `fail2ban-client`).
* **Universal Log Router:** Logging is strictly separated into "System Context" (Daemon, Fail2ban, System Journal) and "Tenant Context" (Nginx Error/Access). The PHP gatekeeper dynamically routes `tail` commands to absolute system paths or restricted `/home/$USER/web/$DOMAIN/` paths based on UI entry points.

## 2. Directory Path Map
* **Web Root (UI):** `/opt/panel/www/`
* **API/AJAX Controllers:** `/opt/panel/www/ajax/*.php`
* **PHP Classes/Logic:** `/opt/panel/www/classes/*.php`
* **Frontend JS/CSS:** `/opt/panel/www/js/modules/*.js`
* **JSON State Configurations:** `/opt/panel/www/config/*.json` 
* **Bash Worker Scripts:** `/opt/panel/scripts/*.sh`
* **Python Daemon:** `/opt/panel/daemon/worker.py` and `scheduler.py`
* **Nginx vHosts:** `/etc/nginx/sites-available/` (Symlinked to `sites-enabled/`)
* **Nginx Drop & Error Snippets:** `/etc/nginx/snippets/`
* **Global CDN & Proxy Configs:** `/etc/nginx/conf.d/cdn-*.conf` and `/etc/nginx/conf.d/domains/`
* **Fail2ban Filters:** `/etc/fail2ban/filter.d/stackrium-bots.conf`
* **System Logs:** `/opt/panel/logs/`
* **Backup Vault:** `/opt/panel/backups/websites/` and `/opt/panel/backups/databases/`

## 3. Frontend & UI Strictures
* **Frameworks:** Bootstrap 5, pure jQuery, vanilla CSS. **(DO NOT use React, Vue, or Tailwind)**.
* **Design aesthetic:** Clean, professional, and functional UI/UX design. Utilize modern Flexbox Cards (`list-group-item`) over rigid HTML `<table>` structures.
* **Navigation:** Single Page Application (SPA) feel managed by `panel-tabs.php`. Uses Bootstrap Tabs with URL hash persistence (`#domains`, `#security`) to prevent full-page reloads.
* **Modals & Context-Aware UI:** Modals must auto-close upon successful AJAX. Contextual buttons inside data cards (e.g., "Proxy Settings") must trigger JavaScript to open the modal and *automatically pre-select/pre-fill* the relevant hidden inputs (`domain_name`) to eliminate repetitive user clicks.
* **Dictionary-Driven Rendering:** Raw backend database strings (like `update_proxy`) and JSON payloads must never be exposed to the user. JavaScript must map these actions to human-readable dictionaries (Icons, Titles, Descriptions).
* **Terminal/Console Output:** Live log viewers must behave natively. Newest database items render at the top; raw chronological `tail` logs render at the bottom with smooth auto-scroll. 
* **Data Polling:** Live telemetry is updated via periodic `setInterval` AJAX loops. Background intervals MUST use the exact same UI identifiers as manual fetch buttons to prevent conflict overwrites.

## 4. PHP Backend Coding Standards
* **Gatekeeper:** EVERY file in the `/ajax/` directory must require `security.php` on line 2.
* **CSRF & Sessions:** Mandatory `HTTP_X_CSRF_TOKEN` validation. Sessions must immediately call `session_write_close();` after validation to unlock the session file and allow concurrent AJAX polling.
* **Database:** Use the Singleton pattern `Database::getInstance()->getConnection()` with strict PDO Prepared Statements.
* **JSON Payload Parsing:** When PHP pulls JSON payloads from the database queue, it must natively decode and extract the primary target into a safe string.
* **JSON File Integrity:** When saving configuration states via `file_put_contents`, strictly use `JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES` and enforce `chown www-data`.

## 5. Bash Worker Script Guidelines
* **Execution:** All worker scripts (`/opt/panel/scripts/*.sh`) are executed by `worker.py` as `root`.
* **SRE Rollback Protocol (CRITICAL):** Any script that modifies Nginx or PHP configurations MUST create a `.bak` backup, run a syntax test (`nginx -t` or `php-fpm -t`), and **instantly rollback** to the `.bak` file if the syntax check fails, exiting with `1`. Do not allow typos to take the server offline.
* **Inputs:** They rely heavily on `jq` to parse the provided JSON payload (`PAYLOAD=$1`).
* **Source of Truth Synchronization:** State changes must be actively synchronized back to the MariaDB `panel_core` database upon success (`exit 0`).
* **Data Protection:** Scripts cloning from Git or fetching external APIs (like CDN IPs) must use temporary directories (`/tmp/`) and only `mv` the files to production paths if the payload is verified intact.

## 6. Server Infrastructure & Application Deployments
* **Master Panel:** Runs strictly on Port `7443` over HTTPS. 
* **Universal CDN/Proxy Manager:** Nginx must accurately identify real client IPs behind Cloudflare, Fastly, AWS CloudFront, and Sucuri to prevent "CDN Suicide" in Fail2ban. Global IP lists are synced via a weekly cron job. Domains include these dynamically via targeted HTTP headers (e.g., `CF-Connecting-IP`).
* **Native JSON Logging:** Nginx access logs are written exclusively in a highly structured `json_access` format. Fail2ban strictly relies on native JSON Regex parsing (`"status":\s*"444"`) to hunt malicious traffic.
* **Standard PHP:** Uses FastCGI with isolated, self-healing FPM pools generated per user (`/etc/php/8.x/fpm/pool.d/user.conf`).
* **Laravel / Node / Python:** Uses PM2. Nginx dynamically converts from FastCGI into a WebSocket-compatible Reverse Proxy.
* **Git Integrations:** Uses `sudo -u $USERNAME` to securely impersonate the client user utilizing their `id_ed25519` deploy keys.
* **ModSecurity (WAF):** Employs a dynamic branch architecture capable of hot-swapping between v3.3 and v4.x via background Git compilation.
* **Mail & DNS:** Postfix/Dovecot directly hooked into MariaDB (`panel_core.mail_users`). BIND9 manages DNS with dynamic serial generation (`YYYYMMDDNN`).