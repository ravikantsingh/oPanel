<?php
// /opt/panel/www/ajax/secure_panel.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$domain = strtolower(trim(filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL)));
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

// Strict domain validation
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
    $queue->dispatch('secure_panel', [
        'domain' => $domain,
        'email' => $email
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}