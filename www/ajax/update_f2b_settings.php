<?php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$bantime = trim(strip_tags($_POST['bantime'] ?? ''));
$findtime = trim(strip_tags($_POST['findtime'] ?? ''));
$maxretry = (int)($_POST['maxretry'] ?? 0);

// Basic validation (e.g. "1h", "10m")
if (!preg_match('/^\d+[mhd]$/', $bantime) || !preg_match('/^\d+[mhd]$/', $findtime) || $maxretry < 1) {
    echo json_encode(['success' => false, 'error' => 'Invalid configuration parameters provided.']);
    exit;
}

try {
    $queue = new TaskQueue();
    
    $payload = [
        'sub_action' => 'update_settings',
        'bantime'    => $bantime,
        'findtime'   => $findtime,
        'maxretry'   => $maxretry
    ];

    // Reuse the existing manage_fail2ban action!
    $taskId = $queue->dispatch('manage_fail2ban', $payload);

    echo json_encode([
        'success' => true, 
        'message' => "Fail2ban settings update queued! (Task ID: $taskId)"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}
?>