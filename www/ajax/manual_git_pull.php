<?php
// /opt/panel/www/ajax/manual_git_pull.php
header('Content-Type: application/json');

// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------

require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// Safely sanitize inputs
$domain = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL);
$username = preg_replace('/[^a-z0-9]/', '', $_POST['username'] ?? '');
// Allow alphanumeric, dashes, dots, underscores, and slashes for branch names (e.g., feature/update-1)
$branch = preg_replace('/[^a-zA-Z0-9\-\.\_\/]/', '', $_POST['branch'] ?? 'main');

if (empty($domain) || empty($username) || empty($branch)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit;
}

try {
    $queue = new TaskQueue();
    
    // Dispatch the task to the Python Daemon
    $taskId = $queue->dispatch('git_pull', [
        'domain' => $domain,
        'username' => $username,
        'branch' => $branch
    ]);

    echo json_encode([
        'success' => true, 
        'message' => "Manual pull initiated for branch '$branch'."
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}