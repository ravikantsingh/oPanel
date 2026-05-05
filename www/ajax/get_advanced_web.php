<?php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$domain = trim($_POST['domain'] ?? '');
if (!$domain) { echo json_encode(['success' => false, 'error' => 'Domain missing.']); exit; }

try {
    $db = Database::getInstance()->getConnection();
    
    // Fetch Redirects
    $stmt = $db->prepare("SELECT id, source_path, target_url, redirect_type FROM domain_redirects WHERE domain_name = ?");
    $stmt->execute([$domain]);
    $redirects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch MIME Types
    $stmt = $db->prepare("SELECT id, extension, mime_type FROM domain_mimes WHERE domain_name = ?");
    $stmt->execute([$domain]);
    $mimes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'redirects' => $redirects, 'mimes' => $mimes]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>