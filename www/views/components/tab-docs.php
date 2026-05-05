<!-- /opt/panel/www/views/components/tab-docs.php -->
<div class="tab-pane fade" id="docs" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-book text-primary me-2"></i> oPanel Official User Manual</h4>
        <span class="badge bg-secondary fs-6">v1.1.0</span>
    </div>

    <!-- The Core Workflow Warning -->
    <div class="alert alert-warning shadow-sm border-warning border-start-0 border-end-0 border-bottom-0 border-3 rounded-0 mb-4 pb-3 pt-3">
        <h6 class="alert-heading fw-bold"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Critical Cloud Prerequisite: Port Opening</h6>
        <p class="small mb-0 text-dark">oPanel strictly manages your server's internal firewall (UFW). However, if you are hosting on AWS, Google Cloud, DigitalOcean, or Azure, you <strong>MUST</strong> also open the following ports in your provider's external Security Group / Network Firewall:</p>
        <ul class="small mb-0 mt-2 text-dark font-monospace">
            <li><strong>TCP 80 & 443:</strong> Web Traffic (HTTP/HTTPS)</li>
            <li><strong>TCP 7443:</strong> oPanel Dashboard Access</li>
            <li><strong>TCP & UDP 53:</strong> BIND9 DNS Routing</li>
            <li><strong>TCP 20, 21, & 40000-50000:</strong> Pure-FTPd Access</li>
            <li><strong>TCP 22:</strong> SSH Server Access</li>
        </ul>
    </div>

    <!-- The Accordion Manual -->
    <div class="accordion shadow-sm border-0" id="manualAccordion">
        
        <!-- SECTION 1: Core Workflow -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingWorkflow">
                <button class="accordion-button fw-bold bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWorkflow">
                    <i class="bi bi-diagram-3-fill me-2 text-primary"></i> 1. The Core Workflow (Order of Operations)
                </button>
            </h2>
            <div id="collapseWorkflow" class="accordion-collapse collapse show" data-bs-parent="#manualAccordion">
                <div class="accordion-body text-sm bg-white">
                    <p class="text-muted mb-3">oPanel utilizes a strict permission and isolation architecture. To successfully get a website or app online, you must follow this exact order of operations:</p>
                    <ol class="mb-0 list-group list-group-numbered list-group-flush">
                        <li class="list-group-item border-0 pb-1"><strong>Create a System User:</strong> Go to the <em>Users & DBs</em> tab and create a Linux user. This isolates your website's files from other users on the server.</li>
                        <li class="list-group-item border-0 pb-1"><strong>Add the Domain:</strong> Go to the <em>Web & Git</em> tab, click "New Domain", and assign it to the user you just created. This generates the Nginx vHost and PHP pool.</li>
                        <li class="list-group-item border-0 pb-1"><strong>Point Your DNS:</strong> Before proceeding, ensure your domain (or subdomain) has an A-Record pointing to this server's public IP address globally.</li>
                        <li class="list-group-item border-0 pb-1"><strong>Install SSL (HTTPS):</strong> Go to the <em>Security & DNS</em> tab and issue a Let's Encrypt certificate. <em>(This will fail if Step 3 is incomplete).</em></li>
                        <li class="list-group-item border-0"><strong>Deploy Code:</strong> Return to <em>Web & Git</em> to deploy Laravel, Python, Node.js, clone a Git repo, or upload files via the File Manager.</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- SECTION 2: Web, App Engines & Deployment -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingWeb">
                <button class="accordion-button collapsed fw-bold bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWeb">
                    <i class="bi bi-cpu-fill me-2 text-success"></i> 2. Domains, App Engines & Deployment
                </button>
            </h2>
            <div id="collapseWeb" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body text-sm bg-white">
                    <p>The <strong>Web & Git</strong> tab is the core engine for hosting applications. oPanel automatically configures Nginx, FastCGI Process Managers, and PM2 background workers behind the scenes.</p>
                    
                    <h6 class="fw-bold mt-3 text-dark border-bottom pb-1">Framework-Specific Deployments</h6>
                    <ul class="text-muted small mb-3">
                        <li class="mb-2"><strong>Laravel Environment:</strong> When you click "Deploy Laravel", oPanel automatically reconfigures Nginx to point the document root to the <code>/public</code> directory. It also installs Composer dependencies. <em>Note: Laravel 11 requires SQLite. Ensure <code>php8.3-sqlite3</code> and <code>php8.3-xml</code> are installed via the Software Center.</em></li>
                        <li class="mb-2"><strong>Python (WSGI/ASGI):</strong> Python apps run in isolated virtual environments (<code>venv</code>). oPanel uses PM2 to keep the process running forever. Nginx is reconfigured as a <strong>Reverse Proxy</strong>, securely forwarding external port 80/443 traffic to your internal Python app.</li>
                        <li class="mb-2"><strong>Node.js (NPM):</strong> Deployed natively via PM2. You can specify your entry file (e.g., <code>server.js</code>) and internal port. PM2 ensures the app restarts automatically if it crashes or if the server reboots.</li>
                        <li class="mb-2"><strong>1-Click WordPress:</strong> Auto-generates the database and installs the CMS. <em>Requires an empty <code>public_html</code> folder.</em></li>
                    </ul>

                    <h6 class="fw-bold text-dark border-bottom pb-1">The "Revert to PHP" Safety Mechanism</h6>
                    <p class="text-muted small mb-3">If you want to uninstall Laravel, Python, or Node.js and go back to a standard website, use the <strong>Revert to PHP</strong> tool. This performs a "Scorched Earth" cleanup:</p>
                    <ul class="text-muted small mb-3">
                        <li>It securely targets and kills the specific PM2 background worker associated with the domain.</li>
                        <li>It strips the Reverse Proxy rules from Nginx and restores the standard FastCGI PHP execution blocks.</li>
                        <li><strong>Safety Net:</strong> If your folder is empty after reverting, oPanel automatically injects a default <code>index.html</code> template so your website doesn't show a blank "Invalid Response" error.</li>
                    </ul>

                    <h6 class="fw-bold mt-3 text-dark border-bottom pb-1">Git Repository Auto-Deployment</h6>
                    <ul class="text-muted small mb-4">
                        <li>oPanel enforces a <strong class="text-dark">"One User, One Identity"</strong> rule. Each system user gets one unique SSH Deploy Key. If deploying different private repos to different domains, create a new System User for each project.</li>
                        <li><strong>Webhooks:</strong> Once cloned, oPanel provides a unique Webhook URL. Add this to your GitHub/GitLab repository settings (Content type: <code>application/json</code>). Every code push will automatically trigger oPanel to pull the latest changes!</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- SECTION 3: Advanced Web Settings & Performance -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingAdvanced">
                <button class="accordion-button collapsed fw-bold bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdvanced">
                    <i class="bi bi-rocket-takeoff-fill me-2 text-danger"></i> 3. Advanced Web Settings & Redis
                </button>
            </h2>
            <div id="collapseAdvanced" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body text-sm bg-white">
                    <p class="text-muted mb-3">Optimize how Nginx handles traffic and accelerate database queries using In-Memory caching.</p>
                    
                    <h6 class="fw-bold mt-3 text-dark border-bottom pb-1">Nginx Routing Rules</h6>
                    <ul class="text-muted small mb-3">
                        <li class="mb-2"><strong>URL Redirects:</strong> Route old links to new destinations. Use <strong>301 (Permanent)</strong> to tell Google to update its search index, or <strong>302 (Temporary)</strong> if you are just doing maintenance. <em>Example: Redirect <code>/old-store</code> to <code>https://shop.domain.com</code>.</em></li>
                        <li class="mb-2"><strong>MIME Types:</strong> Nginx needs a dictionary to understand file types. If your users are trying to view an image or app file but it forces a "Download" instead, you need to add a MIME type. <em>Example: Ext: <code>apk</code>, MIME: <code>application/vnd.android.package-archive</code>.</em></li>
                        <li class="mb-2"><strong>Hotlink Protection:</strong> Stop other websites from stealing your bandwidth. When enabled, Nginx checks the <code>Referer</code> header. If another website embeds your images or videos, Nginx blocks them with a <code>403 Forbidden</code> error.</li>
                    </ul>

                    <h6 class="fw-bold mt-3 text-dark border-bottom pb-1">Redis In-Memory Caching</h6>
                    <p class="text-muted small mb-3">Redis stores frequently accessed database queries directly in your server's RAM. Because RAM is 100x faster than SSD storage, this drastically speeds up dynamic applications.</p>
                    <ul class="text-muted small mb-0">
                        <li><strong>Guardrails:</strong> oPanel hard-caps Redis at <strong>128MB</strong> of RAM and uses an LRU (Least Recently Used) eviction policy. This prevents Redis from causing an Out-Of-Memory (OOM) server crash.</li>
                        <li><strong>WordPress:</strong> Use the 1-Click "Enable Redis" button to automatically install the Redis Object Cache plugin and inject the secure credentials into your <code>wp-config.php</code>.</li>
                        <li><strong>Custom Apps:</strong> Click "Developer Guide" in the Redis tab to reveal the auto-generated secure password and get boilerplate connection code for PHP, Node, and Python.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- SECTION 4: Domain Suspension & Lifecycle -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingLifecycle">
                <button class="accordion-button collapsed fw-bold bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLifecycle">
                    <i class="bi bi-pause-circle-fill me-2 text-warning"></i> 4. Domain Suspension & Lifecycle
                </button>
            </h2>
            <div id="collapseLifecycle" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body text-sm bg-white">
                    <p class="text-muted mb-3">Administrators can temporarily pause web traffic to a domain without destroying its underlying data.</p>
                    <ul class="text-muted small">
                        <li class="mb-2"><strong>How Suspension Works:</strong> Clicking "Suspend" modifies the Nginx vHost configuration to intercept all incoming requests and instantly return a <code>503 Service Unavailable</code> header.</li>
                        <li class="mb-2"><strong>Non-Destructive:</strong> Suspension does NOT delete files, databases, or SSL certificates. The domain remains perfectly intact on the hard drive for a 1-click unsuspend later.</li>
                        <li class="mb-2"><strong>Custom Branding:</strong> While suspended, visitors are shown the <code>opanel_suspended.html</code> template. You can customize this file located in <code>/var/www/opanel_errors/</code> to match your organization's branding.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- SECTION 5: Users & Databases -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingDb">
                <button class="accordion-button collapsed fw-bold bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDb">
                    <i class="bi bi-people me-2 text-info"></i> 5. System Users & Databases
                </button>
            </h2>
            <div id="collapseDb" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body text-sm bg-white">
                    <p class="text-muted mb-3">Manage Linux system users and MariaDB/MySQL databases with granular access control.</p>
                    <ul class="text-muted small">
                        <li><strong>System Users:</strong> Linux users are jailed to their <code>/home/user/web/</code> directories. <em>Warning: Deleting a user destroys their entire home directory. You must delete their domains from the Web tab first.</em></li>
                        <li><strong>Databases:</strong> When provisioning a database, use <strong>Access Control</strong> to define security. Select <em>Localhost Only</em> for maximum security, or input a specific IP if you are connecting remotely from another server.</li>
                        <li><strong>phpMyAdmin:</strong> Click the green <i class="bi bi-database-fill-gear"></i> icon next to any database to securely launch phpMyAdmin. oPanel uses SSO, meaning you never have to type your database password.</li>
                        <li><strong>Role-Based Access:</strong> When building custom apps, you can restrict database users to specific permissions (e.g., <code>SELECT</code>, <code>INSERT</code> only) using the Custom Role option for added security.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- SECTION 6: Security, WAF & DNS -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingSecurity">
                <button class="accordion-button collapsed fw-bold bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSecurity">
                    <i class="bi bi-shield-check me-2 text-dark"></i> 6. Security, WAF & DNS
                </button>
            </h2>
            <div id="collapseSecurity" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body text-sm bg-white">
                    <p class="text-muted mb-3">Protect your server and route traffic using the built-in BIND9 and UFW engines.</p>
                    <ul class="text-muted small">
                        <li class="mb-2"><strong>WAF (ModSecurity):</strong> Toggle the Web Application Firewall on/off per domain. This protects against SQL injections, Cross-Site Scripting (XSS), and malicious bots by inspecting every incoming packet against the OWASP Core Rule Set.</li>
                        <li class="mb-2"><strong>Install SSL:</strong> Secure domains with Let's Encrypt. The script automatically solves the ACME challenge and reloads Nginx. Ensure DNS is fully propagated before attempting.</li>
                        <li class="mb-2"><strong>Initialize New Zone:</strong> If you want oPanel to act as your Master DNS server, click this to generate the baseline BIND9 records (A, MX, TXT) automatically. <em>(Note: You must set ns1/ns2 at your registrar).</em></li>
                        <li class="mb-2"><strong>Manage DNS:</strong> Add custom CNAME, TXT, or A records directly into your active BIND9 zones.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- SECTION 7: Backups -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingBackups">
                <button class="accordion-button collapsed fw-bold bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBackups">
                    <i class="bi bi-archive me-2 text-primary"></i> 7. Backups & Automation
                </button>
            </h2>
            <div id="collapseBackups" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body text-sm bg-white">
                    <p class="text-muted mb-3">Never lose your data. oPanel handles automated compression and SQL dumping via Python daemon workers.</p>
                    <ul class="text-muted small">
                        <li><strong>Manual Backups:</strong> Generate instant <code>.tar.gz</code> website file archives or <code>.sql.gz</code> database dumps.</li>
                        <li><strong>Auto-Schedule:</strong> Set up Daily, Weekly, or Monthly automated backups. Set a "Retention Limit" (e.g., 3 days) and oPanel will automatically delete older backups to prevent your disk from filling up.</li>
                        <li><strong>1-Click Restore:</strong> Click the red restore button next to an archive in the vault to instantly overwrite the live site/database with the backup data.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- SECTION 8: Cron Jobs -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingCron">
                <button class="accordion-button collapsed fw-bold bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCron">
                    <i class="bi bi-clock-history me-2 text-secondary"></i> 8. Automated Tasks (Cron Jobs)
                </button>
            </h2>
            <div id="collapseCron" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body text-sm bg-white">
                    <p class="text-muted mb-3">Automate repetitive server tasks like cache clearing, backups, or script execution without manual intervention.</p>
                    <ul class="text-muted small">
                        <li><strong>Add Cron Job:</strong> Schedule commands to run as specific system users. oPanel uses standard cron syntax <code>* * * * *</code> (Minute, Hour, Day, Month, Weekday).</li>
                        <li><strong>Pro-Tip (Laravel):</strong> To run a Laravel scheduler every minute, set all time fields to <code>*</code> and use the command: <br><code>php /home/user/web/domain.com/public_html/artisan schedule:run</code></li>
                        <li><strong>System Time:</strong> Remember that cron jobs execute based on the server's Master Time Zone, which you can configure in the Dashboard's System Settings.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- SECTION 9: Mail Management -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingMail">
                <button class="accordion-button collapsed fw-bold bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMail">
                    <i class="bi bi-envelope-at me-2 text-info"></i> 9. Mail Server & Routing
                </button>
            </h2>
            <div id="collapseMail" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body text-sm bg-white">
                    <p class="text-muted mb-3">oPanel uses a Modular Mail Architecture. By default, the mail engine is completely uninstalled to save your server's RAM and CPU.</p>
                    <ul class="text-muted small mb-0">
                        <li><strong>Local Mail Engine:</strong> If you want to host physical emails on the server, click the Mail icon next to any domain and click "Install Mail Engine". This downloads Postfix and Dovecot in the background.</li>
                        <li><strong>External Routing (Recommended):</strong> If you use Google Workspace or Microsoft 365, do <strong>not</strong> install the local engine. Simply use the "External Provider" tab in the Mail Modal for 1-Click DNS setup.</li>
                        <li><strong>Webmail:</strong> If hosting locally, you can access your inbox by navigating to <code>https://webmail.yourdomain.com</code>.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- SECTION 10: FAQ -->
        <div class="accordion-item border-0">
            <h2 class="accordion-header" id="headingFaq">
                <button class="accordion-button collapsed fw-bold bg-dark text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq">
                    <i class="bi bi-question-circle-fill me-2 text-warning"></i> 10. Frequently Asked Questions (FAQ)
                </button>
            </h2>
            <div id="collapseFaq" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body text-sm bg-light">
                    
                    <h6 class="fw-bold text-dark mt-2">Q: When I visit my domain, it downloads an "octet-stream" file instead of showing the website.</h6>
                    <p class="text-muted mb-3 pb-2 border-bottom"><strong>A:</strong> Nginx has lost its MIME types mapping. Go to <strong>Advanced Web Settings</strong> and add a MIME type, or ensure your master Nginx template includes the <code>include /etc/nginx/mime.types;</code> directive.</p>

                    <h6 class="fw-bold text-dark mt-2">Q: I created a domain, but when I visit it in my browser, it says "Site cannot be reached."</h6>
                    <p class="text-muted mb-3 pb-2 border-bottom"><strong>A:</strong> This is almost always a DNS or Firewall issue. First, verify in your domain registrar (e.g., GoDaddy) that the A-Record points to your oPanel server IP. Second, ensure Port 80 and Port 443 are open in your cloud provider's network security group.</p>

                    <h6 class="fw-bold text-dark">Q: My Let's Encrypt SSL installation failed! Why?</h6>
                    <p class="text-muted mb-3 pb-2 border-bottom"><strong>A:</strong> Let's Encrypt must verify that you own the domain. If your DNS hasn't fully propagated globally, or if Cloudflare Proxy (the orange cloud) is turned on during installation, Let's Encrypt cannot verify the IP and will fail. Ensure DNS is propagated and proxying is disabled before retrying.</p>

                    <h6 class="fw-bold text-dark">Q: My PM2 App (Python/Node) deployment shows "Errored" in Live Tasks.</h6>
                    <p class="text-muted mb-3 pb-2 border-bottom"><strong>A:</strong> This usually means the port your application is trying to use is already bound to another process, or your code has a fatal syntax error. Use the File Manager to check your application's internal logs, and verify the correct port is set in your Node <code>server.js</code> or Python <code>app.py</code>.</p>

                    <h6 class="fw-bold text-dark">Q: Can I use multiple Git repositories or generate multiple SSH Deploy Keys for a single user?</h6>
                    <p class="text-muted mb-3 pb-2 border-bottom"><strong>A:</strong> For strict isolation, oPanel enforces a <strong>"One User, One Identity"</strong> rule. Each system user is assigned exactly one unique ED25519 SSH Deploy Key. If you are managing multiple domains that require different Git repositories, you must provision a new User in the <strong>Users</strong> tab. This ensures that if one website is compromised, the attacker cannot use that user's SSH key to access your other repositories.</p>

                    <h6 class="fw-bold text-dark">Q: I get a "File too large" error when importing a database in phpMyAdmin.</h6>
                    <p class="text-muted mb-3 pb-2 border-bottom"><strong>A:</strong> By default, PHP limits uploads. You can increase the global Max Upload Size to 512MB in System Settings. However, for massive SQL files (over 512MB), do not use phpMyAdmin. Upload the `.sql` file via File Manager and use the terminal: <br><code>mysql -u db_user -p database_name < database.sql</code></p>

                    <h6 class="fw-bold text-dark">Q: How do I completely remove the Mail Engine to get my RAM back?</h6>
                    <p class="text-muted mb-3 pb-2 border-bottom"><strong>A:</strong> Open the Mail Modal for any domain. Scroll to the bottom of the "Host Locally" tab and click the red "Uninstall Engine" button. <em>Warning: This permanently deletes all local emails and removes Postfix/Dovecot from the system.</em></p>

                    <h6 class="fw-bold text-dark">Q: I changed the PHP settings for a domain, but the site isn't updating.</h6>
                    <p class="text-muted mb-3 pb-2 border-bottom"><strong>A:</strong> Whenever you change advanced PHP settings (like <code>memory_limit</code>), oPanel automatically tests syntax and restarts the PHP-FPM worker for that domain. If it didn't reflect, check the <strong>Live Task Log</strong>. If you entered invalid syntax, the server will block the reload to keep your site online.</p>

                    <h6 class="fw-bold text-dark">Q: I need an older version of PHP for a legacy application. How do I get it?</h6>
                    <p class="text-muted mb-3 pb-2 border-bottom"><strong>A:</strong> Go to the <strong>Web & Git</strong> tab and click the <strong>Software Center</strong>. Install older engines (like PHP 7.4) there. Once installed, click <strong>PHP Config</strong> on your domain to instantly assign the legacy engine. All other domains will remain on their modern versions.</p>

                    <h6 class="fw-bold text-dark">Q: I enabled 2FA but lost my phone. How do I get back in?</h6>
                    <p class="text-muted mb-0"><strong>A:</strong> You will need SSH access to the server. Log in to your terminal and simply run the command: <code>sudo opanel login</code>. The oPanel CLI will instantly generate a secure, one-time access link. Copy and paste that link into your browser to bypass the login screen. Once inside, go to <strong>System Settings</strong> to reset or disable your 2FA.</p>
                    
                </div>
            </div>
        </div>

    </div>
</div>