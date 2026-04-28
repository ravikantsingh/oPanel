<?php
// /opt/panel/www/ajax/create_user.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------

require_once '../classes/TaskQueue.php';

// 1. Basic security: Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// 2. Grab and sanitize the inputs
$username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
$password = $_POST['password']; // Do not sanitize passwords, they might contain special characters!

// 3. Validate input format (matching our Bash script rules)
if (!preg_match('/^[a-z0-9]+$/', $username)) {
    echo json_encode(['success' => false, 'error' => 'Username must be lowercase letters and numbers only.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters.']);
    exit;
}

// 4. Dispatch the task to the queue
try {
    $queue = new TaskQueue();
    
    // The payload matches exactly what our Python/Bash scripts expect
    $payload = [
        'sub_action' => 'create',
        'username'   => $username,
        'password'   => $password
    ];

    $taskId = $queue->dispatch('create_user', $payload);

    // 5. Return success to jQuery
    echo json_encode([
        'success' => true, 
        'message' => 'User creation task queued successfully! (Task ID: ' . $taskId . ')'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}