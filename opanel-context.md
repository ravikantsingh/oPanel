 PROJECT MANIFEST & COMPLETE ARCHITECTURE MAP

**Read this entirely before generating any code, file paths, or architecture suggestions.**
## 1. Core Architectural Paradigm & Security Model
* **Project:** Stackrium (Custom Linux web hosting control panel).
* **Web Stack:** Nginx, MariaDB, PHP 8.x (FPM), Node.js, PM2, Redis, Pure-FTPd, ModSecurity, Fail2ban.
* **Separation of Concerns (SRE Bridge):** PHP acts strictly as an unprivileged API/Gatekeeper (`www-data`). It does **not** execute heavy tasks or root-level server modifications directly.
* **The Execution Bridge:** PHP dispatches formatted JSON payloads to a MariaDB `tasks_queue` using the `TaskQueue-&gt;dispatch()` method.
* **The Execution Engine:** A Python background daemon (`worker.py`) running as `root` polls this queue every 3 seconds. It securely maps the action to a whitelisted Bash script in `/opt/panel/scripts/`, passes the JSON as `$1`, and logs the physical bash output back to the database.
* **Database Schema Isolation (CRITICAL):** Master Panel authentication is strictly isolated from Tenant hosting accounts.
  * `panel_admins`: Exclusively holds Master Admin credentials, Bcrypt password hashes, and 2FA TOTP secrets. NEVER store emails or profile data here.
  * `users`: Holds standard web-hosting tenants, disk quotas, SSH public keys, and webhook API tokens.
  * `settings`: A global Key-Value store used for server-wide configurations and Admin Profile/Licensing data (Name, Email, Country).
* **Multi-Stage SaaS Gatekeeper:** Strict separation of Security and Licensing.
  **Phase 1 (Security):* Critical security onboarding (forcing the rotation of the default `admin123` password) is intercepted at the `login.php` gateway *pre-authentication* and routes to `setup_first_admin.php`.
  * *Phase 2 (Licensing):* Telemetry and License registration is handled by unbreakable modals at the dashboard level *post-authentication*, checking for `profile.json` and pinging Stackrium Central via `complete_registration.php`.
* **Defense-in-Depth (Layer-7 & Layer-4):** Nginx acts as a proactive Layer-7 shield using instant `444` Connection Drops for known botnet payloads (e.g., `.env`, `phpunit`). Fail2ban acts as the reactive Layer-4 executioner, parsing native JSON logs across wildcard tenant paths to dynamically permanently ban attackers in UFW.
* **Real-Time State (Sudoers Bridges):** To manage real-time UI states without queue delays, specific Sudoers bridges are configured for `www-data` (e.g., toggling the Master WAF, reading SSL certificates via `openssl`, checking Fail2ban statuses via `fail2ban-client`).
* **Universal Log Router:** Logging is strictly separated into "System Context" (Daemon, Fail2ban, System Journal) and "Tenant Context" (Nginx Error/Access). The PHP gatekeeper dynamically routes `tail` commands to absolute system paths or restricted `/home/$USER/web/$DOMAIN/` paths based on UI entry points.

## 2. Directory Path Map
* **Web Root (UI):** `/opt/panel/www/`
* **API/AJAX Controllers:** `/opt/panel/www/ajax/*.php`
  * *SSO Generators:* `/ajax/get_fm_sso.php` and `/ajax/get_pma_sso.php`
  * *Fallback Gateway:* `/ajax/fm_proxy.php`
* **PHP Classes/Logic:** `/opt/panel/www/classes/*.php`
* **Frontend JS/CSS:** `/opt/panel/www/js/modules/*.js` (Master entry: `core.js`)
* **JSON State Configurations:** `/opt/panel/www/config/*.json`
* **License State:** `/opt/panel/www/config/profile.json`
* **Bash Worker Scripts:** `/opt/panel/scripts/*.sh`
* **Python Daemon:** `/opt/panel/daemon/worker.py` and `scheduler.py`
* **Nginx vHosts:** `/etc/nginx/sites-available/` (Symlinked to `sites-enabled/`)
* **Nginx Drop & Error Snippets:** `/etc/nginx/snippets/`
* **Global CDN & Proxy Configs:** `/etc/nginx/conf.d/cdn-*.conf` and `/etc/nginx/conf.d/domains/`
* **Tenant Routing Map:** `/etc/nginx/stackrium_tenant_map.conf`
* **Fail2ban Filters:** `/etc/fail2ban/filter.d/stackrium-bots.conf`
* **System Logs:** `/opt/panel/logs/`
* **Backup Vault:** `/opt/panel/backups/websites/` and `/opt/panel/backups/databases/`

