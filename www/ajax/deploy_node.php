<?php
// /opt/panel/www/ajax/deploy_node.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$domain = strtolower(trim(filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL)));
$username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
$app_root = trim(filter_input(INPUT_POST, 'app_root', FILTER_SANITIZE_STRING));
$startup_file = trim(filter_input(INPUT_POST, 'startup_file', FILTER_SANITIZE_STRING));
$app_port = filter_input(INPUT_POST, 'app_port', FILTER_VALIDATE_INT);
$app_mode = trim(filter_input(INPUT_POST, 'app_mode', FILTER_SANITIZE_STRING));
$env_vars = trim($_POST['env_vars'] ?? '');

// Validation
if (!$domain || !$username || !$app_root || !$startup_file || !$app_port) {
    echo json_encode(['success' => false, 'error' => 'All required fields must be filled.']);
    exit;
}

if ($app_port < 1024 || $app_port > 65535) {
    echo json_encode(['success' => false, 'error' => 'Port must be between 1024 and 65535.']);
    exit;
}

try {
    $queue = new TaskQueue();
    
    $payload = [
        'domain' => $domain,
        'username' => $username,
        'app_root' => $app_root,
        'startup_file' => $startup_file,
        'app_port' => $app_port,
        'app_mode' => $app_mode,
        'env_vars' => base64_encode($env_vars) // Securely pack for bash
    ];

    $taskId = $queue->dispatch('deploy_node', $payload);

    echo json_encode([
        'success' => true, 
        'message' => "Node.js deployment queued for $domain!"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>