<?php
// /opt/panel/www/ajax/clone_repo.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------

require_once '../classes/TaskQueue.php';

// STRICT POST CHECK
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$domain = strtolower(trim(filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL)));
$repo_url = trim(filter_input(INPUT_POST, 'repo_url', FILTER_SANITIZE_URL));
$username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');

if (empty($domain) || empty($repo_url) || empty($username)) {
    echo json_encode(['success' => false, 'error' => 'Domain, Username, and Repository URL are required.']);
    exit;
}

if (!preg_match('/^https?:\/\//', $repo_url) && !str_contains($repo_url, '.git')) {
    echo json_encode(['success' => false, 'error' => 'Please provide a valid HTTP/HTTPS Git repository URL.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $taskId = $queue->dispatch('git_clone', [
        'username' => $username,
        'domain'   => $domain,
        'repo_url' => $repo_url
    ]);

    echo json_encode(['success' => true, 'message' => "Git clone task queued for $domain!"]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}