## 3. Frontend & UI Strictures
* **Frameworks:** Bootstrap 5, pure jQuery, vanilla CSS. **(DO NOT use React, Vue, or Tailwind)**.
* **Design aesthetic:** Clean, professional, and functional UI/UX design. Utilize modern Flexbox Cards (`list-group-item`) over rigid HTML `&lt;table&gt;` structures.
* **Navigation:** Single Page Application (SPA) feel managed by `panel-tabs.php`. Uses Bootstrap Tabs with URL hash persistence (`#domains`, `#security`) to prevent full-page reloads.
* **Modals & Context-Aware UI:** Modals must auto-close upon successful AJAX. Contextual buttons inside data cards (e.g., "Proxy Settings") must trigger JavaScript to open the modal and *automatically pre-select/pre-fill* the relevant hidden inputs (`domain_name`) to eliminate repetitive user clicks.
* **Dictionary-Driven Rendering:** Raw backend database strings (like `update_proxy`) and JSON payloads must never be exposed to the user. JavaScript must map these actions to human-readable dictionaries (Icons, Titles, Descriptions).
* **Bulk Processing UI:** Forms dealing with multiple files or domains must use array inputs (e.g., `&lt;input name="targets[]"&gt;`) bound to master Select-All checkboxes and floating action toolbars.
* **Terminal/Console Output:** Live log viewers must behave natively. Newest database items render at the top; raw chronological `tail` logs render at the bottom with smooth auto-scroll.
* **Data Polling:** Live telemetry is updated via periodic `setInterval` AJAX loops. Background intervals MUST use the exact same UI identifiers as manual fetch buttons to prevent conflict overwrites.
* **Dynamic Helper UI:** Forms interacting with complex syntax (like BIND9 records) must dynamically inject helper warnings (e.g., trailing dot warnings for CNAMEs) based on active dropdown selections to guide user input and prevent backend failures.
* **Icon Version Strictness:** The panel utilizes Bootstrap Icons. Code generators must verify icon availability against the specific deployed version (e.g., v1.10.5) to prevent blank spaces in the UI matrix.

## 4. PHP Backend Coding Standards
* **Gatekeeper:** EVERY file in the `/ajax/` directory must require `security.php` on line 2.
* **Secure Sessions:** Sessions must strictly enforce `PANEL_SESSION` naming, `secure =&gt; true`, `httponly =&gt; true`, and `samesite =&gt; 'Strict'` to prevent Cross-Site Request Forgery (CSRF).
* **CSRF Unlocking:** Mandatory `HTTP_X_CSRF_TOKEN` validation. Sessions must immediately call `session_write_close();` after validation to unlock the session file and allow concurrent AJAX polling.
* **SSO Cryptography:** Inter-service authentication (e.g., Panel to File Manager) must rely on Time-Based HMAC-SHA256 tokens using the Tenant's specific `webhook_token` as the secret key.
* **Database:** Use the Singleton pattern `Database::getInstance()-&gt;getConnection()` with strict PDO Prepared Statements.
* **JSON Payload Parsing:** When PHP pulls JSON payloads from the database queue, it must natively decode and extract the primary target into a safe string.
* **JSON File Integrity:** When saving configuration states via `file_put_contents`, strictly use `JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES` and enforce `chown www-data`.
* **Binary/File Transfers:** Any PHP script outputting raw files (downloads, bulk zip files, raw image rendering) MUST call `ob_clean();` and `flush();` directly before `readfile()` to prevent buffer injection from corrupting payloads.
* **Bulk Action Engines:** PHP backends processing arrays (e.g., `targets[]`) must iterate using fast, native Linux OS commands (e.g., `zip`, `mv`, `rm -rf`) via `exec()`. Every single parameter MUST be explicitly wrapped in `escapeshellarg()` for directory traversal protection.
* **Global Subsystem States:** Essential server-wide integrations (like `mail_global_settings` for External SMTP Relays) should be tracked via single-row database configurations (`id=1`) to act as a frontend Source of Truth.

