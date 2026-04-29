<?php
// /opt/panel/www/ajax/get_schedules.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    // In a multi-tenant setup, you'd add: WHERE username = $_SESSION['username']
    $stmt = $db->query("SELECT id, target, backup_type, frequency, run_hour, retention_days, last_run FROM panel_core.backup_schedules WHERE is_active = 1 ORDER BY target ASC");
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'schedules' => $schedules]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>