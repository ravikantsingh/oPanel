<?php
// /opt/panel/www/ajax/manage_firewall.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// ---> NEW: Dynamically catch the action (defaults to 'allow' for your existing modal form)
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING) ?: 'allow';
$port = filter_input(INPUT_POST, 'port', FILTER_VALIDATE_INT);
$protocol = filter_input(INPUT_POST, 'protocol', FILTER_SANITIZE_STRING);

if (!$port || $port < 1 || $port > 65535) {
    echo json_encode(['success' => false, 'error' => 'Invalid port number.']);
    exit;
}

if (!in_array($protocol, ['tcp', 'udp'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid protocol.']);
    exit;
}

try {
    $queue = new TaskQueue();
    
    $payload = [
        'sub_action' => $action, // Passes 'allow' or 'delete' to your bash script
        'port'       => $port,
        'protocol'   => $protocol
    ];

    $taskId = $queue->dispatch('manage_firewall', $payload);

    $verb = ($action === 'delete') ? 'closed' : 'opened';
    echo json_encode([
        'success' => true, 
        'message' => "Port $port/$protocol queued to be $verb! (Task ID: $taskId)"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}