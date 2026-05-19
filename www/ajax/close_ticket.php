<?php
// /opt/panel/www/ajax/close_ticket.php
require_once 'security.php';
require_once '../classes/Database.php';
header('Content-Type: application/json');

$local_id = (int)($_POST['ticket_id'] ?? 0);

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Get the central ID before we close it
    $stmt = $db->prepare("SELECT central_id FROM support_tickets WHERE id = ?");
    $stmt->execute([$local_id]);
    $ticket = $stmt->fetch();

    // 2. Close it locally instantly
    $db->prepare("UPDATE support_tickets SET status = 'Closed', updated_at = NOW() WHERE id = ?")->execute([$local_id]);

    // 3. Unlock session and tell Stackrium Central
    session_write_close();
    
    if ($ticket && $ticket['central_id']) {
        $license_key = trim(file_get_contents('/opt/panel/license.key'));
        $ch = curl_init('https://stackrium.com/api/client_close_ticket.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['license_key' => $license_key, 'central_ticket_id' => $ticket['central_id']]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_exec($ch);
        curl_close($ch);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>