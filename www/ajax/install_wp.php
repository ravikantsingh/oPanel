<?php
// /opt/panel/www/ajax/install_wp.php
header('Content-Type: application/json');

// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------

require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// 1. Sanitize Inputs
$domain = strtolower(trim(filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL)));
$username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
$wp_title = trim(filter_input(INPUT_POST, 'wp_title', FILTER_SANITIZE_STRING));
$wp_admin = trim(filter_input(INPUT_POST, 'wp_admin', FILTER_SANITIZE_STRING));
$wp_email = trim(filter_input(INPUT_POST, 'wp_email', FILTER_SANITIZE_EMAIL));

// Passwords bypass standard sanitization to avoid stripping valid special characters
$wp_pass = $_POST['wp_pass'] ?? '';

// Capture the Redis Toggle
$enable_redis = filter_input(INPUT_POST, 'enable_redis', FILTER_VALIDATE_BOOLEAN) ? true : false;

// 2. Validate Required Fields
if (!$domain || !$username || !$wp_title || !$wp_admin || !$wp_pass || !$wp_email) {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit;
}

// 3. Dispatch to the Python/Bash Worker
try {
    $queue = new TaskQueue();
    
    $payload = [
        'domain'   => $domain,
        'username' => $username,
        'wp_title' => $wp_title,
        'wp_admin' => $wp_admin,
        'wp_pass'  => $wp_pass,
        'wp_email' => $wp_email,
        'enable_redis' => $enable_redis
    ];

    $taskId = $queue->dispatch('install_wp', $payload);

    echo json_encode([
        'success' => true, 
        'message' => "WordPress installation queued for $domain! (Task ID: $taskId)"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>