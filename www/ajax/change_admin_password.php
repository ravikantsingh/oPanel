<?php
// /opt/panel/www/ajax/change_admin_password.php
header('Content-Type: application/json');
require_once 'security.php'; // handles CSRF and Session checks
require_once '../classes/Database.php';

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';

if (empty($current_password) || empty($new_password)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']); exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Hardcoded to the primary admin account for now
    $stmt = $db->prepare("SELECT password_hash FROM panel_admins WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($current_password, $admin['password_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Current password is incorrect.']); exit;
    }

    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $update = $db->prepare("UPDATE panel_admins SET password_hash = ? WHERE username = 'admin'");
    $update->execute([$new_hash]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>