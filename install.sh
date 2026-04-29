#!/bin/bash
# ==============================================================================
# oPanel Installer
# Supports: Ubuntu 22.04 LTS & 24.04 LTS (Clean OS Required)
# ==============================================================================

GITHUB_REPO="https://github.com/ravikantsingh/oPanel.git"

# Ensure script is run as root
if [ "$EUID" -ne 0 ]; then
  echo "Please run this installer as root (sudo bash install.sh)"
  exit 1
fi

echo -e "\e[32mStarting oPanel Installation...\e[0m"
export DEBIAN_FRONTEND=noninteractive

# ==========================================
# 1. INSTALL CORE DEPENDENCIES & NODE.JS
# ==========================================
echo -e "\e[34m[1/10] Installing system dependencies...\e[0m"
apt-get update && apt-get upgrade -y
apt-get install -y software-properties-common curl wget git unzip jq quota quotatool

# Add PHP Repository
add-apt-repository ppa:ondrej/php -y

# Add Node.js Repository (Version 20 LTS)
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -

apt-get update

# Install Nginx, MariaDB, Python, Multi-PHP, and Node.js
apt-get install -y nginx mariadb-server python3-pip python3-mysql.connector \
    certbot python3-certbot-nginx \
    bind9 bind9utils bind9-doc \
    pure-ftpd pure-ftpd-common \
    libnginx-mod-http-modsecurity modsecurity-crs \
    php8.1-fpm php8.1-mysql \
    php8.2-fpm php8.2-mysql \
    php8.3-fpm php8.3-mysql php8.3-cli php8.3-curl \
    nodejs

# Install PM2 Globally and Configure Boot Startup
echo -e "\e[34m[+] Installing PM2 Process Manager...\e[0m"
npm install pm2@latest -g
pm2 startup systemd -u root --hp /root

# Purge vsftpd just in case it was installed, to prevent Port 21 conflicts
apt-get purge -y vsftpd 2>/dev/null || true

# ==========================================
# 2. CLONE PANEL FILES
# ==========================================
echo -e "\e[34m[2/10] Downloading oPanel core...\e[0m"
mkdir -p /opt/panel
git clone "$GITHUB_REPO" /tmp/panel_temp
cp -r /tmp/panel_temp/daemon /opt/panel/
cp -r /tmp/panel_temp/scripts /opt/panel/
cp -r /tmp/panel_temp/www /opt/panel/

# ==========================================
# 3. SET STRICT PERMISSIONS
# ==========================================
echo -e "\e[34m[3/10] Securing file permissions...\e[0m"
mkdir -p /opt/panel/logs
mkdir -p /opt/panel/backups/databases
mkdir -p /opt/panel/backups/websites

chown -R www-data:www-data /opt/panel/www
chown -R root:root /opt/panel/daemon /opt/panel/scripts /opt/panel/logs

