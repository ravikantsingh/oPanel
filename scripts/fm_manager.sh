#!/bin/bash
# /opt/panel/scripts/fm_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')
PHP_VER=$(echo "$PAYLOAD" | jq -r '.php_version')
FM_PASS=$(echo "$PAYLOAD" | jq -r '.fm_password')

FM_DIR="/home/$USERNAME/web/$DOMAIN/filemanager"
DOC_ROOT="/home/$USERNAME/web/$DOMAIN/public_html"
VHOST="/etc/nginx/sites-available/$DOMAIN.conf"
WEB_ROOT="/home/$USERNAME/web/$DOMAIN"

if [ ! -f "$VHOST" ]; then
    echo "Error: Nginx configuration for $DOMAIN not found."
    exit 1
fi

# 1. Provision the Isolated Directory & Download TFM
mkdir -p "$FM_DIR"
wget -qO "$FM_DIR/index.php" "https://raw.githubusercontent.com/prasathmani/tinyfilemanager/master/tinyfilemanager.php"

# 2. Cryptographically Hash the Password
HASH=$(php -r "echo password_hash('$FM_PASS', PASSWORD_DEFAULT);")

# 3. Generate the Custom Configuration
# We hard-lock the root_path to their public_html so they cannot browse the server root
cat <<EOF > "$FM_DIR/config.php"
<?php
\$auth_users = array('$USERNAME' => '$HASH');
\$readonly_users = array();
\$use_auth = true;
\$theme = 'dark';
\$root_path = '$DOC_ROOT';
\$root_url = '';
// Dynamically detect HTTPS so cookies don't break over HTTP
\$is_https = isset(\$_SERVER['HTTPS']) && (\$_SERVER['HTTPS'] === 'on' || \$_SERVER['HTTPS'] == 1);
?>
EOF

# Fetch the secret token from the database
WEBHOOK_TOKEN=$(mysql -N -s -e "SELECT webhook_token FROM panel_core.users WHERE username='$USERNAME';")

# NEW CRYPTOGRAPHIC SSO INJECTION
cat << 'EOF' > /tmp/sso_logic.txt
/* === CRYPTOGRAPHIC CROSS-DOMAIN SSO === */
if (isset($_GET['sso_t']) && isset($_GET['sso_h'])) {
    $expected = hash_hmac('sha256', 'REPLACE_DOMAIN|' . $_GET['sso_t'], 'REPLACE_TOKEN');
    
    // Verify the signature is flawless AND the token is less than 60 seconds old
    if (hash_equals($expected, $_GET['sso_h']) && (time() - $_GET['sso_t'] < 60)) {
        session_name('filemanager');
        session_start();
        
        // THE FIX: Tiny File Manager strictly requires this specific array format!
        $_SESSION['filemanager']['logged'] = 'REPLACE_USER';
        
        session_write_close();
        
        // Redirect to clear the tokens from the URL bar immediately
        header("Location: index.php");
        exit;
    }
}
/* ====================================== */
EOF

# Swap the placeholders securely
sed -i "s/REPLACE_DOMAIN/$DOMAIN/g" /tmp/sso_logic.txt
sed -i "s/REPLACE_TOKEN/$WEBHOOK_TOKEN/g" /tmp/sso_logic.txt
sed -i "s/REPLACE_USER/$USERNAME/g" /tmp/sso_logic.txt

# Inject into TFM
awk 'NR==FNR{sso=sso$0"\n";next} /^<\?php/ && !inserted {print; printf "%s", sso; inserted=1; next} 1' /tmp/sso_logic.txt "$FM_DIR/index.php" > "$FM_DIR/index_tmp.php"
mv "$FM_DIR/index_tmp.php" "$FM_DIR/index.php"

rm /tmp/sso_logic.txt
# ---------------------------------------

# 4. Strict Ownership (Crucial for ACL Isolation)
chown -R $USERNAME:$USERNAME "$FM_DIR"

# 5. Safe Nginx Root Injection with open_basedir override
if ! grep -q "location \^~ /filemanager" "$VHOST"; then
    # Backup the config BEFORE modifying it
    cp "$VHOST" "${VHOST}.bak"

    awk -v web_root="$WEB_ROOT" -v doc_root="$DOC_ROOT" -v fm_dir="$FM_DIR" -v php_ver="$PHP_VER" -v user="$USERNAME" '/location \/ \{/ {
        print "    # Isolated Tiny File Manager"
        print "    location ^~ /filemanager {"
        print "        modsecurity off;"
        print "        root " web_root ";"
        print "        index index.php;"
        print "        location ~ \\.php$ {"
        print "            include snippets/fastcgi-php.conf;"
        print "            fastcgi_param PHP_ADMIN_VALUE \"open_basedir=" fm_dir ":" doc_root ":/tmp:/var/lib/php/sessions\";"
        print "            fastcgi_pass unix:/run/php/php" php_ver "-fpm-" user ".sock;"
        print "        }"
        print "    }"
        print ""
    } 1' "$VHOST" > "${VHOST}.tmp" && mv "${VHOST}.tmp" "$VHOST"
fi

# 6. Test and Reload
if nginx -t > /dev/null 2>&1; then
    systemctl reload nginx
    # Clean up the backup file since it succeeded
    rm -f "${VHOST}.bak"
    echo "Success: File Manager deployed securely for $DOMAIN."
    exit 0
else
    # Flawless Rollback Protocol
    mv "${VHOST}.bak" "$VHOST" 2>/dev/null
    echo "Error: Nginx syntax failed during File Manager injection. Rolled back safely."
    exit 1
fi