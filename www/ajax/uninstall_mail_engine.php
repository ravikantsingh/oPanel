<?php
// /opt/panel/www/ajax/uninstall_mail_engine.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

try {
    $queue = new TaskQueue();
    $queue->dispatch('uninstall_mail_engine', []);
    echo json_encode(['success' => true, 'message' => "Mail Engine uninstallation queued!"]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}
?>