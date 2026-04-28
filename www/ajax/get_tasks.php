<?php
// /opt/panel/www/ajax/get_tasks.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------
require_once '../classes/Database.php';

// STRICT POST CHECK
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Fetch the 10 most recent tasks
    $stmt = $db->query("SELECT id, action, payload, status, created_at FROM tasks_queue ORDER BY id DESC LIMIT 10");
    $tasks = $stmt->fetchAll();

    echo json_encode(['success' => true, 'tasks' => $tasks]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}