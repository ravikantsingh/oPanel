<?php
// /opt/panel/www/ajax/manage_schedule.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$type = $_POST['backup_type'] ?? '';
$target = ($type === 'web') ? $_POST['target_web'] : $_POST['target_db'];
$frequency = $_POST['frequency'] ?? '';
$run_hour = (int)($_POST['run_hour'] ?? 2);
$retention = (int)($_POST['retention_days'] ?? 3);

// In a real multi-user setup, you'd pull this from the session. 
// For now, we assume master admin.
$username = 'admin'; 

if (empty($target) || empty($frequency)) {
    echo json_encode(['success' => false, 'error' => 'Missing data.']); exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // UPSERT logic: If a schedule for this target already exists, update it. Otherwise insert.
    $stmt = $db->prepare("SELECT id FROM panel_core.backup_schedules WHERE target = ? AND backup_type = ?");
    $stmt->execute([$target, $type]);
    $existing = $stmt->fetch();

    if ($existing) {
        $update = $db->prepare("UPDATE panel_core.backup_schedules SET frequency=?, run_hour=?, retention_days=?, is_active=1 WHERE id=?");
        $update->execute([$frequency, $run_hour, $retention, $existing['id']]);
    } else {
        $insert = $db->prepare("INSERT INTO panel_core.backup_schedules (username, target, backup_type, frequency, run_hour, retention_days) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->execute([$username, $target, $type, $frequency, $run_hour, $retention]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>