#!/bin/bash
# ==============================================================================
# Stackrium Control Installer
# Supports: Ubuntu 22.04 LTS & 24.04 LTS (Clean OS Required)
# ==============================================================================

GITHUB_REPO="https://github.com/ravikantsingh/oPanel.git" # (Keep as your source repo)
BRANCH="lara-py"

# Ensure script is run as root
if [ "$EUID" -ne 0 ]; then
  echo "Please run this installer as root (sudo bash install.sh)"
  exit 1
fi

# 1. Check if the user passed the email as an environment variable (for automation)
if [ -z "$USER_EMAIL" ]; then
    # 2. If not, prompt them interactively. 
    # The "< /dev/tty" forces bash to read from the keyboard, bypassing the curl pipe!
    read -p "Enter your email address for License Registration: " USER_EMAIL < /dev/tty
fi

# 3. Final validation check
if [ -z "$USER_EMAIL" ]; then
    echo -e "\e[31mError: Email is required to register the server.\e[0m"
    exit 1
fi

echo -e "\n\e[32mStarting Stackrium Control Installation...\e[0m"
export DEBIAN_FRONTEND=noninteractive

# ==========================================
# 1. INSTALL CORE DEPENDENCIES & NODE.JS
# ==========================================
echo -e "\e[34m[1/14] Installing system dependencies...\e[0m"
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
    php8.3-fpm php8.3-mysql php8.3-cli php8.3-curl php8.3-common \
    nodejs fail2ban python3-venv python3-dev build-essential composer \
    php8.3-intl php8.3-curl php8.3-xml php8.3-mbstring php8.3-zip php8.3-bcmath php8.3-sqlite3

# Install PM2 Globally and Configure Boot Startup
echo -e "\e[34m[+] Installing PM2 Process Manager...\e[0m"
npm install pm2@latest -g
pm2 startup systemd -u root --hp /root

# Purge vsftpd just in case it was installed, to prevent Port 21 conflicts
apt-get purge -y vsftpd 2>/dev/null || true

# --- Stackrium Master WAF Provisioning ---
echo "Configuring ModSecurity for Master Panel..."
mkdir -p /etc/nginx/waf/
touch /etc/nginx/waf/stackrium-master.conf
cat <<EOF > /etc/nginx/waf/stackrium-master.conf
# Stackrium Master WAF Rules & Exceptions
EOF
systemctl restart nginx

# ==========================================
# 2. CLONE PANEL FILES
# ==========================================
echo -e "\e[34m[2/14] Downloading Stackrium Control core (Branch: $BRANCH)...\e[0m"

# 1. Clone the repository into a temporary folder
git clone -b "$BRANCH" "$GITHUB_REPO" /tmp/panel_temp

# 2. Create the destination config directory on the VPS
mkdir -p /opt/panel/config

# 3. Copy the public key from the cloned repo to the VPS config folder
# We use 2>/dev/null so the script doesn't crash if the file is missing for some reason
cp /tmp/panel_temp/config/public_key.pem /opt/panel/config/public_key.pem 2>/dev/null || true

# 4. Copy the rest of the core panel files
cp -r /tmp/panel_temp/daemon /opt/panel/
cp -r /tmp/panel_temp/scripts /opt/panel/
cp -r /tmp/panel_temp/www /opt/panel/
cp -r /tmp/panel_temp/templates /opt/panel/
cp -r /tmp/panel_temp/cli /opt/panel/

# ==========================================
# 3. SECURE LICENSE REGISTRATION
# ==========================================
echo -e "\e[34m[3/14] Registering server with Stackrium Central...\e[0m"

if [ ! -f "/opt/panel/config/public_key.pem" ]; then
    echo -e "\e[31mError: public_key.pem not found in repository. Cannot encrypt registration.\e[0m"
    exit 1
fi

# Encrypt the email payload using the public key
JSON_PAYLOAD="{\"email\":\"$USER_EMAIL\"}"
ENCRYPTED_PAYLOAD=$(echo -n "$JSON_PAYLOAD" | openssl pkeyutl -encrypt -pubin -inkey "/opt/panel/config/public_key.pem" | base64 -w 0)

