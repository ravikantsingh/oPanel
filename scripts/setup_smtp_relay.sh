#!/bin/bash
# /opt/panel/scripts/setup_smtp_relay.sh
# Executed by Python Daemon as root

PAYLOAD=$1
ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action') # "enable" or "disable"

# ==========================================
# ACTION: ENABLE RELAY
# ==========================================
if [ "$ACTION" == "enable" ]; then
    PROVIDER=$(echo "$PAYLOAD" | jq -r '.provider')
    HOST=$(echo "$PAYLOAD" | jq -r '.host')
    PORT=$(echo "$PAYLOAD" | jq -r '.port')
    USER=$(echo "$PAYLOAD" | jq -r '.user')
    PASS=$(echo "$PAYLOAD" | jq -r '.pass')

    echo "[+] Configuring Postfix for External SMTP Relay..."

    # 1. Write the raw authentication file safely
    # SRE Security: We write directly to the file to prevent the password from appearing in `ps aux` process lists
    cat <<EOF > /etc/postfix/sasl_passwd
[${HOST}]:${PORT} ${USER}:${PASS}
EOF

    # 2. Cryptographic Hashing & System Lockdown
    # Convert text to Berkeley DB hash format, then restrict permissions immediately to root-only
    sudo postmap /etc/postfix/sasl_passwd
    sudo chown root:root /etc/postfix/sasl_passwd /etc/postfix/sasl_passwd.db
    sudo chmod 0600 /etc/postfix/sasl_passwd /etc/postfix/sasl_passwd.db

    # 3. Inject configuration directly into active memory without corrupting master.cf
    sudo postconf -e "relayhost = [${HOST}]:${PORT}"
    sudo postconf -e "smtp_sasl_auth_enable = yes"
    sudo postconf -e "smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd"
    sudo postconf -e "smtp_sasl_security_options = noanonymous"
    sudo postconf -e "smtp_tls_security_level = encrypt"

    # 4. Sync the Source of Truth (Database)
    mysql -e "UPDATE panel_core.mail_global_settings SET smtp_relay_active=1, relay_provider='${PROVIDER}', relay_host='${HOST}', relay_port=${PORT}, relay_user='${USER}' WHERE id=1;"

    sudo systemctl restart postfix
    echo "Success: SMTP Relay enabled and routing through ${HOST}:${PORT}."
    exit 0

# ==========================================
# ACTION: DISABLE RELAY (ROLLBACK)
# ==========================================
elif [ "$ACTION" == "disable" ]; then
    echo "[+] Reverting to direct local mail delivery..."

    # 1. Destroy the authentication files completely
    sudo rm -f /etc/postfix/sasl_passwd
    sudo rm -f /etc/postfix/sasl_passwd.db

    # 2. Extract ALL relay instructions from memory
    sudo postconf -X "relayhost"
    sudo postconf -X "smtp_sasl_auth_enable"
    sudo postconf -X "smtp_sasl_password_maps"
    sudo postconf -X "smtp_sasl_security_options"
    # We leave smtp_tls_security_level=encrypt intact as it is good practice for all outbound mail.

    # 3. Sync the Source of Truth
    mysql -e "UPDATE panel_core.mail_global_settings SET smtp_relay_active=0, relay_provider='none', relay_host='', relay_port=587, relay_user='' WHERE id=1;"

    sudo systemctl restart postfix
    echo "Success: External relay detached. Mail is now routing locally."
    exit 0

else
    echo "Error: Unknown action."
    exit 1
fi