#!/bin/bash
# /opt/panel/scripts/waf_updater.sh
# Automated OWASP Core Rule Set Updater

CRS_DIR="/usr/share/modsecurity-crs"
BACKUP_DIR="/usr/share/modsecurity-crs.bak"
TEMP_DIR="/tmp/owasp-crs-update"
REPO_URL="https://github.com/coreruleset/coreruleset.git"

# 1. Create a safety backup of the currently working rules
rm -rf "$BACKUP_DIR"
cp -r "$CRS_DIR" "$BACKUP_DIR"

# 2. Download the absolute latest threat rules from OWASP
rm -rf "$TEMP_DIR"
git clone --depth 1 "$REPO_URL" "$TEMP_DIR" > /dev/null 2>&1

if [ ! -d "$TEMP_DIR/rules" ]; then
    echo "Error: Failed to download new rules from GitHub."
    exit 1
fi

# 3. Swap the old rules for the new ones
rm -rf "$CRS_DIR/rules"
cp -r "$TEMP_DIR/rules" "$CRS_DIR/rules"

# 4. Apply the Nginx Syntax Patch (The Crucial Step!)
# This ensures the new rules don't crash Nginx with Apache syntax
find "$CRS_DIR" -type f -exec sed -i 's/IncludeOptional/Include/g' {} +

# 5. The "Source of Truth" Safety Test
if nginx -t > /dev/null 2>&1; then
    # Syntax is perfect. Reload the WAF!
    systemctl reload nginx
    echo "Success: WAF rules updated and Nginx secured."
    rm -rf "$TEMP_DIR"
    exit 0
else
    # The new rules broke something! Roll back immediately.
    echo "Critical Error: New WAF rules failed the Nginx syntax test!"
    echo "Initiating emergency rollback..."
    
    rm -rf "$CRS_DIR"
    mv "$BACKUP_DIR" "$CRS_DIR"
    
    echo "Rollback complete. The server remains online using the previous secure rules."
    exit 1
fi