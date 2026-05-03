<?php
// /opt/panel/www/ajax/unban_ip.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$ip = trim(filter_input(INPUT_POST, 'ip', FILTER_SANITIZE_STRING));
$jail = trim(filter_input(INPUT_POST, 'jail', FILTER_SANITIZE_STRING));

// Strict IP validation before it ever hits the worker queue
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    echo json_encode(['success' => false, 'error' => 'Invalid IP address format.']);
    exit;
}

if (empty($jail)) {
    echo json_encode(['success' => false, 'error' => 'Jail name is required.']);
    exit;
}

try {
    $queue = new TaskQueue();
    
    $payload = [
        'sub_action' => 'unban',
        'ip'         => $ip,
        'jail'       => $jail
    ];

    $taskId = $queue->dispatch('manage_fail2ban', $payload);

    echo json_encode([
        'success' => true, 
        'message' => "Unban request for $ip queued successfully! (Task ID: $taskId)"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}
?>