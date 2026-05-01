#!/bin/bash
# /opt/panel/scripts/install_mail_engine.sh
# Executed by Python Daemon as root

# We expect a JSON payload, though we might not need specific data from it for a global install
PAYLOAD=$1
# Example: TARGET_DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain // empty')

# Provide a fallback for the mailname configuration if not specified
MAIL_DOMAIN="localhost"

echo "Starting Local Mail Engine Installation..."

# 1. Install Postfix and Dovecot silently
export DEBIAN_FRONTEND=noninteractive
sudo apt-get update

# Pre-seed the configuration answers so Postfix knows what to build
sudo debconf-set-selections <<< "postfix postfix/mailname string $MAIL_DOMAIN"
sudo debconf-set-selections <<< "postfix postfix/main_mailer_type string 'Internet Site'"

sudo apt-get install -y postfix postfix-mysql dovecot-core dovecot-imapd dovecot-pop3d dovecot-mysql libsasl2-modules

# 2. Create the master 'vmail' user (UID 5000) to own the physical email files
if ! id -u vmail > /dev/null 2>&1; then
    sudo groupadd -g 5000 vmail
    sudo useradd -g vmail -u 5000 vmail -d /var/vmail -m
fi
sudo chown -R vmail:vmail /var/vmail
sudo chmod -R 770 /var/vmail

# 3. Create a highly secure, Read-Only Database User just for Postfix
DB_PASS=$(openssl rand -hex 16)
sudo mysql -e "CREATE USER IF NOT EXISTS 'postfix_db_user'@'localhost' IDENTIFIED BY '${DB_PASS}';"
sudo mysql -e "GRANT SELECT ON panel_core.mail_domains TO 'postfix_db_user'@'localhost';"
sudo mysql -e "GRANT SELECT ON panel_core.mail_users TO 'postfix_db_user'@'localhost';"
sudo mysql -e "GRANT SELECT ON panel_core.mail_aliases TO 'postfix_db_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# 4. Generate the MySQL Mapping Files for Postfix
sudo tee /etc/postfix/mysql-virtual-mailbox-domains.cf > /dev/null <<EOF
user = postfix_db_user
password = ${DB_PASS}
hosts = 127.0.0.1
dbname = panel_core
query = SELECT 1 FROM mail_domains WHERE name='%s'
EOF

sudo tee /etc/postfix/mysql-virtual-mailbox-maps.cf > /dev/null <<EOF
user = postfix_db_user
password = ${DB_PASS}
hosts = 127.0.0.1
dbname = panel_core
query = SELECT 1 FROM mail_users WHERE email='%s'
EOF

sudo tee /etc/postfix/mysql-virtual-alias-maps.cf > /dev/null <<EOF
user = postfix_db_user
password = ${DB_PASS}
hosts = 127.0.0.1
dbname = panel_core
query = SELECT destination FROM mail_aliases WHERE source='%s'
EOF

sudo chmod 640 /etc/postfix/mysql-virtual-*.cf
sudo chgrp postfix /etc/postfix/mysql-virtual-*.cf

# 5. Reconfigure Postfix Core Settings to use MariaDB
sudo postconf -e "mydestination = localhost"
sudo postconf -e "virtual_mailbox_domains = proxy:mysql:/etc/postfix/mysql-virtual-mailbox-domains.cf"
sudo postconf -e "virtual_mailbox_maps = proxy:mysql:/etc/postfix/mysql-virtual-mailbox-maps.cf"
sudo postconf -e "virtual_alias_maps = proxy:mysql:/etc/postfix/mysql-virtual-alias-maps.cf"
sudo postconf -e "virtual_transport = virtual"
sudo postconf -e "virtual_uid_maps = static:5000"
sudo postconf -e "virtual_gid_maps = static:5000"
sudo postconf -e "virtual_mailbox_base = /var/vmail"

# Prepare SASL config for later SMTP relay setup
sudo postconf -e "smtp_sasl_auth_enable = yes"
sudo postconf -e "smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd"
sudo postconf -e "smtp_sasl_security_options = noanonymous"
sudo postconf -e "smtp_tls_security_level = encrypt"
sudo postconf -e "header_size_limit = 4096000"

# Create a blank SASL password file if it doesn't exist
if [ ! -f /etc/postfix/sasl_passwd ]; then
    sudo touch /etc/postfix/sasl_passwd
    sudo postmap /etc/postfix/sasl_passwd
    sudo chown root:root /etc/postfix/sasl_passwd /etc/postfix/sasl_passwd.db
    sudo chmod 0600 /etc/postfix/sasl_passwd /etc/postfix/sasl_passwd.db
fi

sudo systemctl restart postfix

# 6. Configure Dovecot
sudo tee /etc/dovecot/dovecot-sql.conf.ext > /dev/null <<EOF
driver = mysql
connect = host=127.0.0.1 dbname=panel_core user=postfix_db_user password=$DB_PASS
default_pass_scheme = SHA512-CRYPT
password_query = SELECT email as user, password FROM mail_users WHERE email='%u';
user_query = SELECT '/var/vmail/%d/%n' as home, 5000 as uid, 5000 as gid FROM mail_users WHERE email='%u';
EOF

sudo chown root:root /etc/dovecot/dovecot-sql.conf.ext
sudo chmod 600 /etc/dovecot/dovecot-sql.conf.ext

sudo sed -i 's|^#mail_location =.*|mail_location = maildir:/var/vmail/%d/%n|g' /etc/dovecot/conf.d/10-mail.conf
sudo sed -i 's|^#first_valid_uid =.*|first_valid_uid = 5000|g' /etc/dovecot/conf.d/10-mail.conf
sudo sed -i 's|^#first_valid_gid =.*|first_valid_gid = 5000|g' /etc/dovecot/conf.d/10-mail.conf

sudo sed -i 's|^!include auth-system.conf.ext|#!include auth-system.conf.ext|g' /etc/dovecot/conf.d/10-auth.conf
sudo sed -i 's|^#!include auth-sql.conf.ext|!include auth-sql.conf.ext|g' /etc/dovecot/conf.d/10-auth.conf
sudo sed -i 's|^auth_mechanisms = plain|auth_mechanisms = plain login|g' /etc/dovecot/conf.d/10-auth.conf

if ! grep -q "unix_listener /var/spool/postfix/private/auth" /etc/dovecot/conf.d/10-master.conf; then
sudo tee -a /etc/dovecot/conf.d/10-master.conf > /dev/null <<EOF
service auth {
  unix_listener /var/spool/postfix/private/auth {
    mode = 0666
    user = postfix
    group = postfix
  }
}
EOF
fi

sudo systemctl restart dovecot

echo "Success: Local Mail Engine installation and configuration completed."
exit 0