<?php
// /opt/panel/www/ajax/create_backup.php
header('Content-Type: application/json');
require_once 'security.php'; // The CSRF & Session Gatekeeper
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING); // 'backup_db' or 'backup_web'
$target = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST['target'] ?? '');

if (!in_array($action, ['backup_db', 'backup_web']) || empty($target)) {
    echo json_encode(['success' => false, 'error' => 'Invalid action or target.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $taskId = $queue->dispatch('manage_backup', [
        'action' => $action,
        'target' => $target
    ]);

    $type = ($action === 'backup_db') ? 'Database' : 'Website';
    echo json_encode(['success' => true, 'message' => "$type backup queued for $target!"]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}