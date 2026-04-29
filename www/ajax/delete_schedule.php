<?php
// /opt/panel/www/ajax/delete_schedule.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
$id = $_POST['id'] ?? '';

if (empty($id)) {
    echo json_encode(['success' => false, 'error' => 'Invalid schedule ID.']); exit;
}

try {
    $db = Database::getInstance()->getConnection();
    // We just delete it from the Source of Truth. The Python script will never see it again.
    $stmt = $db->prepare("DELETE FROM panel_core.backup_schedules WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>