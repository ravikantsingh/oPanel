<?php
// /opt/panel/www/ajax/manage_smtp_relay.php
header('Content-Type: application/json');
require_once 'security.php'; // Enforce CSRF and Session checks
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

$sub_action = filter_input(INPUT_POST, 'sub_action', FILTER_SANITIZE_STRING);

try {
    $queue = new TaskQueue();

    if ($sub_action === 'enable') {
        $payload = [
            'sub_action' => 'enable',
            'provider'   => $_POST['provider'] ?? 'custom',
            'host'       => $_POST['host'] ?? '',
            'port'       => $_POST['port'] ?? 587,
            'user'       => $_POST['user'] ?? '',
            'pass'       => $_POST['pass'] ?? '' // Password passed to payload, not DB
        ];

        // Dispatch to daemon queue
        $queue->dispatch('setup_smtp_relay', $payload);
        echo json_encode(['success' => true, 'message' => "SMTP Relay configuration queued!"]);
    } 
    elseif ($sub_action === 'disable') {
        $queue->dispatch('setup_smtp_relay', ['sub_action' => 'disable']);
        echo json_encode(['success' => true, 'message' => "Relay removal queued. Delivery reverting to local."]);
    } 
    else {
        echo json_encode(['success' => false, 'error' => 'Invalid sub_action provided.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}
?>