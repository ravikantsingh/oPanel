#!/bin/bash
# /opt/panel/scripts/waf_updater.sh
# Dynamic OWASP CRS Updater (Memory-Safe & Multi-Version)

CRS_DIR="/usr/share/modsecurity-crs"
BACKUP_DIR="/usr/share/modsecurity-crs.bak"
TEMP_DIR="/tmp/owasp-crs-update"
CONFIG_FILE="/opt/panel/www/config/waf_settings.json"
REPO_URL="https://github.com/coreruleset/coreruleset.git"

# 1. Read User Preference from Panel UI (Default to v3.3 if file is missing)
if [ -f "$CONFIG_FILE" ]; then
    BRANCH=$(jq -r '.waf_branch // "v3.3/master"' "$CONFIG_FILE")
else
    BRANCH="v3.3/master"
fi

# 2. Create a safety backup
rm -rf "$BACKUP_DIR"
cp -r "$CRS_DIR" "$BACKUP_DIR"

# 3. Download the specific Branch or Tag chosen by the user
rm -rf "$TEMP_DIR"
git clone -b "$BRANCH" --depth 1 "$REPO_URL" "$TEMP_DIR" > /dev/null 2>&1

if [ ! -d "$TEMP_DIR/rules" ]; then
    echo "Error: Failed to download WAF rules (Branch/Tag: $BRANCH) from GitHub."
    exit 1
fi

# 4. Swap the old rules for the new rules
rm -rf "$CRS_DIR/rules"
cp -r "$TEMP_DIR/rules" "$CRS_DIR/rules"

# 5. DYNAMIC ARCHITECTURE SWITCHER
# Because CRS v3 and v4 use different architectures, we must explicitly copy 
# the setup file from the downloaded repo to guarantee absolute compatibility.
cp "$TEMP_DIR/crs-setup.conf.example" "$CRS_DIR/crs-setup.conf"

# Force Nginx to load the newly formatted setup file and rules
echo "Include $CRS_DIR/crs-setup.conf" > "$CRS_DIR/owasp-crs.load"
echo "Include $CRS_DIR/rules/*.conf" >> "$CRS_DIR/owasp-crs.load"

# 6. Apply the Nginx Syntax Patch (Crucial for all versions)
find "$CRS_DIR" -type f -exec sed -i 's/IncludeOptional/Include/g' {} +

# 7. THE MEMORY-SAFE SAFETY TEST
if nginx -t > /dev/null 2>&1; then
    # CRITICAL: Restart clears RAM allocations. Reload duplicates RAM.
    systemctl restart nginx
    echo "Success: WAF updated to $BRANCH and Nginx secured safely."
    rm -rf "$TEMP_DIR"
    exit 0
else
    # The rules or architecture failed! Rollback everything immediately.
    echo "Critical Error: WAF $BRANCH failed Nginx syntax test!"
    
    rm -rf "$CRS_DIR"
    mv "$BACKUP_DIR" "$CRS_DIR"
    
    # Restart to ensure Nginx stays online with the old, safe rules
    systemctl restart nginx
    echo "Rollback complete. The server remains online using the previous rules."
    exit 1
fi