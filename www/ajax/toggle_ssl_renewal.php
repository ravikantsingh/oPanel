<?php
// /opt/panel/www/ajax/toggle_ssl_renewal.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$domain = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL);
$enable = filter_var($_POST['enable'] ?? false, FILTER_VALIDATE_BOOLEAN);

if (!$domain) { echo json_encode(['success' => false, 'error' => 'Domain required.']); exit; }

try {
    $queue = new TaskQueue();
    $taskId = $queue->dispatch('install_ssl', [
        'sub_action' => 'toggle_renewal',
        'domain' => $domain,
        'status' => $enable ? 'enable' : 'disable'
    ]);
    echo json_encode(['success' => true, 'task_id' => $taskId]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>