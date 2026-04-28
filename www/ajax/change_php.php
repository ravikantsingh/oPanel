<?php
// /opt/panel/www/ajax/change_php.php
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
$php_version = trim(filter_input(INPUT_POST, 'php_version', FILTER_SANITIZE_STRING));

// Security Validation
$allowed_versions = ['8.1', '8.2', '8.3'];
if (!in_array($php_version, $allowed_versions)) {
    echo json_encode(['success' => false, 'error' => 'Invalid PHP version selected.']);
    exit;
}

try {
    $queue = new TaskQueue();
    
    // We send this to the 'create_vhost' action queue, but with the 'update_php' sub_action!
    $payload = [
        'sub_action'  => 'update_php',
        'domain'      => $domain,
        'php_version' => $php_version
    ];

    $taskId = $queue->dispatch('create_vhost', $payload);

    echo json_encode([
        'success' => true, 
        'message' => "$domain queued for PHP $php_version update! (Task ID: $taskId)"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}