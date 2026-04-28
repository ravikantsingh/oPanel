<?php
// /opt/panel/www/ajax/delete_domain.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$domain = strtolower(trim(filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL)));
$username = preg_replace('/[^a-z0-9]/', '', $_POST['username'] ?? '');

if (empty($domain) || empty($username)) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $queue->dispatch('delete_domain', [
        'domain' => $domain,
        'username' => $username
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}