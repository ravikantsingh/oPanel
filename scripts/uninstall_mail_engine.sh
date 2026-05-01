#!/bin/bash
# /opt/panel/scripts/uninstall_mail_engine.sh
# Executed by Python Daemon as root

echo "Starting Local Mail Engine Uninstallation..."

# 1. Stop services safely
sudo systemctl stop postfix dovecot >/dev/null 2>&1

# 2. Silently purge the software
export DEBIAN_FRONTEND=noninteractive
sudo apt-get purge -y postfix postfix-mysql dovecot-core dovecot-imapd dovecot-pop3d dovecot-mysql libsasl2-modules
sudo apt-get autoremove -y

# 3. Wipe configurations and the physical mailboxes
sudo rm -rf /etc/postfix /etc/dovecot
sudo userdel vmail >/dev/null 2>&1 || true
sudo rm -rf /var/vmail

echo "Success: Mail Engine safely uninstalled and RAM reclaimed."
exit 0