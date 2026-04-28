<?php
// /opt/panel/www/ajax/get_dns.php
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
    $stmt = $db->query("SELECT domain_name, record_name, record_type, record_value FROM dns_records ORDER BY domain_name ASC, record_type ASC");
    echo json_encode(['success' => true, 'records' => $stmt->fetchAll()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}