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
    $stmt = $db->query("SELECT port, protocol FROM firewall_rules ORDER BY port ASC");
    echo json_encode(['success' => true, 'rules' => $stmt->fetchAll()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}