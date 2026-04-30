oPanel ⚡
The Lightweight, Enterprise-Grade Web Hosting Control Panel

oPanel is a modern, open-source control panel designed for absolute speed and security. Bypassing bulky legacy software like Apache, oPanel is built purely on Nginx, PHP-FPM, MariaDB, and a Python-powered background task daemon. It brings enterprise-level features like ModSecurity WAF, Let's Encrypt SSL, and Git automation to your server with a single installation command.

🚀 Core Features
Server & Web Environment
Port-Isolated Architecture: The control panel runs securely on Port 7443, leaving 80/443 entirely dedicated to your high-traffic websites.

Multi-PHP Support: Instantly assign PHP 8.1, 8.2, or 8.3 to individual domains with custom resource limits.

Nginx Powered: High-performance web serving with automated Virtual Host provisioning.

Automated SSL: 1-Click Let's Encrypt SSL certificates with automated background renewals.

Enterprise Security
ModSecurity WAF: OWASP Core Rule Set integrated natively into Nginx, complete with a UI for compiling custom exception rules.

UFW Firewall Manager: Open, close, and monitor ports directly from the dashboard.

Cryptographic SSO: Password-free, cross-domain Single Sign-On (SSO) into Tiny File Manager and phpMyAdmin using mathematically proven HMAC tokens.

Developer Workflows
Git Integration: Clone private/public repositories, manage SSH deploy keys, and set up automated webhooks for seamless CI/CD pipelines.

DNS Zone Management: Full BIND9 integration for managing A, CNAME, TXT, and MX records.

Cron Job Scheduler: Intuitive UI for managing scheduled background tasks.

Live System Monitoring: Real-time CPU, RAM, and Disk health stats, plus a live streaming viewer for Nginx access and error logs.

Data & File Management
MariaDB & phpMyAdmin: Provision databases, manage fine-grained user privileges, and securely rotate passwords.

Pure-FTPd Engine: Create securely jailed FTP accounts for individual web roots.

Backup & Restore Vault: 1-Click full website archives and SQL dumps with an instant "restore-to-server" engine.

⚙️ System Requirements
Operating System: Ubuntu 22.04 LTS or 24.04 LTS

Environment: A completely clean/fresh installation. Do not install Nginx, Apache, or MySQL beforehand—the installer needs full control.

Hardware: 1GB RAM minimum (2GB+ recommended for ModSecurity & Git operations), 1 CPU Core, 20GB Disk.

Privileges: Root (sudo) access.

🛠️ Installation
Log into your fresh Ubuntu server as root and run this single command to initiate the automated installer:

Bash

wget -O install.sh https://raw.githubusercontent.com/ravikantsingh/oPanel/main/install.sh && sudo bash install.sh

The script will automatically install all core dependencies, bootstrap the MariaDB environment, configure Nginx and the Python task daemon, set up strict permissions, and lock down the firewall. Installation typically takes 3-5 minutes.

🔑 First Steps & Login
Once the installation finishes, the terminal will output your login instructions.

Navigate your browser to https://<YOUR_SERVER_IP>:7443

Note: You will initially see a "Connection is not private" warning. This is expected because the panel creates a self-signed certificate on boot to encrypt your first login. Click "Advanced" to proceed past the warning.

Log in with the default administrator credentials:

Username: admin

Password: admin123

Secure the Panel: Click the System Settings button in the dashboard to bind a real domain name (e.g., cp.yourdomain.com) to the panel. oPanel will automatically provision a Let's Encrypt certificate and safely reload the interface.

🛑 The "Scorched Earth" Deletion Protocol
oPanel is designed to keep your server completely free of orphaned files. When you delete a domain from the UI, oPanel's automated backend completely eradicates its Nginx configuration, SSL certificates, Git repositories, File Manager instances, and database records in seconds.

📄 License
This project is open-source and available under the MIT License.