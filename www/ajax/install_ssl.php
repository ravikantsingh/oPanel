<?php
// /opt/panel/www/ajax/install_ssl.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------

require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$domain = strtolower(trim(filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL)));
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

if (!preg_match('/^(?!\-)(?:(?:[a-zA-Z\d][a-zA-Z\d\-]{0,61})?[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/', $domain)) {
    echo json_encode(['success' => false, 'error' => 'Invalid domain name format.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
    exit;
}

try {
    $queue = new TaskQueue();
    
    $payload = [
        'sub_action' => 'install',
        'domain'     => $domain,
        'email'      => $email
    ];

    $taskId = $queue->dispatch('install_ssl', $payload);

    echo json_encode([
        'success' => true, 
        'message' => "SSL installation queued for $domain! (Task ID: $taskId). This may take up to 30 seconds."
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}