#!/bin/bash
# /opt/panel/scripts/mail_dns_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
PROVIDER=$(echo "$PAYLOAD" | jq -r '.provider') # 'google' or 'microsoft'

ZONE_FILE="/etc/bind/zones/$DOMAIN.db"

if [ ! -f "$ZONE_FILE" ]; then
    echo "Error: DNS zone file for $DOMAIN not found."
    exit 1
fi

# 1. Create a safe backup before modifying
cp "$ZONE_FILE" "${ZONE_FILE}.bak"

# 2. Increment the SOA Serial Number (Crucial for global DNS propagation)
# Finds the current 10-digit serial (YYYYMMDDNN) and adds 1
CURRENT_SERIAL=$(grep -oE '[0-9]{10}' "$ZONE_FILE" | head -1)
if [ -n "$CURRENT_SERIAL" ]; then
    NEW_SERIAL=$((CURRENT_SERIAL + 1))
    sed -i "s/$CURRENT_SERIAL/$NEW_SERIAL/g" "$ZONE_FILE"
fi

# 3. Scrub existing Mail Records to prevent conflicts
# Deletes old MX, SPF, Autodiscover, and DMARC lines cleanly
sed -i '/ IN MX /d' "$ZONE_FILE"
sed -i '/v=spf1/d' "$ZONE_FILE"
sed -i '/autodiscover/d' "$ZONE_FILE"
sed -i '/_dmarc/d' "$ZONE_FILE"

# 4. Inject Provider-Specific Records
if [ "$PROVIDER" == "google" ]; then
    cat <<EOF >> "$ZONE_FILE"
; --- Google Workspace Mail Records ---
@   IN  MX  1   smtp.google.com.
@   IN  TXT     "v=spf1 include:_spf.google.com ~all"
_dmarc  IN  TXT "v=DMARC1; p=quarantine; sp=quarantine; aspf=r; adkim=r;"
EOF

elif [ "$PROVIDER" == "microsoft" ]; then
    # Microsoft requires the MX record to match their tenant format (domain-com)
    MS_FORMAT=$(echo "$DOMAIN" | tr '.' '-')
    
    cat <<EOF >> "$ZONE_FILE"
; --- Microsoft 365 Mail Records ---
@   IN  MX  0   ${MS_FORMAT}.mail.protection.outlook.com.
@   IN  TXT     "v=spf1 include:spf.protection.outlook.com -all"
autodiscover    IN  CNAME   autodiscover.outlook.com.
_dmarc  IN  TXT "v=DMARC1; p=quarantine; sp=quarantine; aspf=r; adkim=r;"
EOF

else
    echo "Error: Unknown mail provider '$PROVIDER'."
    rm -f "${ZONE_FILE}.bak"
    exit 1
fi

# 5. SRE Safety Check: Validate the Zone File
# named-checkzone ensures we didn't break the DNS formatting
if named-checkzone "$DOMAIN" "$ZONE_FILE" > /dev/null 2>&1; then
    systemctl reload bind9
    
    # Optional: Track this in the Source of Truth UI database
    # mysql -e "UPDATE panel_core.domains SET mail_provider = '$PROVIDER' WHERE domain_name = '$DOMAIN';"
    
    rm -f "${ZONE_FILE}.bak"
    echo "Success: $PROVIDER mail records applied flawlessly for $DOMAIN."
    exit 0
else
    # THE ROLLBACK: If BIND9 rejects the syntax, revert immediately to keep the site online
    mv "${ZONE_FILE}.bak" "$ZONE_FILE"
    echo "Error: DNS syntax check failed. Rolled back to previous state safely."
    exit 1
fi