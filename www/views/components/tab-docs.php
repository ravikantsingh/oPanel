<div class="tab-pane fade" id="docs" role="tabpanel">
    <div class="row g-4 mb-4">
        <div class="col-12">
            <h4 class="fw-bold mb-0"><i class="bi bi-journal-text text-primary me-2"></i> Documentation & Guide</h4>
            <p class="text-muted mt-1">Learn how to manage domains, deploy apps, configure firewalls, schedule backups, and use Stackrium effectively.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="mb-3 position-sticky" style="top: 20px; z-index: 10;">
            <div class="input-group shadow-sm rounded">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <!-- Updated onkeyup event below -->
                <input type="text" id="docSearch" onkeyup="window.filterDocs()" class="form-control border-start-0 ps-0" placeholder="Search topics...">
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 position-sticky" style="top: 20px;">
                <div class="card-body p-0">
                    <div class="list-group list-group-flush rounded" id="docs-list" role="tablist">
                        <a class="list-group-item list-group-item-action active fw-bold py-3" data-bs-toggle="list" href="#doc-prereq" role="tab">
                            <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i> 1. Cloud Prerequisites
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-domains" role="tab">
                            <i class="bi bi-globe me-2 text-primary"></i> 2. Domains & Subdomains
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-ftp" role="tab">
                            <i class="bi bi-folder2-open me-2 text-warning"></i> 3. FTP & File Access
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-filemanager" role="tab">
                            <i class="bi bi-folder-symlink me-2 text-info"></i> 4. Native File Manager
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-db-backups" role="tab">
                            <i class="bi bi-database me-2 text-success"></i> 5. Databases & Backups
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-dns" role="tab">
                            <i class="bi bi-diagram-2 me-2 text-info"></i> 6. DNS Configuration
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-git" role="tab">
                            <i class="bi bi-git me-2 text-danger"></i> 7. Git Deployment
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-pm2" role="tab">
                            <i class="bi bi-cpu me-2 text-success"></i> 8. Node.js & PM2
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-security" role="tab">
                            <i class="bi bi-shield-lock me-2 text-dark"></i> 9. Security & WAF
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-cdn" role="tab">
                            <i class="bi bi-diagram-3 me-2 text-danger"></i> 10. CDN & Proxy Routing
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-mail" role="tab">
                            <i class="bi bi-envelope me-2 text-primary"></i> 11. Mail Server
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-cron" role="tab">
                            <i class="bi bi-clock-history me-2 text-secondary"></i> 12. Cron Jobs
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-updates" role="tab">
                            <i class="bi bi-cloud-arrow-down me-2 text-info"></i> 13. System Updates
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-logs" role="tab">
                            <i class="bi bi-terminal me-2 text-dark"></i> 14. Logs & Tasks
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-faq" role="tab">
                            <i class="bi bi-question-circle me-2 text-secondary"></i> 15. Common FAQ
                        </a>
                        <a class="list-group-item list-group-item-action fw-bold py-3" data-bs-toggle="list" href="#doc-license" role="tab">
                            <i class="bi bi-award me-2 text-primary"></i> 16. Commercial License
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <div class="tab-content" id="nav-tabContent">
                        
                        <div class="tab-pane fade show active" id="doc-prereq" role="tabpanel">
                            <h2 class="fw-bold mb-4">Critical Cloud Prerequisite</h2>
                            <p class="fs-6 mb-4">Stackrium strictly manages your server's internal firewall (UFW). However, if you are hosting on AWS, Google Cloud, DigitalOcean, or Azure, you <strong>MUST</strong> also open the following ports in your provider's external Security Group or Network Firewall before proceeding.</p>

                            <div class="alert alert-warning shadow-sm border-warning border-start border-4 mb-4">
                                <h5 class="alert-heading fw-bold"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Required Core External Ports</h5>
                                <ul class="small mb-0 mt-3 text-dark font-monospace">
                                    <li class="mb-2"><strong>TCP 80 & 443:</strong> Web Traffic (HTTP/HTTPS)</li>
                                    <li class="mb-2"><strong>TCP 7443:</strong> Stackrium Dashboard Access</li>
                                    <li class="mb-2"><strong>TCP & UDP 53:</strong> BIND9 DNS Routing</li>
                                    <li class="mb-2"><strong>TCP 20, 21, & 40000-50000:</strong> Pure-FTPd Access</li>
                                    <li><strong>TCP 22:</strong> SSH Server Access</li>
                                </ul>
                            </div>

                            <div class="alert alert-primary bg-primary bg-opacity-10 shadow-sm border-primary border-start border-4 mb-4">
                                <h5 class="alert-heading fw-bold text-primary"><i class="bi bi-envelope-exclamation-fill me-2"></i> Conditional Mail Engine Ports</h5>
                                <p class="small text-dark mb-3"><strong>CRITICAL RULE:</strong> The infrastructure mappings below are strictly conditional. <strong>DO NOT</strong> open these ports in your cloud provider's security group unless you are planning to install, or have already activated, the local Stackrium Mail Engine subsystem.</p>
                                <ul class="small mb-0 text-dark font-monospace">
                                    <li class="mb-2"><strong>TCP 25 (SMTP Inbound):</strong> Used for server-to-server traffic. Required for remote engines (like Gmail) to route incoming emails into your local tenant mailboxes.</li>
                                    <li class="mb-2"><strong>TCP 465 (SMTPS Submissions):</strong> Secure implicit outbound mail submission channel utilizing dedicated native SSL/TLS handshakes from client apps.</li>
                                    <li class="mb-2"><strong>TCP 587 (SMTP Submission):</strong> Modern outbound client mail submission port utilizing explicit STARTTLS protocol upgrades.</li>
                                    <li class="mb-2"><strong>TCP 993 (IMAPS Secure):</strong> Encrypted incoming mailbox synchronization channel for real-time remote client connections (IMAP over SSL/TLS).</li>
                                    <li><strong>TCP 995 (POP3S Secure):</strong> Encrypted connection layer for legacy local-download mailbox configurations.</li>
                                </ul>
                            </div>

                            <div class="alert alert-danger shadow-sm border-danger border-start border-4 mt-4 p-4">
                                <h4 class="fw-bold text-danger mb-3"><i class="bi bi-shield-lock-fill me-2"></i> The Industry-Wide "Port 25" Outbound Block</h4>
                                
                                <h6 class="fw-bold text-dark mt-4">Why is my outgoing mail blocked by default?</h6>
                                <p class="small text-dark mb-3">
                                    To combat global spam networks, almost every major cloud hosting provider (AWS, DigitalOcean, Vultr, Linode) blocks <strong>Outbound Port 25</strong> at the hardware network layer for all new accounts. Historically, automated botnets would spin up hundreds of virtual servers using stolen credit cards to blast millions of spam emails. This ruined the IP Reputation of the entire cloud provider's network (their ASN). <br><br>
                                    To protect legitimate customers from inheriting blacklisted IP addresses, providers now physically intercept and drop outgoing packets directed at external mail hubs (like Gmail or Yahoo). Your server will queue the mail and eventually throw a <em>"Connection timed out"</em> error until a human administrator verifies your account.
                                </p>

                                <h6 class="fw-bold text-dark mt-4">The Resolution Process (AWS, DigitalOcean, Vultr)</h6>
                                <p class="small text-dark mb-3">You must submit a manual limit increase or support ticket to your cloud provider. Approval typically takes 12 to 24 hours.</p>
                                <ol class="small text-dark mb-4">
                                    <li class="mb-2"><strong>Establish Reputation:</strong> Ensure your server is assigned a static IP (e.g., an AWS Elastic IP) and your billing profile is fully verified.</li>
                                    <li class="mb-2"><strong>File the Ticket:</strong> Go to your provider's Support Center and open a "Service Limit Increase" (AWS) or "General Support" ticket. Request the removal of the <em>"Port 25 outbound sending restriction."</em></li>
                                    <li class="mb-2"><strong>Provide the Blueprint:</strong> Copy and paste this exact justification into your ticket:<br>
                                        <div class="bg-white p-2 mt-2 rounded border font-monospace text-muted">
                                            "I am provisioning a legitimate corporate mail transfer agent (MTA) via Postfix. I have attached a static Elastic IP to the instance and fully configured my Forward/Reverse DNS (PTR), SPF, and DMARC records. Please lift the outbound Port 25 routing restriction on this instance so my server can deliver mail to external providers."
                                        </div>
                                    </li>
                                </ol>

                                <div class="bg-white p-3 rounded border border-danger small text-dark mt-3">
                                    <strong><i class="bi bi-x-octagon-fill text-danger me-1"></i> The Permanent Exceptions (GCP & Azure):</strong><br>
                                    Google Cloud Platform (GCP) and Microsoft Azure permanently block outbound Port 25 for all standard, free, and pay-as-you-go accounts. They will <strong>not</strong> lift this restriction via support ticket under any circumstances. If you host Stackrium on these platforms, you cannot use Postfix for direct internet delivery. You must configure Postfix to route all outbound mail through a third-party authenticated SMTP Relay (such as SendGrid, Mailgun, or Amazon SES) using Port 587.
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="doc-domains" role="tabpanel">
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
                                <li class="list-group-item bg-transparent border-0 py-2"><i class="bi bi-check2 text-success me-2"></i> Go to <b>Websites</b> and click the Status toggle next to the domain.</li>
                                <li class="list-group-item bg-transparent border-0 py-2"><i class="bi bi-check2 text-success me-2"></i> Stackrium intercepts the Nginx traffic and redirects all visitors to a 503 "Service Unavailable" page.</li>
                                <li class="list-group-item bg-transparent border-0 py-2"><i class="bi bi-check2 text-success me-2"></i> It is completely non-destructive (files and DBs remain intact) and can be unsuspended instantly.</li>
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
                        
                        <div class="tab-pane fade" id="doc-filemanager" role="tabpanel">
                            <h2 class="fw-bold mb-4">Native File Manager</h2>
                            <p class="fs-6 mb-4">Stackrium includes a secure, built-in Native File Manager that allows you to manage your website files directly from the browser, bypassing the need for third-party FTP clients entirely.</p>

                            <h5 class="fw-bold text-info"><i class="bi bi-rocket-takeoff me-2"></i> 1. Deployment & Secure Access</h5>
                            <ul class="list-unstyled mb-4 text-muted">
                                <li class="mb-2"><i class="bi bi-shield-lock text-success me-2"></i> <b>Isolated Environment:</b> When launched, Stackrium provisions a dedicated Nginx configuration block specifically for the file manager. It safely bypasses ModSecurity to ensure that legitimate code saves and bulk uploads are not falsely flagged as attacks.</li>
                                <li class="mb-2"><i class="bi bi-key text-success me-2"></i> <b>Single Sign-On (SSO):</b> You access the file manager seamlessly via an HMAC SHA256 signature verification system. This generates a time-limited token that expires in under 60 seconds, meaning you never have to type a password while staying protected from replay attacks.</li>
                            </ul>

                            <h5 class="fw-bold text-info mt-4"><i class="bi bi-window-sidebar me-2"></i> 2. User Interface & Navigation</h5>
                            <ul class="list-unstyled mb-4 text-muted">
                                <li class="mb-2"><i class="bi bi-check2 text-info me-2"></i> <b>Smart UI:</b> Enjoy a fully responsive, mobile-friendly design built with Tailwind CSS, complete with built-in Dark/Light mode toggling that saves directly to your browser's local storage.</li>
                                <li class="mb-2"><i class="bi bi-check2 text-info me-2"></i> <b>Live Sorting & Search:</b> Instantly filter your visible files and folders via the client-side search bar. Click any table header to sort items by Name, Size, or Modified Date (folders will intelligently always float to the top of the list).</li>
                                <li class="mb-2"><i class="bi bi-check2 text-info me-2"></i> <b>Directory Stats:</b> Keep track of your storage with a top bar that actively displays the total number of folders, files, and combined data size of your current directory.</li>
                            </ul>

                            <h5 class="fw-bold text-info mt-4"><i class="bi bi-tools me-2"></i> 3. Core File Operations</h5>
                            <ol class="list-group list-group-numbered list-group-flush mb-4">
                                <li class="list-group-item bg-transparent border-0 py-2"><b>Create & Upload:</b> Quickly spin up new folders and files, or upload assets directly into your active directory.</li>
                                <li class="list-group-item bg-transparent border-0 py-2"><b>In-Browser Code Editing:</b> Click on any code or text file to open the built-in text editor. Make your modifications and click Save without ever needing to download the file.</li>
                                <li class="list-group-item bg-transparent border-0 py-2"><b>Advanced Move & Copy:</b> Utilize the robust "Destination Modal" to type a destination path, or use the "Quick Navigate" AJAX buttons to browse the server tree for seamless file transfers.</li>
                                <li class="list-group-item bg-transparent border-0 py-2"><b>Archives:</b> Natively compress selected items into <code>.zip</code> files, or extract existing archives directly on the server.</li>
                                <li class="list-group-item bg-transparent border-0 py-2"><b>Permissions:</b> Modify file and folder read/write/execute permissions (chmod) via an intuitive popup modal.</li>
                            </ol>

                            <div class="alert alert-primary bg-primary bg-opacity-10 shadow-sm border-primary border-start border-4 mt-4">
                                <h5 class="alert-heading fw-bold text-primary"><i class="bi bi-ui-checks-grid me-2"></i> Floating Bulk Actions Toolbar</h5>
                                <p class="small text-dark mb-0">Selecting multiple items via checkboxes triggers a floating toolbar at the bottom of your screen. From here, you can execute bulk operations to simultaneously Delete, Copy, Move, or Zip your selected files. <br><br><b>Bonus:</b> Selecting multiple files and clicking <strong>Download</strong> will instantly compile them into a temporary <code>.zip</code> archive on the server, stream the download to you, and automatically clean up the temporary file afterward!</p>
                            </div>
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

                        <div class="tab-pane fade" id="doc-cdn" role="tabpanel">
                            <h2 class="fw-bold mb-4">Universal CDN & Proxy Manager</h2>
                            <p class="fs-6 mb-4">Stackrium includes a zero-configuration proxy detection engine. This is a critical security feature if your website uses Cloudflare, Fastly, Sucuri, AWS CloudFront, or any custom load balancer.</p>

                            <h5 class="fw-bold text-danger"><i class="bi bi-diagram-3 me-2"></i> Why is this required? (The "Spoofing" Problem)</h5>
                            <p class="text-muted mb-4">
                                When a user visits a site behind a CDN like Cloudflare, the CDN acts as a middleman. The CDN connects to your Stackrium server, not the user. 
                                By default, Nginx and Fail2ban will only see the CDN's IP address. If a hacker attacks your site through Cloudflare, Fail2ban will block Cloudflare's IP—taking your entire site offline for everyone!
                            </p>

                            <h5 class="fw-bold"><i class="bi bi-robot text-primary me-2"></i> Automated Global Networks (The "Big Players")</h5>
                            <p class="text-muted">Stackrium fully automates the IP resolution for major CDN networks.</p>
                            <ol class="list-group list-group-numbered list-group-flush mb-4">
                                <li class="list-group-item bg-transparent border-0 py-2">Go to the <b>Websites</b> tab and click the <b>CDN / Proxy</b> button for your domain.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Select your network (e.g., <b>Cloudflare</b> or <b>Fastly</b>) from the dropdown and click Apply.</li>
                                <li class="list-group-item bg-transparent border-0 py-2"><strong>The Magic:</strong> A background cron job automatically downloads the official, trusted IP ranges from your provider's API every week. Nginx uses this list to safely intercept the correct HTTP header (e.g., <code>CF-Connecting-IP</code> or <code>Fastly-Client-IP</code>) and replaces the proxy IP with the real visitor IP in your access logs.</li>
                            </ol>

                            <h5 class="fw-bold mt-4"><i class="bi bi-sliders text-success me-2"></i> Custom Proxies & Private Load Balancers</h5>
                            <p class="text-muted">If you are using an internal Docker network, HAProxy, or an obscure CDN, use the Custom Proxy option to prevent spoofing.</p>
                            <ul class="list-unstyled mb-4 text-muted">
                                <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i> <b>Trusted Proxy IPs:</b> Enter the specific internal IP address (e.g., <code>10.0.0.5</code>) or CIDR range (e.g., <code>192.168.1.0/24</code>) of the machine forwarding the traffic. Nginx will <em>only</em> trust headers coming from these exact IPs.</li>
                                <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i> <b>Real-IP Header:</b> Enter the header name your proxy uses to pass the real IP (most commonly <code>X-Forwarded-For</code>).</li>
                            </ul>
                            
                            <div class="alert alert-danger shadow-sm border-0 mt-3">
                                <strong>Security Warning:</strong> Never configure a Custom Proxy with <code>0.0.0.0/0</code> as the Trusted IP. Hackers can easily send fake <code>X-Forwarded-For</code> headers to trick Fail2ban into blocking innocent services like Google or Cloudflare.
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="doc-mail" role="tabpanel">
                            <h2 class="fw-bold mb-4">Mail Server Subsystem (Postfix/Dovecot Engine)</h2>
                            <p class="fs-6 mb-4">Stackrium includes a secure, highly scalable multi-tenant Mail Transfer Agent (MTA) built around Postfix and Dovecot, backed directly by automated system user boundaries and live MySQL lookup mappings.</p>

                            <h5 class="fw-bold text-primary"><i class="bi bi-envelope-plus me-2"></i> Step 1: Provisioning a Mail Domain</h5>
                            <ol class="list-group list-group-numbered list-group-flush mb-4">
                                <li class="list-group-item bg-transparent border-0 py-2">Go to the <b>Mail Server</b> tab.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Click "Add Mail Domain" and enter your parent domain.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">The panel instantly provisions independent MySQL access records and configures the environment to listen across local interfaces.</li>
                            </ol>

                            <h5 class="fw-bold text-primary"><i class="bi bi-person-badge me-2"></i> Step 2: Provisioning Isolated Mailboxes</h5>
                            <p>Once your domain mapping is verified, click the <b>Accounts</b> button next to the target domain name to configure mail users.</p>
                            <ul class="list-unstyled text-muted mb-4 ps-3">
                                <li class="mb-2"><i class="bi bi-shield-check text-success me-2"></i> <strong>Cryptographic Isolation:</strong> System mail users are mapped to a secure global <code>vmail</code> profile (UID/GID 5000).</li>
                                <li class="mb-2"><i class="bi bi-folder-symlink text-success me-2"></i> <strong>Maildir Delivery:</strong> The system enforces explicit Maildir resolution paths via <code>CONCAT('%d/', '%u', '/')</code> mapping fields. Raw messages are saved directly into independent filesystem paths at <code>/var/vmail/{domain}/{user}/new/</code>.</li>
                            </ul>

                            <h5 class="fw-bold text-primary"><i class="bi bi-lock-fill me-2"></i> Step 3: Standalone SRE SSL Provisioning</h5>
                            <p>To avoid security exceptions or port filtering conflicts with ModSecurity Web Application Firewalls (WAF), securing your mail subsystem utilizes an explicit **Standalone Handshake Method**.</p>
                            <div class="bg-light p-3 border rounded mb-4 small">
                                <span class="fw-bold text-dark d-block mb-1"><i class="bi bi-terminal-fill me-1"></i> Under the Hood Security Loop:</span>
                                When you click <b>Secure Mail Server</b>, the background system pauses Nginx for 2–3 seconds to temporarily release port 80/443 interface bindings. Certbot spins up an ultra-light standalone authentication server to communicate with Let's Encrypt directly, bypassing all web rule filters. Once the valid trusted certificates are verified and saved, Nginx reloads instantly alongside Postfix and Dovecot.
                            </div>

                            <div class="alert alert-warning border-0 shadow-sm d-flex mt-4">
                                <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-warning"></i>
                                <div>
                                    <strong>Crucial DNS Requirements for Mail Delivery:</strong>
                                    <p class="mb-1 mt-2 small">To prevent your emails from going to Spam, you <b>must</b> configure these DNS records for your domain:</p>
                                    <ul class="mb-0 small pl-3">
                                        <li><b>MX Record:</b> Pointing to `mail.yourdomain.com` (Priority 10)</li>
                                        <li><b>A Record:</b> `mail.yourdomain.com` pointing to this server's IP.</li>
                                        <li><b>TXT (SPF):</b> `v=spf1 mx a ip4:YOUR_SERVER_IP ~all`</li>
                                        <li><b>TXT (DMARC):</b> `v=DMARC1; p=quarantine;`</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <h5 class="fw-bold text-primary mt-4"><i class="bi bi-pc-display me-2"></i> Client Connection Matrix</h5>
                            <div class="bg-light p-3 rounded border font-monospace small mb-4">
                                <p class="mb-1"><b>Incoming Server (IMAP):</b> mail.yourdomain.com (Port 993, Connection Security: SSL/TLS)</p>
                                <p class="mb-1"><b>Incoming Server (POP3):</b> mail.yourdomain.com (Port 995, Connection Security: SSL/TLS)</p>
                                <p class="mb-1"><b>Outgoing Server (SMTP):</b> mail.yourdomain.com (Port 465, Connection Security: SSL/TLS) - <i>Requires Authentication</i></p>
                                <p class="mb-1"><b>Alternative Outgoing Server (SMTP):</b> mail.yourdomain.com (Port 587, Connection Security: STARTTLS)</p>
                                <p class="mb-0"><b>Username / Auth Identification:</b> Your full email address (e.g., admin@yourdomain.com).</p>
                            </div>

                            <h5 class="fw-bold text-primary mt-4"><i class="bi bi-send-check me-2"></i> Step 4: Connecting a Mail Client (e.g., Mozilla Thunderbird)</h5>
                            <p class="text-muted mb-3">Once your mailbox is provisioned and secured with an SSL certificate, you can connect it to desktop or mobile email clients like Mozilla Thunderbird, Microsoft Outlook, or Apple Mail. Here is a detailed, step-by-step example using Thunderbird:</p>
                            
                            <ol class="list-group list-group-numbered list-group-flush mb-4">
                                <li class="list-group-item bg-transparent border-0 py-2">Open Thunderbird and navigate to <b>Account Settings > Add Mail Account</b>.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">Enter your Full Name, your <b>Full Email Address</b> (e.g., <code>admin@yourdomain.com</code>), and your mailbox password. Click <b>Configure Manually</b>.</li>
                                <li class="list-group-item bg-transparent border-0 py-2">
                                    <strong>Incoming Server Settings:</strong>
                                    <ul class="mb-0 mt-2 small text-muted">
                                        <li><b>Protocol:</b> Select <code>IMAP</code> (Recommended to keep devices synced) or <code>POP3</code> (Downloads and removes from server).</li>
                                        <li><b>Hostname:</b> <code>mail.yourdomain.com</code></li>
                                        <li><b>Port:</b> <code>993</code> (for IMAP) or <code>995</code> (for POP3).</li>
                                        <li><b>Connection Security:</b> Select <code>SSL/TLS</code>.</li>
                                        <li><b>Authentication Method:</b> Select <code>Normal password</code>.</li>
                                        <li><b>Username:</b> Enter your <i>full email address</i> (not just the prefix).</li>
                                    </ul>
                                </li>
                                <li class="list-group-item bg-transparent border-0 py-2">
                                    <strong>Outgoing Server (SMTP) Settings:</strong>
                                    <ul class="mb-0 mt-2 small text-muted">
                                        <li><b>Hostname:</b> <code>mail.yourdomain.com</code></li>
                                        <li><b>Port:</b> <code>465</code> (Recommended implicit SSL wrapper) or <code>587</code>.</li>
                                        <li><b>Connection Security:</b> Select <code>SSL/TLS</code> (if using port 465) or <code>STARTTLS</code> (if using port 587).</li>
                                        <li><b class="text-danger">Authentication Method:</b> Select <code>Normal password</code>. <i>CRITICAL: Do not leave this blank or anonymous. Postfix will reject outbound emails with an "Access Denied" error if you do not authenticate.</i></li>
                                        <li><b>Username:</b> Enter your <i>full email address</i>.</li>
                                    </ul>
                                </li>
                                <li class="list-group-item bg-transparent border-0 py-2">Click <b>Done</b> or <b>Re-test</b>. The client will securely connect to the Dovecot/Postfix backend using your Let's Encrypt certificate, sync your inbox, and allow you to send outbound emails instantly.</li>
                            </ol>

                            <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-start border-4 mt-4 p-4">
                                <h6 class="fw-bold text-danger mb-2"><i class="bi bi-send-x-fill me-2"></i> Troubleshooting: Emails are sending but not arriving?</h6>
                                <p class="mb-0 text-dark" style="font-size: 0.9rem;">
                                    If your mail client successfully sends the email, but it never arrives at the destination (like Gmail or Yahoo), your server is likely suffering from an <strong>Outbound Port 25 Block</strong> imposed by your cloud provider. <br><br>
                                    You can verify this by logging into your server terminal via SSH and typing <code>mailq</code>. If you see emails sitting in the queue with a <em>"Connection timed out"</em> error, your cloud provider (AWS, DigitalOcean, Vultr) is dropping your packets. You must contact their support team to remove the anti-spam block on your account. <em>(See <strong>1. Cloud Prerequisites</strong> for more details).</em>
                                </p>
                            </div>

                            <h5 class="fw-bold text-primary mt-5"><i class="bi bi-send-arrow-up me-2"></i> Step 5: External SMTP Relay (Bypassing Cloud Blocks)</h5>
                                <p class="text-muted mb-3"><strong>When and Why to use it:</strong> If your cloud provider (AWS, GCP, DigitalOcean) blocks outbound Port 25, your emails will queue up locally and eventually fail with connection timeouts. Additionally, even if Port 25 is open, sending directly from a fresh server IP can cause your emails to land in spam. The <strong>External SMTP Relay</strong> solves both issues by seamlessly routing all outgoing server mail through highly trusted third-party networks (like SendGrid, Brevo, or Amazon SES) using an alternate secure port (Port 587).</p>

                                <p class="text-muted mb-2"><strong>Setup Process & Example:</strong></p>
                                <ol class="list-group list-group-numbered list-group-flush mb-4">
                                    <li class="list-group-item bg-transparent border-0 py-2"><strong>Get an API Key:</strong> Register for a free tier account at an SMTP provider like Brevo or SendGrid. Navigate to their SMTP & API settings and generate a new SMTP API Key.</li>
                                    <li class="list-group-item bg-transparent border-0 py-2"><strong>Configure the Panel:</strong> Open the Mailboxes interface for your domain in Stackrium. Click the <b>Configure Relay</b> button located in the External SMTP Routing banner.</li>
                                    <li class="list-group-item bg-transparent border-0 py-2">
                                        <strong>Input Credentials Example (Using Brevo):</strong>
                                        <ul class="mb-0 mt-2 small text-dark font-monospace bg-light p-3 rounded border">
                                            <li class="mb-1">Provider Preset: Custom Provider</li>
                                            <li class="mb-1">SMTP Hostname: smtp-relay.brevo.com</li>
                                            <li class="mb-1">Port: 587</li>
                                            <li class="mb-1">SMTP Username: your_brevo_login_email@example.com</li>
                                            <li>SMTP Password: (Paste the long API Key generated in Step 1)</li>
                                        </ul>
                                    </li>
                                    <li class="list-group-item bg-transparent border-0 py-2"><strong>Apply & Bypass:</strong> Click <b>Apply Routing Rules</b>. Stackrium will cryptographically lock the credentials, inject the new route into the active Postfix memory, and immediately begin routing your mail around the cloud firewall.</li>
                                </ol>

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
                                        <button class="accordion-button collapsed bg-light fw-bold rounded text-danger" type="button" data-bs-toggle="collapse" data-bs-target="#faq_cdn_ban">
                                            My entire website went offline after turning on Cloudflare!
                                        </button>
                                    </h2>
                                    <div id="faq_cdn_ban" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            You are likely a victim of <strong>CDN Suicide</strong>. Because you didn't configure the proxy routing in the panel, Fail2ban detected malicious traffic coming from Cloudflare, assumed Cloudflare was the hacker, and blocked Cloudflare's IP in the server firewall. <br><br>
                                            <strong>The Fix:</strong> Log into the server via SSH, run <code>sudo fail2ban-client unban --all</code>, and immediately go to the Domains tab > CDN / Proxy Settings to configure Cloudflare properly!
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

                                <div class="accordion-item bg-transparent border-top mt-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-light fw-bold rounded text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#faq_mail_zero_msg">
                                            Incoming emails hit the log with status=sent but mail clients show 0 messages.
                                        </button>
                                    </h2>
                                    <div id="faq_mail_zero_msg" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            This occurs when Postfix delivers mail in <strong>mbox format</strong> (a single flat file) while Dovecot scans for a modern folder-based **Maildir system**. If your MySQL query parameters return a static integer fallback, Postfix pools every email into a flat file named <code>/var/vmail/1</code> instead of parsing individual user paths.<br><br>
                                            <strong>The Fix:</strong> Ensure your <code>/etc/postfix/mysql-virtual-mailbox-maps.cf</code> handles relational paths using explicit string concatenation: <br><code>query = SELECT CONCAT('%d/', '%u', '/') FROM mail_users WHERE email='%s'</code>. <br>The trailing slash explicitly instructs Postfix to initialize full Maildir directories, allowing Dovecot and mail clients to sync indices successfully.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item bg-transparent border-top mt-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-light fw-bold rounded text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#faq_mail_relay_denied">
                                            Outbound mail drops with "Recipient address rejected: Access denied" or disconnects.
                                        </button>
                                    </h2>
                                    <div id="faq_mail_relay_denied" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            This error indicates that Postfix is evaluating external mail routes before your client authenticates, or it hasn't been configured to leverage Dovecot's security daemons for SASL verification. Postfix rejects the unverified transfer request to prevent the server from becoming an open proxy for spam.<br><br>
                                            <strong>The Fix:</strong> Ensure Postfix connects directly to Dovecot's authentication paths by running:<br>
                                            <code>sudo postconf -e "smtpd_sasl_type = dovecot"</code><br>
                                            <code>sudo postconf -e "smtpd_sasl_path = private/auth"</code><br>
                                            Additionally, ensure both <code>submission</code> (587) and <code>submissions</code> (465) services include the explicit relay parameter override inside your <code>master.cf</code> file to process SASL tokens before dropping traffic: <br><code>-o smtpd_relay_restrictions=permit_sasl_authenticated,reject</code>. Finally, check your client application (e.g., Thunderbird) to ensure its outgoing SMTP server is explicitly set to use <strong>Normal password</strong> authentication rather than anonymous submission.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item bg-transparent border-top mt-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-light fw-bold rounded text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#faq_smtp_relay_auth">
                                            My SMTP Relay connects, but the provider rejects the sender or bounces the email.
                                        </button>
                                    </h2>
                                    <div id="faq_smtp_relay_auth" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body px-0 text-muted">
                                            If you see a "sender rejected" or "domain not valid" error in your logs after setting up an External SMTP Relay, it means Stackrium successfully bypassed your cloud firewall and handed the email to your relay provider (e.g., Brevo or SendGrid), but their edge network rejected the payload for security reasons. To prevent spam spoofing, external relays require cryptographic proof that you actually own the domain you are sending from.<br><br>
                                            <strong>The Fix:</strong> Log into your relay provider's dashboard, locate their Domain Authentication section, and generate their specific DNS records (usually a TXT verification code, DKIM, and SPF). Copy those values and add them into Stackrium's <strong>Security & DNS > DNS Management</strong> tab. Once the DNS records propagate, click "Authenticate" in the provider's dashboard to permanently lift the security hold and allow your emails through.
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