<?php
// /opt/panel/www/ajax/toggle_2fa.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/Database.php';

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
        // Generate a mathematically secure 16-character Base32 Secret
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }

        // Save the new secret and enable 2FA
        $stmt = $db->prepare("UPDATE panel_core.panel_admins SET totp_secret = ?, is_2fa_enabled = 1 WHERE username = ?");
        $stmt->execute([$secret, $adminUser]);

        // Generate the provisioning URL for Google Authenticator
        $panelName = urlencode("oPanel");
        $otpAuthUrl = "otpauth://totp/{$panelName}:{$adminUser}?secret={$secret}&issuer={$panelName}";
        
        // Use Google's Chart API to render the QR Code image securely and instantly
        $qrUrl = "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=" . urlencode($otpAuthUrl);

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