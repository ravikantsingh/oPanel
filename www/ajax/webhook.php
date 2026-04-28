<?php
// /opt/panel/www/ajax/webhook.php
header('Content-Type: application/json');
require_once '../classes/Database.php';
require_once '../classes/TaskQueue.php';

// GitHub sends webhooks as POST requests. 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Extract domain and secure token from the webhook URL
// Example: https://panel.com/ajax/webhook.php?domain=testsite.com&token=YOUR_TOKEN
$domain = filter_input(INPUT_GET, 'domain', FILTER_SANITIZE_URL);
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

if (!$domain || !$token) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing domain or authorization token.']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Authenticate the token
    $stmt = $db->prepare("SELECT username FROM users WHERE webhook_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid webhook token. Deployment denied.']);
        exit;
    }

    // Authentication passed! Queue the auto-deploy task.
    $queue = new TaskQueue();
    $queue->dispatch('git_pull', [
        'username' => $user['username'],
        'domain'   => $domain
    ]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => "Deployment queued for {$domain}"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error.']);
}