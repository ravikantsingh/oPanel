#!/bin/bash
# /opt/panel/scripts/db_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1

if [ -z "$PAYLOAD" ]; then
    echo "Error: No JSON payload provided."
    exit 1
fi

# 1. Extract Core Data
ACTION=$(echo "$PAYLOAD" | jq -r '.sub_action')
DB_NAME=$(echo "$PAYLOAD" | jq -r '.db_name')
DB_USER=$(echo "$PAYLOAD" | jq -r '.db_user')
DB_PASS=$(echo "$PAYLOAD" | jq -r '.db_pass // empty')

# Reconstruct the owner username from the prefix (e.g., "user1_db" -> "user1")
USERNAME=$(echo "$DB_NAME" | cut -d'_' -f1)

# 2. Extract Advanced Data
ACL=$(echo "$PAYLOAD" | jq -r '.acl // "localhost"')
CUSTOM_IP=$(echo "$PAYLOAD" | jq -r '.custom_ip // empty')
ROLE=$(echo "$PAYLOAD" | jq -r '.role // "ALL PRIVILEGES"')
CUSTOM_PRIVS=$(echo "$PAYLOAD" | jq -r '.custom_privs // empty')

# Security: Ensure db names and users only contain alphanumeric characters and underscores
if [[ ! "$DB_NAME" =~ ^[a-zA-Z0-9_]+$ ]] || [[ ! "$DB_USER" =~ ^[a-zA-Z0-9_]+$ ]]; then
    echo "Error: Invalid characters in database name or user."
    exit 1
fi

