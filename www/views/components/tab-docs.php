<!-- /opt/panel/www/views/components/tab-docs.php -->
<div class="tab-pane fade" id="docs" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-book text-primary me-2"></i> oPanel Official User Manual</h4>
        <span class="badge bg-secondary fs-6">v1.0.0</span>
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
                        <li class="list-group-item border-0"><strong>Deploy Code:</strong> Return to <em>Web & Git</em> to install WordPress, deploy Node.js, clone a Git repo, or upload files via the File Manager.</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- SECTION 2: Web & Subdomains -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingWeb">
                <button class="accordion-button collapsed fw-bold bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWeb">
                    <i class="bi bi-globe me-2 text-success"></i> 2. Web, Domains & Subdomains
                </button>
            </h2>
            <div id="collapseWeb" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body text-sm bg-white">
                    <p>The <strong>Web & Git</strong> tab is the core engine for hosting applications. oPanel automatically configures Nginx and FastCGI Process Managers behind the scenes.</p>
                    
                    <h6 class="fw-bold mt-3 text-dark border-bottom pb-1">How to Create Subdomains</h6>
                    <p class="text-muted small">In oPanel's architecture, subdomains are treated exactly like root domains. They receive their own isolated Nginx configs, web roots, and SSL certificates.</p>
                    <ul class="text-muted small mb-4">
                        <li>Click <strong>New Domain</strong>.</li>
                        <li>In the Domain Name field, simply type the full subdomain (e.g., <code>api.example.com</code> or <code>store.yourdomain.in</code>).</li>
                        <li>Assign it to a user and click Create.</li>
                        <li><strong>DNS Note:</strong> You must create an A-Record for <code>api</code> pointing to this server at your domain registrar for it to become accessible online.</li>
                    </ul>

                    <h6 class="fw-bold mt-3 text-dark border-bottom pb-1">Application Deployment & Git</h6>
                    <ul class="text-muted small mb-3">
                        <li><strong>1-Click WordPress:</strong> Click the <i class="bi bi-wordpress"></i> icon. It auto-generates the database and installs the site. <em>Requires an empty public_html folder.</em></li>
                        <li><strong>Node.js Apps:</strong> Click the <i class="bi bi-hexagon-fill"></i> icon to deploy via PM2. oPanel routes traffic from Nginx to your app's internal port automatically.</li>
                        <li><strong>Git Repository Deployment:</strong> Click the <strong>Deploy Git Repo</strong> button.
                            <ul>
                                <li>oPanel uses SSH keys to authenticate with GitHub/GitLab securely.</li>
                                <li>Click <em>"View Key"</em> to get your server's public key, and add it to your repo's Deploy Keys.</li>
                                <li><strong>Webhooks (Auto-Deploy):</strong> Once cloned, oPanel provides a unique Webhook URL. Add this to your GitHub repository settings to enable automatic deployment every time you push code!</li>
                            </ul>
                        </li>
                    </ul>

                    <h6 class="fw-bold text-dark border-bottom pb-1">Software Center & PHP Management</h6>
                    <ul class="text-muted small mb-0">
                        <li><strong>Software Center:</strong> Click the <i class="bi bi-box-seam"></i> button to install new PHP versions (e.g., PHP 8.1, 8.2, 8.3) directly from the OS repositories.</li>
                        <li><strong>Change PHP Version:</strong> Once installed via the Software Center, click <strong>Change PHP</strong> to instantly switch a domain to a different PHP engine without downtime.</li>
                        <li><strong>Advanced PHP Settings:</strong> Click the <i class="bi bi-sliders"></i> icon next to a domain to modify <code>memory_limit</code>, <code>upload_max_filesize</code>, or tune the FPM Worker limits. Changes are applied instantly.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- SECTION 3: Users & Databases -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingDb">
                <button class="accordion-button collapsed fw-bold bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDb">
                    <i class="bi bi-people me-2 text-info"></i> 3. System Users & Databases
                </button>
            </h2>
            <div id="collapseDb" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body text-sm bg-white">
                    <p class="text-muted mb-3">Manage Linux system users and MariaDB/MySQL databases with granular access control.</p>
                    <ul class="text-muted small">
                        <li><strong>System Users:</strong> Linux users are jailed to their <code>/home/user/web/</code> directories. <em>Warning: Deleting a user destroys their entire home directory. You must delete their domains from the Web tab first.</em></li>
                        <li><strong>Databases:</strong> When provisioning a database, use <strong>Access Control</strong> to define security. Select <em>Localhost Only</em> for maximum security, or input a specific IP if you are connecting remotely from another server.</li>
                        <li><strong>phpMyAdmin:</strong> Click the green <i class="bi bi-database-fill-gear"></i> icon next to any database to securely launch phpMyAdmin. oPanel uses SSO, meaning you never have to type your database password.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- SECTION 4: Security & DNS -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingSecurity">
                <button class="accordion-button collapsed fw-bold bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSecurity">
                    <i class="bi bi-shield-check me-2 text-danger"></i> 4. Security, Firewall & DNS
                </button>
            </h2>
            <div id="collapseSecurity" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body text-sm bg-white">
                    <p class="text-muted mb-3">Protect your server and route traffic using the built-in BIND9 and UFW engines.</p>
                    <ul class="text-muted small">
                        <li><strong>Initialize New Zone:</strong> If you want oPanel to act as your Master DNS server, click this to generate the baseline BIND9 records (A, MX, TXT) automatically. <em>(Note: You must set ns1 and ns2 as custom nameservers at your registrar).</em></li>
                        <li><strong>Manage DNS:</strong> Add custom CNAME, TXT, or A records directly into your active BIND9 zones.</li>
                        <li><strong>Install SSL:</strong> Secure domains with Let's Encrypt. The script automatically solves the ACME challenge and reloads Nginx.</li>
                        <li><strong>WAF (ModSecurity):</strong> Toggle the Web Application Firewall on/off per domain directly from the Web tab to block SQL injections and malicious bots.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- SECTION 5: Backups -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingBackups">
                <button class="accordion-button collapsed fw-bold bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBackups">
                    <i class="bi bi-archive me-2 text-warning"></i> 5. Backups & Automation
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
        <!-- SECTION 6: Cron Jobs -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingCron">
                <button class="accordion-button collapsed fw-bold bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCron">
                    <i class="bi bi-clock-history me-2 text-secondary"></i> 6. Automated Tasks (Cron Jobs)
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

        <!-- SECTION 7: Mail Management -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingMail">
                <button class="accordion-button collapsed fw-bold bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMail">
                    <i class="bi bi-envelope-at me-2 text-primary"></i> 7. Mail Server & Routing
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

        <!-- SECTION 8: FAQ -->
        <div class="accordion-item border-0">
            <h2 class="accordion-header" id="headingFaq">
                <button class="accordion-button collapsed fw-bold bg-dark text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq">
                    <i class="bi bi-question-circle-fill me-2 text-info"></i> 6. Frequently Asked Questions (FAQ)
                </button>
            </h2>
            <div id="collapseFaq" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body text-sm bg-light">
                    
                    <h6 class="fw-bold text-dark mt-2">Q: I created a domain, but when I visit it in my browser, it says "Site cannot be reached."</h6>
                    <p class="text-muted mb-3 pb-2 border-bottom"><strong>A:</strong> This is almost always a DNS or Firewall issue. First, verify in your domain registrar (e.g., GoDaddy) that the A-Record points to your oPanel server IP. Second, ensure Port 80 and Port 443 are open in your cloud provider's network security group.</p>

                    <h6 class="fw-bold text-dark">Q: My Let's Encrypt SSL installation failed! Why?</h6>
                    <p class="text-muted mb-3 pb-2 border-bottom"><strong>A:</strong> Let's Encrypt must verify that you own the domain. If your DNS hasn't fully propagated globally, or if Cloudflare Proxy (the orange cloud) is turned on during installation, Let's Encrypt cannot verify the IP and will fail. Ensure DNS is propagated and proxying is disabled before retrying.</p>

                    <h6 class="fw-bold text-dark">Q: I get a "File too large" error when importing a database in phpMyAdmin.</h6>
                    <p class="text-muted mb-3 pb-2 border-bottom"><strong>A:</strong> By default, PHP limits uploads. Go to the <em>Users & DBs</em> tab, click <strong>Global Settings (Gear Icon)</strong> at the top right, and increase the Max Upload Size to 512MB. This applies instantly to the entire server.</p>

                    <h6 class="fw-bold text-dark">Q: How do I completely remove the Mail Engine to get my RAM back?</h6>
                    <p class="text-muted mb-3 pb-2 border-bottom"><strong>A:</strong> Open the Mail Modal for any domain. Scroll to the bottom of the "Host Locally" tab and click the red "Uninstall Engine" button. <em>Warning: This permanently deletes all local emails and removes Postfix/Dovecot from the system.</em></p>

                    <h6 class="fw-bold text-dark">Q: I changed the PHP settings for a domain, but the site isn't updating.</h6>
                    <p class="text-muted mb-0"><strong>A:</strong> Whenever you change advanced PHP settings (like <code>memory_limit</code>), oPanel automatically tests syntax and restarts the PHP-FPM worker for that domain. If it didn't reflect, check the <strong>Live Task Log</strong> on the Dashboard. If you entered invalid syntax, the server will block the reload to keep your site online.</p>

                    <h6 class="fw-bold text-dark mt-4">Q: How do I auto-deploy code from GitHub when I push?</h6>
                    <p class="text-muted mb-3 pb-2 border-bottom"><strong>A:</strong> First, use the "Deploy Git Repo" tool to clone your repository. Once cloned, oPanel will display a unique <strong>Webhook URL</strong> in the domain list. Go to your repository settings in GitHub, navigate to "Webhooks", click "Add Webhook", and paste that URL. Set the content type to <code>application/json</code>. Every time you push, GitHub will ping oPanel to pull the latest code automatically.</p>

                    <h6 class="fw-bold text-dark">Q: I need an older version of PHP for a legacy application. How do I get it?</h6>
                    <p class="text-muted mb-3 pb-2 border-bottom"><strong>A:</strong> Go to the <strong>Web & Git</strong> tab and click the <strong>Software Center</strong> button. You can install older engines (like PHP 7.4 or 8.0) there. Once the installation task completes, click <strong>Change PHP</strong> to assign the legacy engine specifically to your legacy domain. All other domains will remain on their current versions.</p>

                    <h6 class="fw-bold text-dark">Q: I enabled 2FA but lost my phone. How do I get back in?</h6>
                    <p class="text-muted mb-3 pb-2 border-bottom"><strong>A:</strong> You will need SSH access to the server. Log in to your terminal and simply run the command: <code>sudo opanel login</code>. The oPanel CLI will instantly generate a secure, one-time access link. Copy and paste that link into your browser to bypass the login screen. Once inside, go to <strong>System Settings</strong> to reset or disable your 2FA.</p>
                    
                </div>
            </div>
        </div>

    </div>
</div>