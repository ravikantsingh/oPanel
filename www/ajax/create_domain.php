<?php
// /opt/panel/www/ajax/create_domain.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------

require_once '../classes/TaskQueue.php';
// In a full build, we would also require '../classes/Database.php' here to verify the user actually exists in the DB first.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// 1. Sanitize Inputs
$domain = strtolower(trim(filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL)));
$username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
$php_version = trim(filter_input(INPUT_POST, 'php_version', FILTER_SANITIZE_STRING));

// 2. Validate Format
if (!preg_match('/^(?!\-)(?:(?:[a-zA-Z\d][a-zA-Z\d\-]{0,61})?[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/', $domain)) {
    echo json_encode(['success' => false, 'error' => 'Invalid domain name format.']);
    exit;
}

if (!preg_match('/^[a-z0-9]+$/', $username)) {
    echo json_encode(['success' => false, 'error' => 'Invalid username format.']);
    exit;
}

// 3. Dispatch to the Python/Bash Worker
try {
    $queue = new TaskQueue();
    
    // The payload mapping exactly to Phase 3.2 (vhost_manager.sh)
    $payload = [
        'sub_action'  => 'create',
        'domain'      => $domain,
        'username'    => $username,
        'php_version' => $php_version
    ];

    $taskId = $queue->dispatch('create_vhost', $payload);

    echo json_encode([
        'success' => true, 
        'message' => "Domain $domain queued for provisioning! (Task ID: $taskId)"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}