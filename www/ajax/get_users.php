<?php
// /opt/panel/www/ajax/get_users.php
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
    // We check if the SSH key or Webhook token exists to show cool badges in the UI
    $stmt = $db->query("SELECT username, email, 
                        IF(ssh_pub_key IS NOT NULL, 1, 0) as has_ssh, 
                        IF(webhook_token IS NOT NULL, 1, 0) as has_webhook 
                        FROM users ORDER BY username ASC");
    echo json_encode(['success' => true, 'users' => $stmt->fetchAll()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}