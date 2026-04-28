<?php
// /opt/panel/www/ajax/delete_backup.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

// We use basename() to strip out any directory paths (like ../../) 
// to prevent malicious users from trying to delete system files.
$file = basename($_POST['file'] ?? ''); 
$type = $_POST['type'] ?? '';

if (empty($file) || empty($type)) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $queue->dispatch('delete_backup', [
        'file' => $file,
        'type' => $type
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}