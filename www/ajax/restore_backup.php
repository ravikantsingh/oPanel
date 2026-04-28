<?php
// /opt/panel/www/ajax/restore_backup.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

// Strict sanitization to prevent Directory Traversal attacks
$file = basename($_POST['file'] ?? ''); 
$type = $_POST['type'] ?? '';
$target = $_POST['target'] ?? '';

if (empty($file) || empty($type) || empty($target)) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $queue->dispatch('restore_backup', [
        'file' => $file,
        'type' => $type,
        'target' => $target
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}