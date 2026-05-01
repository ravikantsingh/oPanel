<?php
// /opt/panel/www/ajax/install_mail_engine.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

try {
    $queue = new TaskQueue();
    // Dispatch the task with an empty payload (the script doesn't need specific data to install the stack)
    $queue->dispatch('install_mail_engine', []);

    echo json_encode(['success' => true, 'message' => "Mail Engine installation queued successfully!"]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}
?>