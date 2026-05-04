<?php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$domain = trim(filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL));
$action = trim(filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING)); // 'suspend' or 'unsuspend'

if (!$domain || !in_array($action, ['suspend', 'unsuspend'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid domain or action.']);
    exit;
}

try {
    // 1. Update the Source of Truth (Database)
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $newStatus = ($action === 'suspend') ? 'suspended' : 'active';
    $stmt = $pdo->prepare("UPDATE domains SET status = ? WHERE domain_name = ?");
    $stmt->execute([$newStatus, $domain]);

    // 2. Dispatch to the Python/Bash Engine
    $queue = new TaskQueue();
    $payload = ['domain' => $domain, 'action' => $action];
    
    // We will call this action 'domain_status' in the Python daemon
    $taskId = $queue->dispatch('domain_status', $payload);

    echo json_encode([
        'success' => true, 
        'message' => "Domain $action task queued! (Task ID: $taskId)"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>