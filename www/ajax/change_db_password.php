<?php
// /opt/panel/www/ajax/change_db_password.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$db_user = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['db_user'] ?? '');
$new_pass = $_POST['new_password'] ?? '';

if (empty($db_user) || empty($new_pass)) {
    echo json_encode(['success' => false, 'error' => 'User and new password are required.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $taskId = $queue->dispatch('manage_db', [
        'sub_action' => 'change_password',
        'db_user'    => $db_user,
        'db_pass'    => $new_pass
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}