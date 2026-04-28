<?php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------

require_once '../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT id, username, minute, hour, day, month, weekday, command FROM cron_jobs ORDER BY username ASC, id DESC");
    echo json_encode(['success' => true, 'jobs' => $stmt->fetchAll()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}