<?php
// /opt/panel/www/ajax/get_logs.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------

// STRICT POST CHECK
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
$domain = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL);

if (empty($username) || empty($domain)) {
    echo json_encode(['success' => false, 'error' => 'Username and Domain are required to view logs.']);
    exit;
}

$log_type = (isset($_POST['type']) && $_POST['type'] === 'access') ? 'access.log' : 'error.log';
$log_file = "/home/$username/web/$domain/logs/$log_type";

if (!file_exists($log_file)) {
    echo json_encode(['success' => false, 'error' => "Log file not found at $log_file"]);
    exit;
}

$safe_path = escapeshellarg($log_file);
$output = shell_exec("tail -n 50 $safe_path");

echo json_encode([
    'success' => true, 
    'logs' => htmlspecialchars($output)
]);