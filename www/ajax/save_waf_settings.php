<?php
// /opt/panel/www/ajax/save_waf_settings.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php'; // <-- INJECT THE QUEUE

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$branch = $_POST['waf_branch'] ?? '';
$allowed_branches = ['v3.3/master', 'lts/v4.25.x'];

if (!in_array($branch, $allowed_branches)) {
    echo json_encode(['success' => false, 'error' => 'Invalid WAF branch selected.']);
    exit;
}

$config_file = '/opt/panel/www/config/waf_settings.json';
$data = ['waf_branch' => $branch];

// Save the JSON without escaping the slashes!
if (file_put_contents($config_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
    
    // Dispatch to the Python Background Worker natively
    try {
        $queue = new TaskQueue();
        $taskId = $queue->dispatch('update_waf', [
            'target_branch' => $branch
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Settings saved! WAF compilation task queued.']);
    } catch (Exception $e) {
        // If the queue fails, the file still saved, but we warn the user
        echo json_encode(['success' => false, 'error' => 'Settings saved, but failed to queue update task: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'error' => 'Failed to write config. Check file permissions.']);
}
?>