# Send via secure URL-encoded cURL
RESPONSE=$(curl -s -X POST https://stackrium.com/api/register.php --data-urlencode "payload=$ENCRYPTED_PAYLOAD")
LICENSE_KEY=$(echo "$RESPONSE" | jq -r '.license_key')

if [ "$LICENSE_KEY" == "null" ] || [ -z "$LICENSE_KEY" ]; then
    echo -e "\e[31mError: Failed to obtain license key from stackrium.com. Registration failed.\e[0m"
    echo "Response: $RESPONSE"
    exit 1
fi

echo -e "\e[32mLicense activated successfully: $LICENSE_KEY\e[0m"
echo "$LICENSE_KEY" > /opt/panel/license.key
chown root:www-data /opt/panel/license.key
chmod 640 /opt/panel/license.key

# ==========================================
# 4. SET STRICT PERMISSIONS
# ==========================================
echo -e "\e[34m[4/14] Securing file permissions...\e[0m"
mkdir -p /opt/panel/logs
mkdir -p /opt/panel/backups/databases
mkdir -p /opt/panel/backups/websites
mkdir -p /etc/nginx/waf
mkdir -p /opt/panel/cli/migrations

echo "<?php define('PANEL_VERSION', '1.0.0'); ?>" > /opt/panel/www/version.php
chown www-data:www-data /opt/panel/www/version.php
# Provision the dynamic WAF settings state
echo '{"waf_branch": "v3.3/master"}' > /opt/panel/www/config/waf_settings.json
chown www-data:www-data /opt/panel/www/config/waf_settings.json
chmod 644 /opt/panel/www/config/waf_settings.json

chown -R www-data:www-data /opt/panel/www
chown -R root:root /opt/panel/daemon /opt/panel/scripts /opt/panel/logs /opt/panel/templates /opt/panel/cli

# This instantly scrubs Windows characters from ALL cloned scripts
sed -i -e 's/\r$//' /opt/panel/scripts/*.sh
sed -i -e 's/\r$//' /opt/panel/daemon/*.py
sed -i -e 's/\r$//' /opt/panel/cli/*.php
sed -i -e 's/\r$//' /opt/panel/config/*.pem

chmod +x /opt/panel/scripts/*.sh
chmod +x /opt/panel/daemon/worker.py
chmod +x /opt/panel/daemon/scheduler.py 

chgrp -R www-data /opt/panel/backups
find /opt/panel/backups -type d -exec chmod 750 {} +
find /opt/panel/backups -type f -exec chmod 640 {} +

echo 'www-data ALL=(root) NOPASSWD: /opt/panel/scripts/toggle_master_waf.sh *' > /etc/sudoers.d/stackrium-waf
chmod 440 /etc/sudoers.d/stackrium-waf

# ==========================================
# 5. INITIALIZE DATABASE
# ==========================================
echo -e "\e[34m[5/14] Bootstrapping MariaDB Environment...\e[0m"
systemctl start mariadb

DB_PASS=$(openssl rand -hex 16)

mysql -e "CREATE DATABASE IF NOT EXISTS panel_core;"
mysql -e "CREATE USER IF NOT EXISTS 'panel_user'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON panel_core.* TO 'panel_user'@'localhost';"

mysql -e "CREATE USER IF NOT EXISTS 'pma_sso'@'localhost' IDENTIFIED BY 'PmaMasterKey998877';"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'pma_sso'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

mysql panel_core < /tmp/panel_temp/schema.sql

cat <<EOF > /opt/panel/www/config/database.php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'panel_core');
define('DB_USER', 'panel_user');
define('DB_PASS', '$DB_PASS');
EOF

# ==========================================
# 6. CONFIGURE PHPMYADMIN
# ==========================================
echo -e "\e[34m[6/14] Installing phpMyAdmin...\e[0m"

wget -q --tries=3 --timeout=15 https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.zip -O /tmp/pma.zip

if [ ! -s /tmp/pma.zip ]; then
    echo -e "\e[33mWarning: wget failed. Falling back to curl...\e[0m"
    curl -sL https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.zip -o /tmp/pma.zip
fi

unzip -q /tmp/pma.zip -d /tmp/
mv /tmp/phpMyAdmin-*-all-languages /opt/panel/www/pma
rm /tmp/pma.zip

BLOWFISH=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9' | head -c 32)
cp /tmp/panel_temp/www/pma/config.inc.php /opt/panel/www/pma/config.inc.php
sed -i "s/'x8y9z0A1b2C3d4E5f6G7h8I9j0K1l2M3'/'$BLOWFISH'/g" /opt/panel/www/pma/config.inc.php
chown -R www-data:www-data /opt/panel/www/pma

# ==========================================
# 7. CONFIGURE MODSECURITY & PURE-FTPD & BIND9
# ==========================================
echo -e "\e[34m[7/14] Configuring WAF, FTP, and DNS...\e[0m"
mkdir -p /etc/modsecurity
wget -qO /etc/modsecurity/modsecurity.conf https://raw.githubusercontent.com/owasp-modsecurity/ModSecurity/v3/master/modsecurity.conf-recommended
wget -qO /etc/modsecurity/unicode.mapping https://raw.githubusercontent.com/owasp-modsecurity/ModSecurity/v3/master/unicode.mapping
sed -i 's/SecRuleEngine DetectionOnly/SecRuleEngine On/' /etc/modsecurity/modsecurity.conf
echo 'Include /usr/share/modsecurity-crs/owasp-crs.load' >> /etc/modsecurity/modsecurity.conf
find /usr/share/modsecurity-crs/ -type f -exec sed -i 's/IncludeOptional/Include/g' {} +
apt-mark hold modsecurity-crs 

ln -sf /etc/pure-ftpd/conf/PureDB /etc/pure-ftpd/auth/50pure
echo 'yes' > /etc/pure-ftpd/conf/ChrootEveryone
PUBLIC_IP=$(curl -s ifconfig.me)
echo '40000 50000' > /etc/pure-ftpd/conf/PassivePortRange
echo '$PUBLIC_IP' > /etc/pure-ftpd/conf/ForcePassiveIP
touch /etc/pure-ftpd/pureftpd.passwd
pure-pw mkdb
systemctl restart pure-ftpd

mkdir -p /etc/bind/zones
chown bind:bind /etc/bind/zones

echo -e "\e[34m[+] Resolving local DNS Port 53 conflicts...\e[0m"
mkdir -p /etc/systemd/resolved.conf.d
echo -e "[Resolve]\nDNSStubListener=no" > /etc/systemd/resolved.conf.d/stackrium-dns.conf
systemctl restart systemd-resolved
rm /etc/resolv.conf
ln -s /run/systemd/resolve/resolv.conf /etc/resolv.conf

echo -e "\e[34m[+] Configuring BIND9 for Public Access...\e[0m"
cat << 'EOF' > /etc/bind/named.conf.options
options {
        directory "/var/cache/bind";

        // Listen on all IPv4 interfaces
        listen-on { any; };
        
        // Listen on all IPv6 interfaces
        listen-on-v6 { any; };

        // Allow anyone on the internet to query your DNS server
        allow-query { any; };

        // SECURITY WARNING: Do NOT enable recursion if this is a public authoritative server!
        recursion no; 
};
EOF

systemctl restart bind9

# ==========================================
# 8. CONFIGURE NGINX & SSL
# ==========================================
echo -e "\e[34m[8/14] Provisioning Nginx, SSL, and Custom Errors...\e[0m"
SERVER_IP=$(curl -s ifconfig.me)
mkdir -p /etc/ssl/private /etc/ssl/certs
openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
    -keyout /etc/ssl/private/mypanel-selfsigned.key \
    -out /etc/ssl/certs/mypanel-selfsigned.crt \
    -subj "/C=IN/ST=UP/L=City/O=Stackrium/CN=$SERVER_IP" >/dev/null 2>&1

sed -i 's/# server_tokens off;/server_tokens off;/g' /etc/nginx/nginx.conf

mkdir -p /var/www/stackrium_errors
cp /tmp/panel_temp/www/errors/*.html /var/www/stackrium_errors/ 2>/dev/null || true

chown -R www-data:www-data /var/www/stackrium_errors
find /var/www/stackrium_errors -type f -exec chmod 644 {} +
chmod 755 /var/www/stackrium_errors

rm -f /var/www/html/index.nginx-debian.html
rm -f /var/www/html/index.html
cp /opt/panel/templates/index.html /var/www/html/index.html

chown www-data:www-data /var/www/html/index.html
chmod 644 /var/www/html/index.html

cat << 'EOF' > /etc/nginx/snippets/stackrium-errors.conf
fastcgi_intercept_errors on;
error_page 403 /stackrium_403.html;
error_page 404 /stackrium_404.html;
error_page 500 502 504 /stackrium_50x.html;
error_page 503 /stackrium_suspended.html;
location = /stackrium_403.html { root /var/www/stackrium_errors; allow all; internal; }
location = /stackrium_404.html { root /var/www/stackrium_errors; allow all; internal; }
location = /stackrium_50x.html { root /var/www/stackrium_errors; allow all; internal; }
location = /stackrium_suspended.html { root /var/www/stackrium_errors; allow all; internal; }
EOF

cat << 'EOF' > /etc/nginx/snippets/domain-suspended.conf
error_page 503 @suspended;
return 503;
location @suspended {
    root /var/www/stackrium_errors;
    rewrite ^(.*)$ /stackrium_suspended.html break;
    allow all;
}
EOF

echo -e "\e[34m[+] Configuring Sudoers Bridge...\e[0m"
echo 'Defaults:www-data !syslog, !pam_session' > /etc/sudoers.d/stackrium-ssl
echo 'www-data ALL=(root) NOPASSWD: /usr/bin/openssl x509 *' >> /etc/sudoers.d/stackrium-ssl
chmod 440 /etc/sudoers.d/stackrium-ssl

echo 'Defaults:www-data !syslog, !pam_session' > /etc/sudoers.d/stackrium-redis
echo 'www-data ALL=(root) NOPASSWD: /bin/systemctl restart redis-server' >> /etc/sudoers.d/stackrium-redis
chmod 440 /etc/sudoers.d/stackrium-redis

cp /tmp/panel_temp/nginx-default.conf /etc/nginx/sites-available/default

mkdir -p /etc/nginx/stackrium/redirects
mkdir -p /etc/nginx/stackrium/mimes
mkdir -p /etc/nginx/stackrium/hotlink
chown -R root:root /etc/nginx/stackrium
chmod -R 755 /etc/nginx/stackrium
chown -R root:root /etc/nginx/stackrium/hotlink

systemctl restart nginx

# ==========================================
# 9. INSTALL & SECURE REDIS CACHE
# ==========================================
echo -e "\e[34m[9/14] Provisioning Redis In-Memory Cache...\e[0m"
apt-get install -y redis-server php8.3-redis
REDIS_PASS=$(openssl rand -hex 16)
sed -i "s/# requirepass foobared/requirepass $REDIS_PASS/g" /etc/redis/redis.conf
echo "maxmemory 128mb" >> /etc/redis/redis.conf
echo "maxmemory-policy allkeys-lru" >> /etc/redis/redis.conf
systemctl restart redis-server
systemctl enable redis-server

mkdir -p /opt/panel/www/config
cat <<EOF > /opt/panel/www/config/redis.php
<?php
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASS', '$REDIS_PASS');
EOF

chown root:www-data /opt/panel/www/config/redis.php
chmod 640 /opt/panel/www/config/redis.php

# ==========================================
# 10. START PYTHON TASK DAEMON
# ==========================================
echo -e "\e[34m[10/14] Initializing Background Queue Worker...\e[0m"
sed -i "s/YOUR_SECURE_PASSWORD/$DB_PASS/g" /opt/panel/daemon/worker.py
sed -i "s/YOUR_DB_PASSWORD/$DB_PASS/g" /opt/panel/daemon/scheduler.py

cp /tmp/panel_temp/panel-daemon.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable panel-daemon
systemctl start panel-daemon

echo -e "\e[34m[+] Configuring System Cron Jobs...\e[0m"

cat <<EOF > /etc/cron.d/stackrium-core
# Stackrium Control Automated Tasks
# --------------------------------------------------
# Run Python scheduler every hour
0 * * * * root /usr/bin/python3 /opt/panel/daemon/scheduler.py >> /opt/panel/logs/scheduler.log 2>&1

# Telemetry Heartbeat (2:00 AM)
0 2 * * * root /bin/bash /opt/panel/scripts/heartbeat.sh > /dev/null 2>&1

# Update WAF Rules (3:00 AM)
0 3 * * * root /opt/panel/scripts/waf_updater.sh > /dev/null 2>&1

# Auto-Update Engine (4:00 AM)
0 4 * * * root /usr/bin/php /opt/panel/cli/auto_update.php >> /opt/panel/logs/auto_update.log 2>&1
EOF

chmod 644 /etc/cron.d/stackrium-core
systemctl restart cron

echo -e "\e[34m[+] Configuring Sudoers Bridge for Heartbeat Sync...\e[0m"
echo 'Defaults:www-data !syslog, !pam_session' > /etc/sudoers.d/stackrium-heartbeat
echo 'www-data ALL=(root) NOPASSWD: /bin/bash /opt/panel/scripts/heartbeat.sh' >> /etc/sudoers.d/stackrium-heartbeat
chmod 440 /etc/sudoers.d/stackrium-heartbeat
# Update Engine sudoers
echo 'www-data ALL=(root) NOPASSWD: /bin/bash /opt/panel/scripts/updater.sh *' | tee /etc/sudoers.d/stackrium-updater
chmod 440 /etc/sudoers.d/stackrium-updater

# ==========================================
# 11. CONFIGURE UFW FIREWALL
# ==========================================
echo -e "\e[34m[11/14] Securing perimeter...\e[0m"
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 7443/tcp
ufw allow 21/tcp
ufw allow 20/tcp
ufw allow 40000:50000/tcp
ufw allow 53/tcp
ufw allow 53/udp
ufw --force enable

/opt/panel/scripts/sync_firewall.sh

# ==========================================
# 12. CONFIGURE FAIL2BAN ACTIVE DEFENSE
# ==========================================
echo -e "\e[34m[12/14] Configuring Fail2ban Intrusion Prevention...\e[0m"
cat << 'EOF' > /etc/fail2ban/filter.d/Stackrium.conf
[Definition]
failregex = ^.*Stackrium Auth Failed:.*IP: <HOST>.*$
ignoreregex =
EOF

cat << 'EOF' > /etc/fail2ban/jail.local
[DEFAULT]
usedns   = no
bantime  = 1h
findtime  = 10m
maxretry = 5
banaction = ufw

[sshd]
enabled = true
port    = ssh
logpath = %(sshd_log)s
backend = %(sshd_backend)s
maxretry = 3

[pure-ftpd]
enabled  = true
port     = ftp
filter   = pure-ftpd
logpath  = /var/log/syslog
maxretry = 5

[postfix-sasl]
enabled  = true
port     = smtp,465,submission
filter   = postfix[mode=auth]
logpath  = /var/log/mail.log
maxretry = 5

[dovecot]
enabled = true
port    = pop3,pop3s,imap,imaps,submission,465,sieve
filter  = dovecot
logpath = /var/log/mail.log
maxretry = 5

[stackrium]
enabled  = true
port     = 7443
filter   = Stackrium
logpath  = /opt/panel/logs/auth.log
maxretry = 5
EOF

touch /opt/panel/logs/auth.log
chown www-data:www-data /opt/panel/logs/auth.log
chmod 660 /opt/panel/logs/auth.log

echo 'Defaults:www-data !syslog, !pam_session' > /etc/sudoers.d/stackrium-fail2ban
echo 'www-data ALL=(root) NOPASSWD: /usr/bin/fail2ban-client status, /usr/bin/fail2ban-client status *' >> /etc/sudoers.d/stackrium-fail2ban
chmod 440 /etc/sudoers.d/stackrium-fail2ban

systemctl restart fail2ban
systemctl enable fail2ban

# ==========================================
# 13. OPTIMIZE SYSTEM JOURNAL LOGGING
# ==========================================
echo -e "\e[34m[13/14] Capping System Logs to prevent CPU/Disk exhaustion...\e[0m"
sed -i 's/#SystemMaxUse=/SystemMaxUse=200M/g' /etc/systemd/journald.conf
systemctl restart systemd-journald

# ==========================================
# 14. CONFIGURE CLI & SERVER BRANDING
# ==========================================
echo -e "\e[34m[14/14] Installing Stackrium CLI and Branding...\e[0m"
chmod +x /opt/panel/cli/stackrium
ln -sf /opt/panel/cli/stackrium /usr/local/bin/stackrium

chmod -x /etc/update-motd.d/* 2>/dev/null || true
rm -f /etc/motd

cat <<\EOF > /etc/update-motd.d/01-stackrium
#!/bin/bash
echo -e "\e[33m"
echo "     .d0000b.  000                      000              000                        "    
echo "    d00P  Y00b 000                      000              000                        "
echo "    Y00b.      000                      000                                         "
echo "     'Y000b.   000000  0000b.   .d0000b 000  000 000d000 000 000  000 00000b.d00b.  "
echo "        'Y00b. 000        '00b d00P'    000 .00P 000P'   000 000  000 000 '000 '00b "
echo "          '000 000    .d000000 000      00000K   000     000 000  000 000  000  000 "
echo "    Y00b  d00P Y00b.  000  000 Y00b.    000 '00b 000     000 Y00b 000 000  000  000 "
echo "     'Y0000P'   'Y000 'Y000000  'Y0000P 000  000 000     000  'Y00000 000  000  000 "
echo -e "\e[34m"
echo "                        00''''''Y00                     00                     00   "
echo "                        0' .000. '0                     00                     00   "
echo "                        0  00000oo0 .d0000b. 00d000b. d0000P 00d000b. .d0000b. 00   "
echo "                        0  00000000 00'  '00 00'  '00   00   00'  '00 00'  '00 00   "
echo "                        0. '000' .0 00.  .00 00    00   00   00       00.  .00 00   "
echo "                        00.......d0 '00000P' 00    00   00   00       '00000P' 00   "
echo -e "\e[0m"
echo -e "\e[1m Welcome to Stackrium Control\e[0m"
echo -e "\e[1m Enterprise Cloud Server Management\e[0m"
echo -e " ----------------------------------------------"
echo -e " \e[32mSystem:\e[0m $(lsb_release -d -s)"
echo -e " \e[32mKernel:\e[0m $(uname -r)"
echo -e " \e[1mAccess:\e[0m Type \e[31msudo stackrium login\e[0m to access the web interface."
echo ""
EOF

chmod +x /etc/update-motd.d/01-stackrium
rm -rf /tmp/panel_temp

echo -e "\e[34m[+] Performing initial Update & Telemetry Sync...\e[0m"
/bin/bash /opt/panel/scripts/heartbeat.sh

# ==========================================
# COMPLETE
# ==========================================
echo -e "\e[32m=========================================================\e[0m"
echo -e "\e[32m🎉 Stackrium Control Installation Complete! \e[0m"
echo -e "\e[32m=========================================================\e[0m"
echo -e "Your server is now locked down and running securely on Port 7443."
echo -e ""
echo -e "Login URL: \e[1mhttps://${SERVER_IP}:7443\e[0m"
echo -e "Username:  \e[1madmin\e[0m"
echo -e "Password:  \e[1madmin123\e[0m"
echo -e ""
echo -e "Access the CLI by running:\e[31m sudo stackrium login\e[0m in the Terminal."
echo -e ""
echo -e "IMPORTANT: You will see a 'Not Private' warning because the"
echo -e "initial certificate is self-signed. Click 'Advanced' to bypass it."
echo -e "Once logged in, use 'System Settings' to secure the panel with a domain!"
echo -e "\e[32m=========================================================\e[0m"