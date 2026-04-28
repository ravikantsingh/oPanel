<?php
// /opt/panel/www/ajax/manage_fm.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$domain    = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL);
$username  = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$php_ver   = filter_input(INPUT_POST, 'php_version', FILTER_SANITIZE_STRING);
$password  = $_POST['fm_password'] ?? '';

if (empty($domain) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Domain and Password are required.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $queue->dispatch('manage_fm', [
        'domain'       => $domain,
        'username'     => $username,
        'php_version'  => $php_ver,
        'fm_password'  => $password
    ]);
    echo json_encode(['success' => true, 'message' => "File Manager deployment queued."]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}