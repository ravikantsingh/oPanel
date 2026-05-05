<?php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$domain = trim($_POST['domain'] ?? '');
$username = trim($_POST['username'] ?? '');
$action = trim($_POST['action'] ?? ''); // 'revert' or 'restart'

if (!$domain || !$username || !in_array($action, ['revert', 'restart'])) { 
    echo json_encode(['success' => false, 'error' => 'Invalid parameters.']); exit; 
}

try {
    $queue = new TaskQueue();
    $taskName = $action === 'revert' ? 'revert_to_php' : 'restart_app';
    $taskId = $queue->dispatch($taskName, ['domain' => $domain, 'username' => $username]);

    echo json_encode(['success' => true, 'message' => "Task queued (Task #$taskId)."]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>