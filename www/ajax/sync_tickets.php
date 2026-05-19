<?php
require_once 'security.php';
require_once '../classes/Database.php';
header('Content-Type: application/json');

define('CENTRAL_SYNC_URL', 'https://stackrium.com/api/support_sync.php');
$license_key = trim(file_get_contents('/opt/panel/license.key'));

try {
    $db = Database::getInstance()->getConnection();
    session_write_close(); 

    $ch = curl_init(CENTRAL_SYNC_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['license_key' => $license_key]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 && $response) {
        $central_data = json_decode($response, true);
        
        if (isset($central_data['success']) && $central_data['success']) {
            $db->beginTransaction();
            
            $updateTicketStmt = $db->prepare("UPDATE support_tickets SET status = ?, updated_at = ?, ticket_number = ? WHERE central_id = ?");
            foreach ($central_data['tickets'] as $ct) {
                $updateTicketStmt->execute([$ct['status'], $ct['updated_at'], $ct['ticket_number'], $ct['id']]);
            }

            $checkReplyStmt = $db->prepare("SELECT id FROM support_replies WHERE central_reply_id = ?");
            $insertReplyStmt = $db->prepare("INSERT INTO support_replies (central_reply_id, ticket_id, sender_type, message_body, created_at) SELECT ?, id, ?, ?, ? FROM support_tickets WHERE central_id = ?");
            $markUnreadStmt = $db->prepare("UPDATE support_tickets SET is_unread = 1 WHERE central_id = ?");
            
            $new_admin_reply_count = 0;

            foreach ($central_data['replies'] as $cr) {
                if ($cr['sender_type'] === 'Admin') {
                    $checkReplyStmt->execute([$cr['central_reply_id']]);
                    if (!$checkReplyStmt->fetch()) {
                        $insertReplyStmt->execute([$cr['central_reply_id'], $cr['sender_type'], $cr['message_body'], $cr['created_at'], $cr['central_ticket_id']]);
                        $markUnreadStmt->execute([$cr['central_ticket_id']]);
                        $new_admin_reply_count++;
                    }
                }
            }
            $db->commit();

            // ==========================================
            // OPTION 3: THE EMAIL ALERT BRIDGE
            // ==========================================
            if ($new_admin_reply_count > 0) {
                $profile_file = '/opt/panel/www/config/profile.json';
                if (file_exists($profile_file)) {
                    $profile = json_decode(file_get_contents($profile_file), true);
                    if (!empty($profile['email'])) {
                        $subject = "Stackrium Support: New Reply";
                        $body = "Hello,\n\nStackrium Support has responded to your ticket.\n\nPlease log into your server control panel at https://" . $_SERVER['HTTP_HOST'] . ":7443 to view the response and continue the conversation.\n\n--\nStackrium Support";
                        $headers = "From: no-reply@" . parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST);
                        @mail($profile['email'], $subject, $body, $headers); // Local Postfix routing
                    }
                }
            }
        }
    }

    $stmt = $db->query("SELECT * FROM support_tickets ORDER BY updated_at DESC");
    $local_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $local_replies = [];
    if (count($local_tickets) > 0) {
        $stmt = $db->query("SELECT r.*, a.file_path, a.file_name FROM support_replies r LEFT JOIN support_attachments a ON r.id = a.reply_id ORDER BY r.created_at ASC");
        $local_replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['success' => true, 'tickets' => $local_tickets, 'replies' => $local_replies]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>