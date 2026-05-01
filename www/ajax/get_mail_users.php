<?php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

$domain = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT email, quota FROM mail_users WHERE domain = ? ORDER BY email ASC");
    $stmt->execute([$domain]);
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'emails' => $emails]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB Fetch Error']);
}
?>