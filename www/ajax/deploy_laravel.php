<?php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$domain = trim($_POST['domain'] ?? '');
$username = trim($_POST['username'] ?? '');

if (!$domain || !$username) { 
    echo json_encode(['success' => false, 'error' => 'Missing domain or user context.']); 
    exit; 
}

try {
    $queue = new TaskQueue();
    $taskId = $queue->dispatch('deploy_laravel', [
        'domain' => $domain, 
        'username' => $username
    ]);

    echo json_encode(['success' => true, 'message' => "Laravel Deployment Initiated! Task queued (Task #$taskId)."]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>