<?php
// /opt/panel/www/ajax/manage_php.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------

require_once '../classes/TaskQueue.php';
require_once '../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// We pull the entire $_POST array, sanitize it, and dispatch it to Python
$payload = ['sub_action' => 'update_php'];
$dbData = []; // Array to build our MySQL update

foreach ($_POST as $key => $value) {
    // Basic sanitization
    $cleanValue = trim(strip_tags($value));
    $payload[$key] = $cleanValue;
    
    // Build DB update array (skip domain, user, php_version)
    if (!in_array($key, ['domain', 'username', 'php_version'])) {
        $dbData[$key] = $cleanValue;
    }
}

try {
    // 1. Dispatch to Python to rewrite the FPM file
    $queue = new TaskQueue();
    $queue->dispatch('manage_php', $payload);

    // 2. Update the "Source of Truth" UI Database instantly
    $db = Database::getInstance()->getConnection();
    
    // Dynamically build the UPDATE query based on POST keys
    $setClause = [];
    $params = [];
    foreach ($dbData as $col => $val) {
        $setClause[] = "`$col` = ?";
        $params[] = $val;
    }
    $params[] = $payload['domain']; // WHERE domain_name = ?
    
    $sql = "UPDATE domains SET " . implode(', ', $setClause) . " WHERE domain_name = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'message' => "PHP Settings queued and UI updated."]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}