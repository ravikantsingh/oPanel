<?php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';
$domain = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL);
$sys_user = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$ftp_user = filter_input(INPUT_POST, 'ftp_user', FILTER_SANITIZE_STRING);
$ftp_pass = $_POST['ftp_pass'] ?? '';

try {
    $queue = new TaskQueue();
    $queue->dispatch('manage_ftp', [
        'sub_action' => $action,
        'domain'     => $domain,
        'username'   => $sys_user,
        'ftp_user'   => $ftp_user,
        'ftp_pass'   => $ftp_pass
    ]);
    echo json_encode(['success' => true, 'message' => "FTP Task Queued."]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}