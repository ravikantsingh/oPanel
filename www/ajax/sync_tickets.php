<?php
require_once 'security.php';
header('Content-Type: application/json');

define('CENTRAL_SYNC_URL', 'https://stackrium.com/api/support_sync.php');
$license_key = trim(file_get_contents('/opt/panel/license.key'));

try {
    $db = Database::getInstance()->getConnection();
    
    // ==========================================
    // 1. BACKGROUND SYNC WITH CENTRAL SERVER
    // ==========================================
    
    // Unlock session early so UI doesn't hang
    session_write_close(); 

    $ch = curl_init(CENTRAL_SYNC_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['license_key' => $license_key]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Fast timeout so we don't stall the UI if Central is slow
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 && $response) {
        $central_data = json_decode($response, true);
        
        if (isset($central_data['success']) && $central_data['success']) {
            $db->beginTransaction();
            
            // Sync Tickets (Update statuses like 'Answered' or 'Closed')
            $updateTicketStmt = $db->prepare("UPDATE support_tickets SET status = ?, updated_at = ? WHERE central_id = ?");
            foreach ($central_data['tickets'] as $ct) {
                $updateTicketStmt->execute([$ct['status'], $ct['updated_at'], $ct['id']]);
            }

            // Sync Admin Replies (Insert new replies from Stackrium Central)
            $checkReplyStmt = $db->prepare("SELECT id FROM support_replies WHERE central_reply_id = ?");
            $insertReplyStmt = $db->prepare("INSERT INTO support_replies (central_reply_id, ticket_id, sender_type, message_body, created_at) SELECT ?, id, ?, ?, ? FROM support_tickets WHERE central_id = ?");
            
            foreach ($central_data['replies'] as $cr) {
                if ($cr['sender_type'] === 'Admin') {
                    $checkReplyStmt->execute([$cr['central_reply_id']]);
                    if (!$checkReplyStmt->fetch()) {
                        // We don't have this admin reply locally yet, insert it!
                        $insertReplyStmt->execute([
                            $cr['central_reply_id'], 
                            $cr['sender_type'], 
                            $cr['message_body'], 
                            $cr['created_at'], 
                            $cr['central_ticket_id']
                        ]);
                    }
                }
            }
            $db->commit();
        }
    }

    // ==========================================
    // 2. FETCH AND RETURN LOCAL DATA TO UI
    // ==========================================
    
    // Now that we've updated the local DB (if Central was reachable), we serve the local data.
    $stmt = $db->query("SELECT * FROM support_tickets ORDER BY updated_at DESC");
    $local_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $local_replies = [];
    if (count($local_tickets) > 0) {
        $stmt = $db->query("SELECT r.*, a.file_path, a.file_name FROM support_replies r LEFT JOIN support_attachments a ON r.id = a.reply_id ORDER BY r.created_at ASC");
        $local_replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'tickets' => $local_tickets,
        'replies' => $local_replies
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>