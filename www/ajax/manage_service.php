<?php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
$service = filter_input(INPUT_POST, 'service', FILTER_SANITIZE_STRING);

try {
    $queue = new TaskQueue();
    $taskId = $queue->dispatch('manage_service', [
        'sub_action' => $action,
        'service'    => $service
    ]);

    echo json_encode(['success' => true, 'message' => "Command '$action' queued for $service. (Task ID: $taskId)"]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>