# ==========================================
# ACTION: CREATE DATABASE & USER
# ==========================================
if [ "$ACTION" == "create" ]; then

    # A. Determine Host Connection String (ACL)
    HOST="localhost"
    if [ "$ACL" == "anywhere" ]; then
        HOST="%"
    elif [ "$ACL" == "custom" ] && [ -n "$CUSTOM_IP" ] && [ "$CUSTOM_IP" != "null" ]; then
        HOST="$CUSTOM_IP"
    fi

    # B. Determine Permissions Matrix
    PRIVILEGES="ALL PRIVILEGES"
    if [ "$ROLE" == "custom" ] && [ -n "$CUSTOM_PRIVS" ]; then
        PRIVILEGES="$CUSTOM_PRIVS"
    elif [ "$ROLE" != "ALL PRIVILEGES" ] && [ "$ROLE" != "custom" ]; then
        PRIVILEGES="$ROLE"
    fi

    # C. Execute MariaDB Transactions
    mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`;"
    if [ $? -ne 0 ]; then
        echo "Error: Failed to create database $DB_NAME."
        exit 1
    fi

    mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'$HOST' IDENTIFIED BY '$DB_PASS';"
    mysql -e "GRANT $PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'$HOST';"
    mysql -e "FLUSH PRIVILEGES;"

    # D. Source of Truth UI Tracking
    mysql -e "INSERT IGNORE INTO panel_core.databases (db_name, db_user, owner_username) VALUES ('$DB_NAME', '$DB_USER', '$USERNAME');"

    echo "Success: Database $DB_NAME created with fine-grained access control ($HOST)."
    exit 0
# ==========================================
# ACTION: CHANGE PASSWORD
# ==========================================
elif [ "$ACTION" == "change_password" ]; then
    
    # Find all host connections for this user and rotate the keys
    for DB_HOST in $(mysql -N -B -e "SELECT Host FROM mysql.user WHERE User='$DB_USER'"); do
        mysql -e "ALTER USER '$DB_USER'@'$DB_HOST' IDENTIFIED BY '$DB_PASS';"
    done
    mysql -e "FLUSH PRIVILEGES;"
    
    echo "Success: Password securely rotated for database user $DB_USER."
    exit 0
# ==========================================
# ACTION: DELETE DATABASE
# ==========================================
elif [ "$ACTION" == "delete" ]; then
    
    # Drop database
    mysql -e "DROP DATABASE IF EXISTS \`$DB_NAME\`;"
    
    # Dynamically find and drop the user from ALL hosts they might be registered under
    for DB_HOST in $(mysql -N -B -e "SELECT Host FROM mysql.user WHERE User='$DB_USER'"); do
        mysql -e "DROP USER '$DB_USER'@'$DB_HOST';"
    done
    mysql -e "FLUSH PRIVILEGES;"

    # UI Cleanup Tracking
    mysql -e "DELETE FROM panel_core.databases WHERE db_name = '$DB_NAME';"
    
    echo "Success: Database $DB_NAME and user $DB_USER cleanly deleted."
    exit 0

# ==========================================
# ACTION: IMPORT DUMP
# ==========================================
elif [ "$ACTION" == "import" ]; then

    TEMP_FILE=$(echo "$PAYLOAD" | jq -r '.temp_file')

    if [ ! -f "$TEMP_FILE" ]; then
        echo "Error: Staging import file not found at $TEMP_FILE."
        exit 1
    fi

    echo "Importing SQL dump into $DB_NAME..."
    
    # Detect if file is gzipped or plain text SQL
    if [[ "$TEMP_FILE" == *.gz ]]; then
        gunzip -c "$TEMP_FILE" | mysql "$DB_NAME"
    else
        mysql "$DB_NAME" < "$TEMP_FILE"
    fi

    EXIT_CODE=$?
    
    # Always clean up the temporary staging file in /tmp/
    rm -f "$TEMP_FILE"

    if [ $EXIT_CODE -eq 0 ]; then
        echo "Success: Dump successfully imported into $DB_NAME."
        exit 0
    else
        echo "Error: MySQL import failed for $DB_NAME."
        exit 1
    fi

# ==========================================
# ACTION: COPY / CLONE DATABASE
# ==========================================
elif [ "$ACTION" == "copy" ]; then

    NEW_DB_NAME=$(echo "$PAYLOAD" | jq -r '.new_db_name')
    HOST="localhost"

    if [[ ! "$NEW_DB_NAME" =~ ^[a-zA-Z0-9_]+$ ]]; then
        echo "Error: Invalid target database name '$NEW_DB_NAME'."
        exit 1
    fi

    echo "Cloning database $DB_NAME -> $NEW_DB_NAME..."

    # 1. Create target schema
    mysql -e "CREATE DATABASE IF NOT EXISTS \`$NEW_DB_NAME\`;"
    if [ $? -ne 0 ]; then
        echo "Error: Failed to create target database schema $NEW_DB_NAME."
        exit 1
    fi

    # 2. Provision identical user credentials and grants
    mysql -e "CREATE USER IF NOT EXISTS '$NEW_DB_NAME'@'$HOST' IDENTIFIED BY '$DB_PASS';"
    mysql -e "GRANT ALL PRIVILEGES ON \`$NEW_DB_NAME\`.* TO '$NEW_DB_NAME'@'$HOST';"
    mysql -e "FLUSH PRIVILEGES;"

    # 3. Stream data in-memory directly from source to target
    mysqldump --single-transaction "$DB_NAME" | mysql "$NEW_DB_NAME"

    if [ $? -eq 0 ]; then
        # Register new database in Source of Truth
        mysql -e "INSERT IGNORE INTO panel_core.databases (db_name, db_user, owner_username) VALUES ('$NEW_DB_NAME', '$NEW_DB_NAME', '$USERNAME');"
        echo "Success: Database $DB_NAME successfully cloned to $NEW_DB_NAME."
        exit 0
    else
        echo "Error: In-memory streaming clone failed."
        exit 1
    fi

# ==========================================
# ACTION: CHECK & REPAIR TABLES
# ==========================================
elif [ "$ACTION" == "check_repair" ]; then

    echo "1. Running integrity check and auto-repair on $DB_NAME..."
    mysqlcheck --auto-repair "$DB_NAME"
    
    if [ $? -ne 0 ]; then
        echo "Error: mysqlcheck encountered errors during repair phase on $DB_NAME."
        exit 1
    fi

    echo "2. Running table optimization to reclaim disk space on $DB_NAME..."
    mysqlcheck --optimize "$DB_NAME"

    if [ $? -ne 0 ]; then
        echo "Error: mysqlcheck encountered errors during optimization phase on $DB_NAME."
        exit 1
    fi

    echo "Success: Diagnostics, repair, and optimization completed for $DB_NAME."
    exit 0

# ==========================================
# ACTION: SOFT TRANSFER OWNER
# ==========================================
elif [ "$ACTION" == "transfer_owner_soft" ]; then

    NEW_OWNER=$(echo "$PAYLOAD" | jq -r '.new_owner')
    OLD_OWNER=$(echo "$PAYLOAD" | jq -r '.old_owner')

    if ! id "$NEW_OWNER" >/dev/null 2>&1; then
        echo "Error: Linux user '$NEW_OWNER' does not exist."
        exit 1
    fi

    # Update control panel Source of Truth
    mysql -e "UPDATE panel_core.databases SET owner_username = '$NEW_OWNER' WHERE db_name = '$DB_NAME';"

    if [ $? -eq 0 ]; then
        echo "Success: Ownership of $DB_NAME soft-transferred from $OLD_OWNER to $NEW_OWNER."
        exit 0
    else
        echo "Error: Source of Truth update failed."
        exit 1
    fi

else
    echo "Error: Unknown sub_action '$ACTION'."
    exit 1
fi