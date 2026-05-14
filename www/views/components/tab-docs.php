<div class="tab-pane fade" id="docs" role="tabpanel">
    <div class="row g-4 mb-4">
        <div class="col-12">
            <h4 class="fw-bold mb-0"><i class="bi bi-journal-text text-primary me-2"></i> Documentation & Guide</h4>
            <p class="text-muted mt-1">Learn how to manage domains, deploy apps, configure firewalls, schedule backups, and use Stackrium effectively.</p>
        </div>
    </div>

    <div class="alert alert-warning shadow-sm border-warning border-start-0 border-end-0 border-bottom-0 border-3 rounded-0 mb-4 pb-3 pt-3">
        <h6 class="alert-heading fw-bold"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Critical Cloud Prerequisite: Port Opening</h6>
        <p class="small mb-0 text-dark">Stackrium strictly manages your server's internal firewall (UFW). However, if you are hosting on AWS, Google Cloud, DigitalOcean, or Azure, you <strong>MUST</strong> also open the following ports in your provider's external Security Group / Network Firewall:</p>
        <ul class="small mb-0 mt-2 text-dark font-monospace">
            <li><strong>TCP 80 & 443:</strong> Web Traffic (HTTP/HTTPS)</li>
            <li><strong>TCP 7443:</strong> Stackrium Dashboard Access</li>
            <li><strong>TCP & UDP 53:</strong> BIND9 DNS Routing</li>
            <li><strong>TCP 20, 21, & 40000-50000:</strong> Pure-FTPd Access</li>
            <li><strong>TCP 22:</strong> SSH Server Access</li>
        </ul>
    </div>

    <div class="row g-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 position-sticky" style="top: 20px;">
                <div class="card-body p-0">
                    <div class="list-group list-group-flush rounded" id="docs-list" role="tablist">
                        <a class="list-group-item list-group-item-action active fw-bold py-3" data-bs-toggle="list" href="#doc-domains" role="tab">
                            <i class="bi bi-globe me-2 text-primary"></i> 1. Domains & Subdomains
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-ftp" role="tab">
                            <i class="bi bi-folder2-open me-2 text-warning"></i> 2. FTP & File Access
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-db-backups" role="tab">
                            <i class="bi bi-database me-2 text-success"></i> 3. Databases & Backups
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-dns" role="tab">
                            <i class="bi bi-diagram-2 me-2 text-info"></i> 4. DNS Configuration
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-git" role="tab">
                            <i class="bi bi-git me-2 text-danger"></i> 5. Git Deployment
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-pm2" role="tab">
                            <i class="bi bi-cpu me-2 text-success"></i> 6. Node.js & PM2
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-security" role="tab">
                            <i class="bi bi-shield-lock me-2 text-dark"></i> 7. Security & WAF
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-mail" role="tab">
                            <i class="bi bi-envelope me-2 text-primary"></i> 8. Mail Server
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-cron" role="tab">
                            <i class="bi bi-clock-history me-2 text-secondary"></i> 9. Cron Jobs
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-updates" role="tab">
                            <i class="bi bi-cloud-arrow-down me-2 text-info"></i> 10. System Updates
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-logs" role="tab">
                            <i class="bi bi-terminal me-2 text-dark"></i> 11. Logs & Tasks
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-faq" role="tab">
                            <i class="bi bi-question-circle me-2 text-secondary"></i> 12. Common FAQ
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-license" role="tab">
                            <i class="bi bi-award me-2 text-primary"></i> 13. Commercial License
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <div class="tab-content" id="nav-tabContent">
                        
                        <div class="tab-pane fade show active" id="doc-domains" role="tabpanel">
                            <h2 class="fw-bold mb-4">Domains, Subdomains & Status</h2>
                            <p class="fs-6 mb-4">Stackrium automatically handles Nginx configurations, permissions, and DNS zone creation when you deploy a new environment.</p>
                            
                            <h5 class="fw-bold mt-4"><i class="bi bi-plus-circle text-primary me-2"></i> Adding a Root Domain</h5>
                            <ol class="list-group list-group-numbered list-group-flush mb-4">
                                <li class="list-group-item bg-transparent border-0 py-2">Go to the <b>Websites</b> tab and click "New Domain".</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Enter your domain (e.g., <code>example.com</code>). Select the PHP version and assign it to a Linux User.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Stackrium generates the Nginx vHost, dedicated PHP-FPM pool, and a primary BIND9 DNS Zone for the domain.</li>
                            </ol>

                            <h5 class="fw-bold mt-4"><i class="bi bi-diagram-3 text-primary me-2"></i> Creating Subdomains</h5>
                            <p>Subdomains are not just aliases; they are fully isolated environments with their own dedicated <code>public_html</code> directories and PHP pools.</p>
                            <ol class="list-group list-group-numbered list-group-flush mb-4">
                                <li class="list-group-item bg-transparent border-0 py-2">Click "New Domain" and check the <b>"Is Subdomain"</b> box.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Enter the prefix (e.g., <code>api</code>) and select the Parent Domain from the dropdown.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Stackrium automatically injects the new A-Records directly into the parent domain's BIND9 zone file and increments the DNS serial safely.</li>
                                <li class="list-group-item bg-transparent border-0 py-2"><strong>Security Note:</strong> The backend strictly verifies that your Linux user actually owns the parent domain before allowing the subdomain to be provisioned.</li>
                            </ol>

                            <h5 class="fw-bold mt-4"><i class="bi bi-pause-circle text-warning me-2"></i> Suspending Domains</h5>
                            <p>If a domain is consuming too many resources or payment is overdue, you can instantly suspend it.</p>
                            <ul class="list-unstyled mb-4 text-muted">
                                <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i> Go to <b>Websites</b> and click the Status toggle next to the domain.</li>
                                <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i> Stackrium intercepts the Nginx traffic and redirects all visitors to a 503 "Service Unavailable" page.</li>
                                <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i> It is completely non-destructive (files and DBs remain intact) and can be unsuspended instantly.</li>
                            </ul>

                            <h5 class="fw-bold mt-4"><i class="bi bi-filetype-php text-info me-2"></i> Changing PHP Versions (Hot-Swap)</h5>
                            <p>You can hot-swap the PHP version of any domain without taking the server offline.</p>
                            <ul class="list-unstyled mb-4 text-muted">
                                <li class="mb-2"><i class="bi bi-arrow-right text-muted me-2"></i> Click <b>Change PHP</b> in the Websites tab.</li>
                                <li class="mb-2"><i class="bi bi-arrow-right text-muted me-2"></i> Select the new version. Stackrium safely rewrites the Nginx FastCGI pass and reloads the worker pool.</li>
                                <li class="mb-2"><i class="bi bi-arrow-right text-muted me-2"></i> Need older versions? Click <b>Software Center</b> to globally install legacy engines like PHP 7.4 or 8.1.</li>
                            </ul>
                        </div>

                        <div class="tab-pane fade" id="doc-ftp" role="tabpanel">
                            <h2 class="fw-bold mb-4">FTP & File Management</h2>
                            <p class="fs-6 mb-4">Stackrium uses Pure-FTPd for secure, isolated file access. Every domain gets its own FTP account.</p>

                            <h5 class="fw-bold"><i class="bi bi-hdd-network text-warning me-2"></i> Connection Details</h5>
                            <div class="bg-light p-3 rounded mb-4 border">
                                <p class="mb-1"><b>Host:</b> Your server IP or Domain Name</p>
                                <p class="mb-1"><b>Port:</b> 21</p>
                                <p class="mb-1"><b>Encryption:</b> Require explicit FTP over TLS (Recommended)</p>
                            </div>

                            <h5 class="fw-bold mt-4"><i class="bi bi-key text-warning me-2"></i> Creating/Resetting Passwords</h5>
                            <ol class="list-group list-group-numbered list-group-flush mb-4">
                                <li class="list-group-item bg-transparent border-0 py-2">Go to <b>Websites > Manage (Gear Icon)</b> for your domain.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Navigate to the <b>FTP Access</b> tab.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Type a new secure password and click "Update FTP Password".</li>
                                <li class="list-group-item bg-transparent border-0 py-2">The username is displayed on that screen (format: <code>user_domain_com</code>).</li>
                            </ol>
                        </div>

                        <div class="tab-pane fade" id="doc-db-backups" role="tabpanel">
                            <h2 class="fw-bold mb-4">Databases & Backups</h2>
                            <p class="fs-6 mb-4">Manage your MySQL databases and configure automated backup schedules to protect your data.</p>

                            <h5 class="fw-bold text-success"><i class="bi bi-database me-2"></i> MySQL Management</h5>
                            <ol class="list-group list-group-numbered list-group-flush mb-4">
                                <li class="list-group-item bg-transparent border-0 py-2">Go to the <b>Databases</b> tab to create new databases and assign users.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">To manage tables and data, click the <b>phpMyAdmin</b> button in the top navigation bar.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Stackrium utilizes SSO (Single Sign-On), meaning you securely log in as root without typing passwords.</li>
                            </ol>

                            <h5 class="fw-bold text-success mt-4"><i class="bi bi-cloud-arrow-up me-2"></i> Automated Backups</h5>
                            <p>Stackrium can automatically back up your website files and databases safely in the background.</p>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i> Go to the <b>Backups</b> tab.</li>
                                <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i> Select whether to backup Web Files or a Database.</li>
                                <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i> Choose the frequency (Daily, Weekly, Monthly) and your desired retention policy.</li>
                                <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i> Backups are stored securely on the server at <code>/opt/panel/backups/</code>.</li>
                            </ul>
                        </div>

                        <div class="tab-pane fade" id="doc-dns" role="tabpanel">
                            <h2 class="fw-bold mb-4">DNS Configuration (BIND9)</h2>
                            <p class="fs-6 mb-4">If you want this server to act as its own Nameserver (e.g., <code>ns1.yourdomain.com</code>), use the DNS tab.</p>

                            <h5 class="fw-bold text-info"><i class="bi bi-cloud-plus me-2"></i> Creating a DNS Zone</h5>
                            <p>To start managing DNS records, you must create a Master Zone:</p>
                            <ol class="list-group list-group-numbered list-group-flush mb-4">
                                <li class="list-group-item bg-transparent border-0 py-2">Go to the <b>DNS Management</b> tab.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Click "Add DNS Zone".</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Enter the domain. BIND9 will automatically generate standard A, CNAME, and MX records pointing to this server.</li>
                            </ol>

                            <div class="alert alert-warning shadow-sm border-warning border-start border-4">
                                <strong>Glue Records:</strong> For custom nameservers to work globally, you must log into your Domain Registrar (Godaddy, Namecheap, etc.) and create "Glue Records" pointing <code>ns1</code> and <code>ns2</code> to this server's IP address.
                            </div>
                        </div>

                        <div class="tab-pane fade" id="doc-git" role="tabpanel">
                            <h2 class="fw-bold mb-4">Git Deployment</h2>
                            <p class="fs-6 mb-4">Deploy code directly from GitHub, GitLab, or Bitbucket without using FTP.</p>

                            <h5 class="fw-bold text-danger"><i class="bi bi-github me-2"></i> How to Deploy</h5>
                            <ol class="list-group list-group-numbered list-group-flush mb-4">
                                <li class="list-group-item bg-transparent border-0 py-2">Ensure your repository is <b>Public</b>, or provide a URL with a Personal Access Token (PAT).</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Go to <b>Websites > Deploy Git Repo</b>.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Paste the HTTPS clone URL (e.g., <code>https://github.com/user/repo.git</code>).</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Enter the branch name (usually <code>main</code> or <code>master</code>).</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Click Pull Repository.</li>
                            </ol>

                            <div class="alert alert-secondary shadow-sm">
                                <i class="bi bi-lightning-fill text-warning me-2"></i>
                                <strong>Automatic Webhooks:</strong> After cloning, Stackrium provides a Webhook URL. Paste this into GitHub's webhook settings to trigger automatic deployments every time you push code!
                            </div>
                        </div>

                        <div class="tab-pane fade" id="doc-pm2" role="tabpanel">
                            <h2 class="fw-bold mb-4">Node.js & PM2 Management</h2>
                            <p class="fs-6 mb-4">Stackrium includes global PM2 installation to keep Node.js, Python, or background workers running permanently.</p>

                            <h5 class="fw-bold text-success"><i class="bi bi-play-circle me-2"></i> Adding a Process</h5>
                            <ol class="list-group list-group-numbered list-group-flush mb-4">
                                <li class="list-group-item bg-transparent border-0 py-2">Go to the <b>Node.js & PM2</b> tab.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Enter the full absolute path to the script (e.g., <code>/home/user/web/domain.com/server.js</code>).</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Click "Start Process". PM2 will launch it and add it to the startup script.</li>
                            </ol>

                            <h5 class="fw-bold text-success mt-4"><i class="bi bi-globe me-2"></i> Nginx Reverse Proxy for Node.js</h5>
                            <p>If your Node app runs on a specific port (e.g., 3000) and you want it accessible via a domain name (e.g., example.com):</p>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="bi bi-arrow-right text-muted me-2"></i> Add the domain in the <b>Websites</b> tab. Select <b>Node.js</b> as the App Type.</li>
                                <li class="mb-2"><i class="bi bi-arrow-right text-muted me-2"></i> Enter the port number your app is using.</li>
                                <li class="mb-2"><i class="bi bi-arrow-right text-muted me-2"></i> Nginx will automatically proxy all traffic from port 80/443 to your local app port!</li>
                            </ul>
                        </div>

                        <div class="tab-pane fade" id="doc-security" role="tabpanel">
                            <h2 class="fw-bold mb-4">Security, WAF & Firewalls</h2>
                            <p class="fs-6 mb-4">Stackrium is built with defense-in-depth architecture.</p>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <div class="p-3 border rounded h-100 bg-light">
                                        <h5 class="fw-bold"><i class="bi bi-shield-check text-success me-2"></i> UFW Firewall</h5>
                                        <p class="small text-muted mb-0">Manages open ports. By default, SSH (22), HTTP (80), HTTPS (443), FTP (21, 40000-50000), DNS (53), and Panel (7443) are open. Add custom ports via the Firewall tab.</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 border rounded h-100 bg-light">
                                        <h5 class="fw-bold"><i class="bi bi-shield-slash text-danger me-2"></i> Fail2Ban</h5>
                                        <p class="small text-muted mb-0">Monitors logs. If an IP fails SSH, FTP, or Panel login 5 times in 10 minutes, Fail2Ban automatically blocks the IP in the UFW firewall for 1 hour.</p>
                                    </div>
                                </div>
                            </div>

                            <h5 class="fw-bold"><i class="bi bi-shield-lock text-dark me-2"></i> ModSecurity WAF</h5>
                            <p>The Web Application Firewall protects PHP apps from SQL Injection, XSS, and botnets. You can toggle it per-domain in the <b>Websites > SSL & Security</b> settings.</p>
                            
                            <h5 class="fw-bold mt-4"><i class="bi bi-braces text-dark me-2"></i> Custom WAF Rules & Dry-Runs</h5>
                            <p class="text-muted">You can write custom ModSecurity rules directly from the dashboard to whitelist IPs or block specific payloads.</p>
                            <div class="alert alert-success border-0 shadow-sm mb-4">
                                <strong>The Safety Mechanism:</strong> When you submit a custom rule, Stackrium creates a backup and performs a strict <code>nginx -t</code> syntax dry-run in the background. If your custom rule contains a typo, the system instantly aborts the save and restores the backup to prevent your server from crashing!
                            </div>

                            <hr class="my-5">
                            <h5 class="fw-bold mt-4"><i class="bi bi-file-earmark-lock text-success me-2"></i> SSL & Advanced Routing</h5>
                            <p class="text-muted">Stackrium syncs directly with live Nginx and Certbot configurations to support automated Let's Encrypt renewals as well as Custom/Cloudflare Origin certificates.</p>
                            <ul class="list-unstyled mb-4 text-muted">
                                <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i> <b>Auto-Renewal:</b> Toggle Let's Encrypt system timers natively.</li>
                                <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i> <b>Force HTTPS:</b> Injects a permanent <code>301 Redirect</code> safely into Port 80.</li>
                            </ul>

                            <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-start border-4 mt-3 mb-1 p-3">
                                <h6 class="fw-bold text-danger mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Critical Warning: HSTS (Strict-Transport-Security)</h6>
                                <p class="mb-0 text-dark" style="font-size: 0.85rem;">
                                    When you enable HSTS, you command web browsers to <strong>never</strong> load your website over an insecure HTTP connection for the duration of the <code>max-age</code> (up to 2 years). <br><br>
                                    <strong>Do not enable HSTS unless you are 100% certain you will maintain a valid SSL certificate.</strong> If your SSL expires or is removed while HSTS is cached in a user's browser, they will be permanently locked out of your site with a non-bypassable security error until the cache expires.
                                </p>
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="doc-mail" role="tabpanel">
                            <h2 class="fw-bold mb-4">Mail Server (Postfix/Dovecot)</h2>
                            <p class="fs-6 mb-4">Stackrium includes a full-featured Mail Transfer Agent (MTA) allowing you to host your own domain email (e.g., hello@yourdomain.com).</p>

                            <h5 class="fw-bold text-primary"><i class="bi bi-envelope-plus me-2"></i> Setting up a Mail Domain</h5>
                            <ol class="list-group list-group-numbered list-group-flush mb-4">
                                <li class="list-group-item bg-transparent border-0 py-2">Go to the <b>Mail Server</b> tab.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Click "Add Mail Domain" and enter your domain name.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Stackrium will configure Postfix & Dovecot for this domain automatically.</li>
                            </ol>

                            <h5 class="fw-bold text-primary"><i class="bi bi-person-badge me-2"></i> Creating Email Accounts</h5>
                            <p>Once a domain is added, click the <b>Accounts</b> button next to the domain name. You can create mailboxes (e.g., admin@yourdomain.com) and set storage quotas.</p>

                            <div class="alert alert-warning border-0 shadow-sm d-flex mt-4">
                                <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-warning"></i>
                                <div>
                                    <strong>Crucial DNS Requirements for Mail Delivery:</strong>
                                    <p class="mb-1 mt-2 small">To prevent your emails from going to Spam, you <b>must</b> configure these DNS records for your domain:</p>
                                    <ul class="mb-0 small pl-3">
                                        <li><b>MX Record:</b> Pointing to `mail.yourdomain.com`</li>
                                        <li><b>A Record:</b> `mail.yourdomain.com` pointing to this server's IP.</li>
                                        <li><b>TXT (SPF):</b> `v=spf1 mx a ip4:YOUR_SERVER_IP ~all`</li>
                                        <li><b>TXT (DMARC):</b> `v=DMARC1; p=quarantine;`</li>
                                    </ul>
                                </div>
                            </div>
                            
                             <h5 class="fw-bold text-primary mt-4"><i class="bi bi-pc-display me-2"></i> Email Client Settings</h5>
                             <div class="bg-light p-3 rounded border">
                                 <p class="mb-1"><b>Incoming Server (IMAP):</b> mail.yourdomain.com (Port 993, SSL/TLS)</p>
                                 <p class="mb-1"><b>Incoming Server (POP3):</b> mail.yourdomain.com (Port 995, SSL/TLS)</p>
                                 <p class="mb-1"><b>Outgoing Server (SMTP):</b> mail.yourdomain.com (Port 465, SSL/TLS) - <i>Requires Authentication</i></p>
                                 <p class="mb-0"><b>Username:</b> Your full email address.</p>
                             </div>
                        </div>

                        <div class="tab-pane fade" id="doc-cron" role="tabpanel">
                            <h2 class="fw-bold mb-4">Scheduled Tasks (Cron Jobs)</h2>
                            <p class="fs-6 mb-4">Automate repetitive server tasks by scheduling scripts to run at specific intervals without manual intervention.</p>

                            <h5 class="fw-bold text-secondary"><i class="bi bi-clock-history me-2"></i> Creating a Cron Job</h5>
                            <ol class="list-group list-group-numbered list-group-flush mb-4">
                                <li class="list-group-item bg-transparent border-0 py-2">Navigate to the <b>Cron Jobs</b> tab.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Define the schedule using standard Cron syntax (Minute, Hour, Day, Month, Weekday).</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Enter the full command or absolute path to your script (e.g., <code>/usr/bin/php /home/user/web/domain.com/public_html/artisan schedule:run</code>).</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Select the executing user (to ensure proper permissions) and save.</li>
                            </ol>
                        </div>

                        <div class="tab-pane fade" id="doc-updates" role="tabpanel">
                            <h2 class="fw-bold mb-4">Stackrium Updates & Migrations</h2>
                            <p class="fs-6 mb-4">Stackrium features a self-healing, enterprise-grade Auto-Update Engine to keep your server secure and up-to-date with zero downtime.</p>

                            <h5 class="fw-bold text-info"><i class="bi bi-arrow-repeat me-2"></i> Autonomous Nightly Updates</h5>
                            <ul class="list-unstyled mb-4 text-muted">
                                <li class="mb-2"><i class="bi bi-check2 text-info me-2"></i> <b>The 4:00 AM Cron:</b> Every day at 4:00 AM server time, Stackrium autonomously pings the central API for new releases.</li>
                                <li class="mb-2"><i class="bi bi-check2 text-info me-2"></i> <b>Staggered Rollouts:</b> To ensure maximum stability and prevent global server crashes, automatic updates are strictly staggered. Your server may safely wait 1-3 days after a global release before pulling the trigger.</li>
                                <li class="mb-2"><i class="bi bi-check2 text-info me-2"></i> <b>Safe Database Migrations:</b> When an update requires a schema change, Stackrium automatically runs the pending SQL migrations during the background Rsync process.</li>
                                <li class="mb-2"><i class="bi bi-check2 text-info me-2"></i> <b>Toggle Updates:</b> You can disable auto-updates in the <b>Settings</b> tab if you prefer to manage version control manually.</li>
                            </ul>

                            <h5 class="fw-bold text-info mt-4"><i class="bi bi-cloud-download me-2"></i> Manual Override</h5>
                            <p>You can force a check for the latest version and bypass the staggered rollout at any time:</p>
                            <ol class="list-group list-group-numbered list-group-flush mb-4">
                                <li class="list-group-item bg-transparent border-0 py-2">Go to the <b>License & Updates</b> tab.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Click <b>"Fetch Updates"</b>.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">If an update is available, you will see an "Install Now" button with a live progress bar tracking the bash executor.</li>
                            </ol>
                        </div>

                        <div class="tab-pane fade" id="doc-logs" role="tabpanel">
                            <h2 class="fw-bold mb-4">Logs & Live Tasks</h2>
                            <p class="fs-6 mb-4">Stackrium utilizes a dual-context architecture to separate global system health from tenant-level website tracking.</p>

                            <h5 class="fw-bold text-dark"><i class="bi bi-terminal me-2"></i> The Universal Log Router</h5>
                            <ul class="list-unstyled mb-4 text-muted">
                                <li class="mb-2"><i class="bi bi-cpu text-primary me-2"></i> <b>System Logs (Overview Tab):</b> Streams global OS logs, including the Python Daemon (<code>worker.py</code>), Fail2ban firewall bans, and system updater logs.</li>
                                <li class="mb-2"><i class="bi bi-journal-code text-info me-2"></i> <b>Website Logs (Domain Tab):</b> Context-aware. Instantly streams Nginx access and error logs specific to that tenant's directory without needing to select paths manually.</li>
                            </ul>

                            <h5 class="fw-bold text-dark mt-4"><i class="bi bi-list-task me-2"></i> Dictionary-Driven Tasks</h5>
                            <p class="text-muted">The task queue intercepts raw database JSON payloads and translates them into human-readable actions using a UI Dictionary. Raw backend payloads (e.g., <code>update_waf</code>) are never exposed directly to the end-user.</p>
                        </div>

                        <div class="tab-pane fade" id="doc-faq" role="tabpanel">
                            <h2 class="fw-bold mb-4">Frequently Asked Questions</h2>
                            
                            <div class="accordion accordion-flush" id="faqAccordion">
                                
                                <div class="accordion-item bg-transparent">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button bg-light fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                            Where are my website files located?
                                        </button>
                                    </h2>
                                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            All websites are stored securely within the user's home directory. The exact document root is <code>/home/{username}/web/{domain.com}/public_html</code>.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item bg-transparent border-top mt-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-light fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                            Do subdomains share FTP accounts with the main domain?
                                        </button>
                                    </h2>
                                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            Yes! Because subdomains are assigned to a specific Linux User during creation, that user's main FTP credentials will have access to the subdomain's directory as well.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item bg-transparent border-top mt-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-light fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq_mime">
                                            When I visit my domain, it downloads an "octet-stream" file.
                                        </button>
                                    </h2>
                                    <div id="faq_mime" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            Nginx has lost its MIME types mapping. Go to <strong>Advanced Web Settings</strong> and add a MIME type, or ensure your master Nginx template includes the <code>include /etc/nginx/mime.types;</code> directive.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item bg-transparent border-top mt-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-light fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq_dns_err">
                                            I created a domain, but it says "Site cannot be reached."
                                        </button>
                                    </h2>
                                    <div id="faq_dns_err" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            This is almost always a DNS or Firewall issue. First, verify in your domain registrar (e.g., GoDaddy) that the A-Record points to your Stackrium server IP. Second, ensure Port 80 and Port 443 are open in your cloud provider's network security group.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item bg-transparent border-top mt-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-light fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq_ssl">
                                            Why is my Let's Encrypt SSL failing?
                                        </button>
                                    </h2>
                                    <div id="faq_ssl" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            Let's Encrypt must verify that you own the domain. If your DNS hasn't fully propagated globally, or if Cloudflare Proxy (the orange cloud) is turned ON during installation, Let's Encrypt cannot verify the IP and will fail. Ensure DNS is fully propagated and proxying is disabled before retrying.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item bg-transparent border-top mt-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-light fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq_pm2">
                                            My PM2 App (Python/Node) deployment shows "Errored" in Live Tasks.
                                        </button>
                                    </h2>
                                    <div id="faq_pm2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            This usually means the port your application is trying to use is already bound to another process, or your code has a fatal syntax error. Use the File Manager to check your application's internal logs, and verify the correct port is set in your Node <code>server.js</code> or Python <code>app.py</code>.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item bg-transparent border-top mt-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-light fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq_git">
                                            Can I use multiple Git repositories for a single user?
                                        </button>
                                    </h2>
                                    <div id="faq_git" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            For strict isolation, Stackrium enforces a <strong>"One User, One Identity"</strong> rule. Each system user is assigned exactly one unique SSH Deploy Key. If you are managing multiple domains that require different Git repositories, you must provision a new User in the <strong>Users</strong> tab. This ensures that if one website is compromised, the attacker cannot access your other repositories.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item bg-transparent border-top mt-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-light fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq_db_large">
                                            I get a "File too large" error when importing in phpMyAdmin.
                                        </button>
                                    </h2>
                                    <div id="faq_db_large" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            By default, PHP limits uploads. You can increase the global Max Upload Size to 512MB in System Settings. However, for massive SQL files (over 512MB), do not use phpMyAdmin. Upload the <code>.sql</code> file via File Manager and use the terminal: <br><code>mysql -u db_user -p database_name < database.sql</code>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item bg-transparent border-top mt-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-light fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq_php_err">
                                            I changed the PHP settings, but the site isn't updating.
                                        </button>
                                    </h2>
                                    <div id="faq_php_err" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            Whenever you change advanced PHP settings (like <code>memory_limit</code>), Stackrium automatically tests syntax and restarts the PHP-FPM worker for that domain. If it didn't reflect, check the <strong>Live Task Log</strong>. If you entered invalid syntax, the server blocked the reload to keep your site online!
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item bg-transparent border-top mt-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-light fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                            How do I restart PHP or Nginx manually?
                                        </button>
                                    </h2>
                                    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            Go to the <b>Dashboard</b> tab, scroll down to System Services, and click the restart icon next to Nginx or PHP-FPM.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item bg-transparent border-top mt-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-light fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq_2fa">
                                            I enabled 2FA but lost my phone. How do I get back in?
                                        </button>
                                    </h2>
                                    <div id="faq_2fa" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            You will need SSH access to the server. Log in to your terminal and simply run the command: <code>sudo stackrium login</code>. The Stackrium CLI will instantly generate a secure, one-time access link. Copy and paste that link into your browser to bypass the login screen. Once inside, go to <strong>System Settings</strong> to reset or disable your 2FA.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item bg-transparent border-top mt-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-light fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq_timeout">
                                            Why did the server freeze or say ERR_TIMED_OUT when I clicked Apply?
                                        </button>
                                    </h2>
                                    <div id="faq_timeout" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            If you leave the panel open for a long time, your security session (CSRF Token) expires. Submitting a form triggers a <code>403 Forbidden</code> error. Stackrium's internal <strong>Fail2ban</strong> firewall detects these 403 errors and assumes a bot is attacking the panel. It immediately blocks your IP address to protect the server, causing a timeout. <br><em>Fix: Refresh the page to get a new token before submitting forms after a long idle period.</em>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item bg-transparent border-top mt-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-light fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq_custom_ssl">
                                            What happens if I paste a corrupted Custom SSL Certificate?
                                        </button>
                                    </h2>
                                    <div id="faq_custom_ssl" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            Stackrium's backend uses a robust SRE rollback failsafe. Before applying your custom certificate to Nginx, it backs up the configuration and runs <code>nginx -t</code>. If your certificate crashes Nginx, the script instantly aborts, deletes the corrupted file, restores the backup, and reloads Nginx safely so your other domains stay online.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item bg-transparent border-top mt-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-light fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq_sys_task">
                                            Why does the UI show "System Task" instead of the real action?
                                        </button>
                                    </h2>
                                    <div id="faq_sys_task" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            If you see "System Task", it means a new backend script was added to the Python daemon, but the JavaScript translation dictionary in <code>system.js</code> hasn't been updated with the new icon and title mapping yet.
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="tab-pane fade" id="doc-license" role="tabpanel">
                            <h2 class="fw-bold mb-4 text-success">Commercial Licensing & Billing</h2>
                            
                            <div class="text-center my-5">
                                <i class="bi bi-wallet2 display-1 text-muted opacity-50 mb-3 d-block"></i>
                                <h3 class="fw-bold">Ah, the dreaded "Commercial License" section.</h3>
                                <p class="lead text-muted mt-3">
                                    Our billing department has rigorously audited your account, consulted with top-tier accountants, and concluded that you currently owe us exactly...
                                </p>
                                <h1 class="display-2 fw-bold text-success my-4">$0.00</h1>
                            </div>

                            <div class="alert alert-light border shadow-sm p-4">
                                <h5 class="fw-bold"><i class="bi bi-info-circle-fill text-primary me-2"></i> The Reality Check</h5>
                                <p class="text-muted mb-3">Stackrium is 100% free to use. Seriously.</p>
                                <p class="text-muted mb-3">
                                    The <code>license.key</code> generated during your server's installation is simply a cryptographic handshake. It allows your server to securely authenticate with Stackrium Central so it can pull down core updates and manage staggered rollouts without getting rate-limited by our API.
                                </p>
                                <p class="text-muted mb-0">
                                    Your "license" is automatically valid for the next 10 years (or until the heat death of the universe, whichever comes first). Keep your wallet in your pocket, close this tab, and go enjoy building something awesome!
                                </p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>