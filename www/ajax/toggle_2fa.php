<?php
// /opt/panel/www/ajax/toggle_2fa.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/Database.php';
require_once '../classes/TOTP.php'; // Inject the TOTP class

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$action = $_POST['action'] ?? '';
$adminUser = 'admin'; // Hardcoded for the master panel admin

try {
    $db = Database::getInstance()->getConnection();

    if ($action === 'disable') {
        $stmt = $db->prepare("UPDATE panel_core.panel_admins SET is_2fa_enabled = 0 WHERE username = ?");
        $stmt->execute([$adminUser]);
        echo json_encode(['success' => true, 'state' => 'disabled']);
        exit;
    } 
    
    if ($action === 'enable') {
        // 1. Generate the secret using our centralized class
        $secret = TOTP::generateSecret();

        // 2. Save the new secret and enable 2FA
        $stmt = $db->prepare("UPDATE panel_core.panel_admins SET totp_secret = ?, is_2fa_enabled = 1 WHERE username = ?");
        $stmt->execute([$secret, $adminUser]);

        // 3. Generate the working QR URL using our centralized class
        $qrUrl = TOTP::getQRCodeUrl($adminUser, $secret, 'oPanel');

        echo json_encode([
            'success' => true, 
            'state' => 'enabled', 
            'qr_url' => $qrUrl, 
            'secret' => $secret
        ]);
        exit;
    }

    throw new Exception("Invalid action.");

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>