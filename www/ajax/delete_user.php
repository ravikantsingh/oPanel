<?php
// /opt/panel/www/ajax/delete_user.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$username = preg_replace('/[^a-z0-9]/', '', $_POST['username'] ?? '');

// Security: Prevent deleting the root user or the oPanel admin!
if (empty($username) || $username === 'root' || $username === 'panel_user') {
    echo json_encode(['success' => false, 'error' => 'Invalid or protected system user.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $queue->dispatch('delete_user', [
        'sub_action' => 'delete',
        'username' => $username
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}