# Make all bash scripts and the Python daemon executable
chmod +x /opt/panel/scripts/*.sh
chmod +x /opt/panel/daemon/worker.py

# Secure the Backup Vaults
chgrp -R www-data /opt/panel/backups
find /opt/panel/backups -type d -exec chmod 750 {} +
find /opt/panel/backups -type f -exec chmod 640 {} +

# ==========================================
# 4. INITIALIZE DATABASE
# ==========================================
echo -e "\e[34m[4/10] Bootstrapping MariaDB Environment...\e[0m"
systemctl start mariadb

# Generate a highly secure random password for the panel's internal DB connection
DB_PASS=$(openssl rand -hex 16)

# Create the databases and users
mysql -e "CREATE DATABASE IF NOT EXISTS panel_core;"
mysql -e "CREATE USER IF NOT EXISTS 'panel_user'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON panel_core.* TO 'panel_user'@'localhost';"

# Create the background phpMyAdmin SSO user (Hardcoded to match config.inc.php)
mysql -e "CREATE USER IF NOT EXISTS 'pma_sso'@'localhost' IDENTIFIED BY 'PmaMasterKey998877';"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'pma_sso'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Import the schema
mysql panel_core < /tmp/panel_temp/schema.sql

# Update the PHP config file with the new generated password
cat <<EOF > /opt/panel/www/config/database.php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'panel_core');
define('DB_USER', 'panel_user');
define('DB_PASS', '$DB_PASS');
EOF

# ==========================================
# 5. CONFIGURE PHPMYADMIN
# ==========================================
echo -e "\e[34m[5/10] Installing phpMyAdmin...\e[0m"
wget -q https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.zip -O /tmp/pma.zip
unzip -q /tmp/pma.zip -d /tmp/
mv /tmp/phpMyAdmin-*-all-languages /opt/panel/www/pma
rm /tmp/pma.zip

# Generate Blowfish Secret for SSO Cookies
BLOWFISH=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9' | head -c 32)
cp /tmp/panel_temp/www/pma/config.inc.php /opt/panel/www/pma/config.inc.php
sed -i "s/'x8y9z0A1b2C3d4E5f6G7h8I9j0K1l2M3'/'$BLOWFISH'/g" /opt/panel/www/pma/config.inc.php
chown -R www-data:www-data /opt/panel/www/pma

# ==========================================
# 6. CONFIGURE MODSECURITY & PURE-FTPD & BIND9
# ==========================================
echo -e "\e[34m[6/10] Configuring WAF, FTP, and DNS...\e[0m"
# WAF
mkdir -p /etc/modsecurity
wget -qO /etc/modsecurity/modsecurity.conf https://raw.githubusercontent.com/owasp-modsecurity/ModSecurity/v3/master/modsecurity.conf-recommended
wget -qO /etc/modsecurity/unicode.mapping https://raw.githubusercontent.com/owasp-modsecurity/ModSecurity/v3/master/unicode.mapping
sed -i 's/SecRuleEngine DetectionOnly/SecRuleEngine On/' /etc/modsecurity/modsecurity.conf
echo "Include /usr/share/modsecurity-crs/owasp-crs.load" >> /etc/modsecurity/modsecurity.conf
find /usr/share/modsecurity-crs/ -type f -exec sed -i 's/IncludeOptional/Include/g' {} +
apt-mark hold modsecurity-crs # Prevent OS from overwriting rules

# FTP
ln -sf /etc/pure-ftpd/conf/PureDB /etc/pure-ftpd/auth/50pure
echo "yes" > /etc/pure-ftpd/conf/ChrootEveryone
touch /etc/pure-ftpd/pureftpd.passwd
pure-pw mkdb
systemctl restart pure-ftpd

# DNS
mkdir -p /etc/bind/zones
chown bind:bind /etc/bind/zones

# ==========================================
# 7. CONFIGURE NGINX & SSL
# ==========================================
echo -e "\e[34m[7/10] Provisioning Self-Signed SSL for Port 7443...\e[0m"
SERVER_IP=$(curl -s ifconfig.me)
mkdir -p /etc/ssl/private /etc/ssl/certs
openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
    -keyout /etc/ssl/private/mypanel-selfsigned.key \
    -out /etc/ssl/certs/mypanel-selfsigned.crt \
    -subj "/C=IN/ST=UP/L=City/O=oPanel/CN=$SERVER_IP" >/dev/null 2>&1

cp /tmp/panel_temp/nginx-default.conf /etc/nginx/sites-available/default
systemctl restart nginx

# ==========================================
# 8. START PYTHON TASK DAEMON
# ==========================================
echo -e "\e[34m[8/10] Initializing Background Queue Worker...\e[0m"

# ---> NEW: Sync the generated DB password with the Python worker <---
sed -i "s/YOUR_SECURE_PASSWORD/$DB_PASS/g" /opt/panel/daemon/worker.py

cp /tmp/panel_temp/panel-daemon.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable panel-daemon
systemctl start panel-daemon

# WAF Cron Job
(crontab -l 2>/dev/null; echo "0 3 * * * /opt/panel/scripts/waf_updater.sh > /dev/null 2>&1") | crontab -

# ==========================================
# 9. CONFIGURE UFW FIREWALL
# ==========================================
echo -e "\e[34m[9/10] Securing perimeter...\e[0m"
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 7443/tcp
ufw allow 21/tcp
ufw allow 20/tcp
ufw allow 40000:50000/tcp
ufw --force enable

# Force the Source of Truth to sync on boot!
/opt/panel/scripts/sync_firewall.sh

# ==========================================
# 9.5 CONFIGURE CLI & SERVER BRANDING
# ==========================================
echo -e "\e[34m[10/10] Installing oPanel CLI and Branding...\e[0m"

# 1. Install the global CLI tool
cp /tmp/panel_temp/cli/opanel /usr/local/bin/opanel
chmod +x /usr/local/bin/opanel

# 2. Silence default Ubuntu/Plesk login messages
chmod -x /etc/update-motd.d/* 2>/dev/null || true
rm -f /etc/motd

# 3. Create the custom oPanel Welcome Banner
cat <<\EOF > /etc/update-motd.d/01-opanel
#!/bin/bash
echo -e "\e[34m"
echo "            8888888b.                            888  "
echo "            888   Y88b                           888  "
echo "            888    888                           888  "
echo "    .d88b.  888   d88P 8888b.  88888b.   .d88b.  888  "
echo "   d88''88b 8888888P'     '88b 888 '88b d8P  Y8b 888  "
echo "   888  888 888       .d888888 888  888 88888888 888  "
echo "   Y88..88P 888       888  888 888  888 Y8b.     888  "
echo "    'Y88P'  888       'Y888888 888  888  'Y8888  888  "
echo -e "\e[0m"
echo -e "\e[1m Welcome to oPanel(Open Panel)\e[0m"
echo -e "\e[1m Open, Omni and Optimize hosting control panel.\e[0m"
echo -e " ----------------------------------------------"
echo -e " \e[32mSystem:\e[0m $(lsb_release -d -s)"
echo -e " \e[32mKernel:\e[0m $(uname -r)"
echo -e " \e[32mAccess:\e[0m Type \e[1msudo opanel login\e[0m to access the web interface."
echo ""
EOF

# 4. Make the banner executable
chmod +x /etc/update-motd.d/01-opanel

# Cleanup
rm -rf /tmp/panel_temp

# ==========================================
# 10. COMPLETE
# ==========================================
echo -e "\e[32m=========================================================\e[0m"
echo -e "\e[32m🎉 oPanel Installation Complete! \e[0m"
echo -e "\e[32m=========================================================\e[0m"
echo -e "Your server is now locked down and running securely on Port 7443."
echo -e ""
echo -e "Login URL: \e[1mhttps://${SERVER_IP}:7443\e[0m"
echo -e "Username:  \e[1madmin\e[0m"
echo -e "Password:  \e[1madmin123\e[0m"
echo -e ""
echo -e "IMPORTANT: You will see a 'Not Private' warning because the"
echo -e "initial certificate is self-signed. Click 'Advanced' to bypass it."
echo -e "Once logged in, use 'System Settings' to secure the panel with a domain!"
echo -e "\e[32m=========================================================\e[0m"