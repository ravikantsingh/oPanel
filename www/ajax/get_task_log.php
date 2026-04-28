<?php
// /opt/panel/www/ajax/get_task_log.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------
require_once '../classes/Database.php';

// ---> THE FIX: Change $_GET to $_POST here <---
if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'Task ID required.']);
    exit;
}

$taskId = $_POST['id'] ?? '';

try {
    $db = Database::getInstance()->getConnection();
    
    // ---> Update the table name here to match your database! <---
    $stmt = $db->prepare("SELECT action, status, output_log FROM tasks_queue WHERE id = ?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    if (!$task) {
        throw new Exception("Task not found.");
    }

    // Map the variable to your 'output_log' column
    $logOutput = $task['output_log'] ?: "No output logged for this task.";

    echo json_encode([
        'success' => true, 
        'action' => $task['action'],
        'status' => $task['status'],
        'output' => $logOutput
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}