<?php
// /opt/panel/www/ajax/delete_db.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------

require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// Strict sanitization to prevent SQL injection
$db_name = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['db_name'] ?? '');

if (empty($db_name)) {
    echo json_encode(['success' => false, 'error' => 'Database name is required.']);
    exit;
}

try {
    $queue = new TaskQueue();
    
    // Dispatch the payload to your db_manager.sh script
    $taskId = $queue->dispatch('delete_db', [
        'sub_action' => 'delete',
        'db_name'    => $db_name,
        'db_user'    => $db_name // In our architecture, the DB and User share the exact same name
    ]);

    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Queue error: ' . $e->getMessage()]);
}