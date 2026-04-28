#!/bin/bash
# /opt/panel/scripts/dns_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
SERVER_IP=$(echo "$PAYLOAD" | jq -r '.server_ip')

ZONE_FILE="/etc/bind/zones/db.$DOMAIN"
CONF_FILE="/etc/bind/named.conf.local"

# 1. Generate a dynamic serial number (YYYYMMDD01 format)
SERIAL=$(date +%Y%m%d01)

# 2. Create the standard DNS Zone File
cat <<EOF > "$ZONE_FILE"
\$TTL    86400
@       IN      SOA     ns1.$DOMAIN. admin.$DOMAIN. (
                     $SERIAL         ; Serial
                     3600            ; Refresh
                     1800            ; Retry
                     604800          ; Expire
                     86400 )         ; Minimum TTL

; Name Servers
@       IN      NS      ns1.$DOMAIN.
@       IN      NS      ns2.$DOMAIN.

; Standard A Records
@       IN      A       $SERVER_IP
ns1     IN      A       $SERVER_IP
ns2     IN      A       $SERVER_IP
www     IN      A       $SERVER_IP
mail    IN      A       $SERVER_IP

; CNAME Records
ftp     IN      CNAME   $DOMAIN.

; MX Records (Mail)
@       IN      MX      10 mail.$DOMAIN.

; TXT Records (SPF for Email Deliverability)
@       IN      TXT     "v=spf1 a mx ip4:$SERVER_IP ~all"
EOF

# 3. Secure the zone file
chown bind:bind "$ZONE_FILE"
chmod 644 "$ZONE_FILE"

# 4. Inject the zone into BIND9's master config (if it doesn't already exist)
if ! grep -q "zone \"$DOMAIN\"" "$CONF_FILE"; then
    echo "zone \"$DOMAIN\" { type master; file \"$ZONE_FILE\"; };" >> "$CONF_FILE"
fi

# 5. Check configuration syntax and reload the DNS server
named-checkconf
if [ $? -eq 0 ]; then
    systemctl reload bind9
    # ---> NEW: SOURCE OF TRUTH TRACKING <---
    # Inject all the standard baseline records generated in the zone file
    mysql -e "INSERT IGNORE INTO panel_core.dns_records (domain_name, record_name, record_type, record_value) VALUES 
    ('$DOMAIN', '@', 'A', '$SERVER_IP'), 
    ('$DOMAIN', 'ns1', 'A', '$SERVER_IP'), 
    ('$DOMAIN', 'ns2', 'A', '$SERVER_IP'), 
    ('$DOMAIN', 'www', 'A', '$SERVER_IP'), 
    ('$DOMAIN', 'mail', 'A', '$SERVER_IP'),
    ('$DOMAIN', 'ftp', 'CNAME', '$DOMAIN.'), 
    ('$DOMAIN', '@', 'MX', '10 mail.$DOMAIN.'), 
    ('$DOMAIN', '@', 'TXT', 'v=spf1 a mx ip4:$SERVER_IP ~all');"
    
    echo "Success: DNS Zone automatically generated and loaded for $DOMAIN."
    exit 0
else
    echo "Error: BIND9 configuration test failed."
    exit 1
fi