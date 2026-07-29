<?php
// /opt/panel/www/ajax/update_f2b_settings.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// Rebuild the strings from the split UI inputs
$bantime = (int)($_POST['bantime_val'] ?? 1) . preg_replace('/[^mhd]/', '', $_POST['bantime_unit'] ?? 'h');
$findtime = (int)($_POST['findtime_val'] ?? 10) . preg_replace('/[^mhd]/', '', $_POST['findtime_unit'] ?? 'm');
$maxretry = (int)($_POST['maxretry'] ?? 5);

// Validation
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

    $taskId = $queue->dispatch('manage_fail2ban', $payload);

    echo json_encode([
        'success' => true, 
        'message' => "Fail2ban settings update queued! (Task ID: $taskId)"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}
?>