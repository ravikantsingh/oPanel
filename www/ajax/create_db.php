<?php
// /opt/panel/www/ajax/create_db.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------

require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// 1. Sanitize Core Inputs
$username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
$db_suffix = trim(filter_input(INPUT_POST, 'db_suffix', FILTER_SANITIZE_STRING));
$db_pass = $_POST['db_pass'] ?? '';

// 2. Extract Advanced Settings (with strict sanitization)
$acl = $_POST['db_acl'] ?? 'localhost';
$custom_ip = filter_input(INPUT_POST, 'db_custom_ip', FILTER_VALIDATE_IP);
$role = $_POST['db_role'] ?? 'ALL PRIVILEGES';
// Only allow uppercase letters, commas, and spaces for MySQL privileges
$custom_privs = preg_replace('/[^A-Z, ]/', '', $_POST['custom_priv_string'] ?? '');

// 3. Validate Format
if (!preg_match('/^[a-z0-9]+$/', $username) || !preg_match('/^[a-zA-Z0-9]+$/', $db_suffix)) {
    echo json_encode(['success' => false, 'error' => 'Username and Database name can only contain letters and numbers.']);
    exit;
}

if (strlen($db_pass) < 8) {
    echo json_encode(['success' => false, 'error' => 'Database password must be at least 8 characters.']);
    exit;
}

$full_db_name = $username . '_' . $db_suffix;

if (strlen($full_db_name) > 32) {
    echo json_encode(['success' => false, 'error' => 'Combined database name exceeds 32 characters.']);
    exit;
}

// 4. Dispatch to Task Queue
try {
    $queue = new TaskQueue();
    
    $payload = [
        'sub_action'   => 'create',
        'db_name'      => $full_db_name,
        'db_user'      => $full_db_name,
        'db_pass'      => $db_pass,
        'acl'          => $acl,
        'custom_ip'    => $custom_ip,
        'role'         => $role,
        'custom_privs' => $custom_privs
    ];

    $taskId = $queue->dispatch('create_db', $payload);

    echo json_encode([
        'success' => true, 
        'message' => "Advanced DB '$full_db_name' queued for creation!"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}