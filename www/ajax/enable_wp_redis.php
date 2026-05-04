<?php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$domain = trim(filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL));
$username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));

if (!$domain || !$username) {
    echo json_encode(['success' => false, 'error' => 'Missing domain or user context.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $payload = ['domain' => $domain, 'username' => $username];
    
    // Dispatch the task specifically to our new bash worker
    $taskId = $queue->dispatch('wp_redis_manager', $payload);

    echo json_encode([
        'success' => true, 
        'message' => "Redis Cache injection queued for $domain! Task ID: $taskId"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Task execution failed: ' . $e->getMessage()]);
}
?>