<?php
// /opt/panel/www/ajax/get_databases.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------

require_once '../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// ... the rest of your code ...

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT db_name, db_user, owner_username FROM `databases` ORDER BY db_name ASC");
    echo json_encode(['success' => true, 'databases' => $stmt->fetchAll()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}