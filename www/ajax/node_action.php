<?php
// /opt/panel/www/ajax/node_action.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$domain = strtolower(trim(filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL)));
$username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
$app_root = trim(filter_input(INPUT_POST, 'app_root', FILTER_SANITIZE_STRING));
$sub_action = trim(filter_input(INPUT_POST, 'sub_action', FILTER_SANITIZE_STRING));

if (!$domain || !$username || !$sub_action) {
    echo json_encode(['success' => false, 'error' => 'Missing required data.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $payload = [
        'domain' => $domain,
        'username' => $username,
        'app_root' => $app_root,
        'sub_action' => $sub_action
    ];

    $taskId = $queue->dispatch('node_action', $payload);

    echo json_encode([
        'success' => true, 
        'message' => "PM2 action '$sub_action' queued for $domain!"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>