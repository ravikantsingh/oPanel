<?php
// /opt/panel/www/ajax/check_first_login.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if the setup flag exists and is set to '1'
    $stmt = $db->query("SELECT setting_value FROM panel_core.settings WHERE setting_key = 'setup_completed' LIMIT 1");
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the setting doesn't exist or is not '1', the modal triggers
    $is_first_login = (!$setting || $setting['setting_value'] !== '1');

    echo json_encode(['success' => true, 'is_first_login' => $is_first_login]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}