## 5. Bash Worker Script Guidelines
* **Execution:** All worker scripts (`/opt/panel/scripts/*.sh`) are executed by `worker.py` as `root`.
* **SRE Rollback Protocol (CRITICAL):** Any script that modifies Nginx or PHP configurations MUST create a `.bak` backup, run a syntax test (`nginx -t` or `php-fpm -t`), and **instantly rollback** to the `.bak` file if the syntax check fails, exiting with `1`. Do not allow typos to take the server offline.
* **Inputs:** They rely heavily on `jq` to parse the provided JSON payload (`PAYLOAD=$1`).
* **Source of Truth Synchronization:** State changes must be actively synchronized back to the MariaDB `panel_core` database upon success (`exit 0`).
* **IP Routing Map Sync:** Any script creating or destroying a domain MUST securely append or `sed` remove its mapping from `/etc/nginx/stackrium_tenant_map.conf` to maintain the fallback proxy logic.
* **Data Protection:** Scripts cloning from Git or fetching external APIs (like CDN IPs) must use temporary directories (`/tmp/`) and only `mv` the files to production paths if the payload is verified intact.
* **Zero-Trace Password Handling:** When bash scripts process API keys or passwords (like SMTP credentials), they must never use `echo "key" &gt; file` as it leaks to OS command histories (`ps aux`). They must use `cat &lt;&lt;EOF &gt; file` to bypass process tracking, followed by immediate cryptographic hashing (e.g., `postmap`) and strict permission locks (`chmod 0600`, `chown root:root`).
* **Memory Injection vs Text Substitution:** When configuring complex daemons (like Postfix), scripts should utilize native memory injection commands (e.g., `postconf -e` to add, `postconf -X` to explicitly strip/rollback) rather than relying on brittle `sed` string replacements inside master config files.
* **Daemon APT Suppression:** Any script utilizing `apt` package managers must enforce `export DEBIAN_FRONTEND=noninteractive` and configure `needrestart` to automatic mode in `/etc/needrestart/needrestart.conf` to prevent interactive OS UI blocks from hanging the Python background queue indefinitely.

## 6. Server Infrastructure & Application Deployments
* **Master Panel:** Runs strictly on Port `7443` over HTTPS. Panel rewrite rules must explicitly whitelist `/pma` (phpMyAdmin) to prevent `301` redirects from destroying background `POST` payloads.
* **IP Fallback Routing (The `~domain.com` Route):** A master Port 80/443 catch-all Nginx block allows accessing unpropagated domains via `http://&lt;IP&gt;/~domain.com/`. It utilizes a centralized tenant map (`stackrium_tenant_map.conf`) and proxies traffic through a secure PHP gateway (`fm_proxy.php`) to dynamically allocate correct document roots, verify SSO tokens, and enforce user boundaries.
* **Universal CDN/Proxy Manager:** Nginx must accurately identify real client IPs behind Cloudflare, Fastly, AWS CloudFront, and Sucuri to prevent "CDN Suicide" in Fail2ban. Global IP lists are synced via a weekly cron job. Domains include these dynamically via targeted HTTP headers (e.g., `CF-Connecting-IP`).
* **Native JSON Logging:** Nginx access logs are written exclusively in a highly structured `json_access` format. Fail2ban strictly relies on native JSON Regex parsing (`"status":\s*"444"`) to hunt malicious traffic.
* **Standard PHP:** Uses FastCGI with isolated, self-healing FPM pools generated per user (`/etc/php/8.x/fpm/pool.d/user.conf`).
* **Laravel / Node / Python:** Uses PM2. Nginx dynamically converts from FastCGI into a WebSocket-compatible Reverse Proxy.
* **Git Integrations:** Uses `sudo -u $USERNAME` to securely impersonate the client user utilizing their `id_ed25519` deploy keys.
* **ModSecurity (WAF):** Employs a dynamic branch architecture capable of hot-swapping between v3.3 and v4.x via background Git compilation. Limits must be explicitly set to 512MB (`SecRequestBodyLimit 536870912`) to prevent upload blockages.
* **Mail Server Engine:** Postfix and Dovecot operate as a multi-tenant MTA directly hooked into MariaDB (`panel_core.mail_users`).
* **External SMTP Relays:** Contains native integrations to bypass Cloud Provider hardware blocks on outbound Port 25. The system securely injects SASL API keys into Postfix and reroutes all outbound traffic through explicit TLS ports (587) using authenticated third-party networks (SendGrid, Brevo, AWS SES).
* **DNS Management (BIND9):** Acts as the authoritative nameserver with dynamic serial generation (`YYYYMMDDNN`). The panel enforces strict RFC standards in the UI—specifically requiring trailing dots (`.`) on external CNAME/MX routing aliases to prevent BIND9 from automatically appending the root domain. BIND9 zone files do not support inline editing; they are managed via a strict "Delete then Add" logical execution flow synchronized directly back to the database Source of Truth.