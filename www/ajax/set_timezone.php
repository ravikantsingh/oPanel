<?php
// /opt/panel/www/ajax/set_timezone.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$timezone = $_POST['timezone'] ?? '';

// Strict Validation: Ensure it is a real, recognized global timezone
if (!in_array($timezone, timezone_identifiers_list())) {
    echo json_encode(['success' => false, 'error' => 'Invalid timezone selected.']);
    exit;
}

try {
    $queue = new TaskQueue();
    // Dispatch the task to the Python worker
    $taskId = $queue->dispatch('set_timezone', ['timezone' => $timezone]);

    echo json_encode([
        'success' => true, 
        'message' => "Timezone update to $timezone queued!"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Task Queue error: ' . $e->getMessage()]);
}
?>