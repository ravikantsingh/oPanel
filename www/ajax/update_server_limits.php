<?php
// /opt/panel/www/ajax/update_server_limits.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

// Validate and sanitize the numbers
$upload_size = filter_input(INPUT_POST, 'upload_size', FILTER_VALIDATE_INT);
$max_time = filter_input(INPUT_POST, 'max_time', FILTER_VALIDATE_INT);

if (!$upload_size || !$max_time) {
    echo json_encode(['success' => false, 'error' => 'Invalid numerical values provided.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $queue->dispatch('update_limits', [
        'upload_size' => $upload_size,
        'max_time' => $max_time
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}