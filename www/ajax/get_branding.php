<?php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    // Fetch all keys that start with brand_
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'brand_%'");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo json_encode(['success' => true, 'data' => $settings]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>