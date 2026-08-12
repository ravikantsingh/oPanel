<?php
// /opt/panel/www/ajax/manage_db.php
header('Content-Type: application/json');
require_once 'security.php';

require_once '../classes/Database.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$action = trim(filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING) ?? '');

if (empty($action)) {
    echo json_encode(['success' => false, 'error' => 'Database action parameter is required.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $db = Database::getInstance()->getConnection();

    switch ($action) {

        // ==========================================
        // 2. IMPORT DUMP
        // ==========================================
        case 'import':
            $db_name = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['db_name'] ?? '');
            $file = $_FILES['dump_file'] ?? null;

            if (empty($db_name)) {
                echo json_encode(['success' => false, 'error' => 'Target database is required.']);
                exit;
            }

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'error' => 'File upload failed or exceeds PHP upload limits.']);
                exit;
            }

            // Strict extension validation (.sql or .sql.gz)
            $filename = basename($file['name']);
            if (!preg_match('/\.(sql|sql\.gz|\.gz)$/i', $filename)) {
                echo json_encode(['success' => false, 'error' => 'Invalid file format. Only .sql or .sql.gz files are allowed.']);
                exit;
            }

            // Save to temporary staging path
            $safe_filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
            $staging_path = '/tmp/import_' . time() . '_' . $safe_filename;

            if (!move_uploaded_file($file['tmp_name'], $staging_path)) {
                echo json_encode(['success' => false, 'error' => 'Failed to stage import file on the server.']);
                exit;
            }

            // Restrict permissions on temporary file
            chmod($staging_path, 0640);

            $taskId = $queue->dispatch('manage_db', [
                'sub_action' => 'import',
                'db_name'    => $db_name,
                'temp_file'  => $staging_path
            ]);

            echo json_encode([
                'success' => true,
                'message' => "Import queued for $db_name! Task ID: $taskId"
            ]);
            break;

        // ==========================================
        // 3. COPY / CLONE DATABASE
        // ==========================================
        case 'copy':
            $src_db_name = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['src_db_name'] ?? '');
            $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING) ?? '');
            $db_suffix = trim(filter_input(INPUT_POST, 'db_suffix', FILTER_SANITIZE_STRING) ?? '');
            $db_pass = $_POST['db_pass'] ?? '';

            if (empty($src_db_name) || empty($username) || empty($db_suffix) || empty($db_pass)) {
                echo json_encode(['success' => false, 'error' => 'All copy parameters and a password are required.']);
                exit;
            }

            if (!preg_match('/^[a-z0-9]+$/', $username) || !preg_match('/^[a-zA-Z0-9]+$/', $db_suffix)) {
                echo json_encode(['success' => false, 'error' => 'User and database names can only contain alphanumeric characters.']);
                exit;
            }

            $new_db_name = $username . '_' . $db_suffix;

            if (strlen($new_db_name) > 32) {
                echo json_encode(['success' => false, 'error' => 'Target database name exceeds 32 characters.']);
                exit;
            }

            if (strlen($db_pass) < 8) {
                echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters long.']);
                exit;
            }

            $taskId = $queue->dispatch('manage_db', [
                'sub_action'  => 'copy',
                'db_name'     => $src_db_name,
                'new_db_name' => $new_db_name,
                'db_pass'     => $db_pass,
                'username'    => $username
            ]);

            echo json_encode([
                'success' => true,
                'message' => "Cloning '$src_db_name' to '$new_db_name' queued! Task ID: $taskId"
            ]);
            break;

        // ==========================================
        // 4. CHECK & REPAIR TABLES
        // ==========================================
        case 'check_repair':
            $db_name = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['db_name'] ?? '');

            if (empty($db_name)) {
                echo json_encode(['success' => false, 'error' => 'Database name is required.']);
                exit;
            }

            $taskId = $queue->dispatch('manage_db', [
                'sub_action' => 'check_repair',
                'db_name'    => $db_name
            ]);

            echo json_encode([
                'success' => true,
                'message' => "Diagnostics and repair queued for $db_name! Task ID: $taskId"
            ]);
            break;

        // ==========================================
        // 5. SOFT TRANSFER OWNER
        // ==========================================
        case 'transfer_owner':
            $db_name = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['db_name'] ?? '');
            $new_owner = trim(filter_input(INPUT_POST, 'new_owner', FILTER_SANITIZE_STRING) ?? '');

            if (empty($db_name) || empty($new_owner)) {
                echo json_encode(['success' => false, 'error' => 'Database name and new owner are required.']);
                exit;
            }

            // Verify the current owner
            $stmt = $db->prepare("SELECT owner_username FROM databases WHERE db_name = ?");
            $stmt->execute([$db_name]);
            $current = $stmt->fetch();

            if (!$current) {
                echo json_encode(['success' => false, 'error' => 'Database record not found in system.']);
                exit;
            }

            $old_owner = $current['owner_username'];

            if ($old_owner === $new_owner) {
                echo json_encode(['success' => false, 'error' => 'Database is already owned by this user.']);
                exit;
            }

            // Verify target user exists
            $stmtUser = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmtUser->execute([$new_owner]);
            if (!$stmtUser->fetch()) {
                echo json_encode(['success' => false, 'error' => "Target user '$new_owner' does not exist."]);
                exit;
            }

            $taskId = $queue->dispatch('manage_db', [
                'sub_action' => 'transfer_owner_soft',
                'db_name'    => $db_name,
                'old_owner'  => $old_owner,
                'new_owner'  => $new_owner
            ]);

            echo json_encode([
                'success' => true,
                'message' => "Soft move queued: $db_name transferred to $new_owner! Task ID: $taskId"
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => "Unknown database action '$action'."]);
            break;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database Queue Error: ' . $e->getMessage()]);
}