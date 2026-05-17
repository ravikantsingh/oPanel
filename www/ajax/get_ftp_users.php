<?php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$domain = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL);

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT ftp_user FROM ftp_accounts WHERE domain_name = ?");
    $stmt->execute([$domain]);
    
    echo json_encode(['success' => true, 'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}