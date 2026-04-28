<?php
// /opt/panel/www/ajax/rotate_fm_password.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$domain = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $_POST['domain'] ?? '');
$username = preg_replace('/[^a-z0-9]/', '', $_POST['username'] ?? '');
$new_pass = $_POST['new_fm_password'] ?? '';

if (empty($domain) || empty($username) || empty($new_pass)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $queue->dispatch('rotate_fm', [
        'domain' => $domain,
        'username' => $username,
        'new_password' => $new_pass
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}