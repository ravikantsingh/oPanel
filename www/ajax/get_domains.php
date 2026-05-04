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
    // Fetch domains and join with the users table to get the webhook token
    $stmt = $db->query("SELECT domains.*, users.webhook_token FROM domains LEFT JOIN users ON domains.username = users.username ORDER BY domains.domain_name ASC");
    echo json_encode(['success' => true, 'domains' => $stmt->fetchAll()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}