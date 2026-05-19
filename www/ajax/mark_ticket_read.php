<?php
require_once 'security.php';
require_once '../classes/Database.php';
$id = (int)($_POST['ticket_id'] ?? 0);
Database::getInstance()->getConnection()->prepare("UPDATE support_tickets SET is_unread = 0 WHERE id = ?")->execute([$id]);
echo json_encode(['success' => true]);
?>