<?php
require_once 'security.php';
header('Content-Type: application/json');

// Stackrium Configuration
define('CENTRAL_API_URL', 'https://stackrium.com/api/support_receive.php');
$license_key = trim(file_get_contents('/opt/panel/license.key'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method.']);
    exit;
}

$subject = trim(strip_tags($_POST['subject'] ?? ''));
$message = trim(htmlspecialchars($_POST['message'] ?? ''));
$priority = in_array($_POST['priority'] ?? '', ['Low', 'Normal', 'High', 'Critical']) ? $_POST['priority'] : 'Normal';

if (empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Subject and message are required.']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    // 1. Save Ticket Locally (Without Central ID yet)
    $stmt = $db->prepare("INSERT INTO support_tickets (subject, priority, status) VALUES (?, ?, 'Open')");
    $stmt->execute([$subject, $priority]);
    $local_ticket_id = $db->lastInsertId();

    // 2. Save Initial Message Locally
    $stmt = $db->prepare("INSERT INTO support_replies (ticket_id, sender_type, message_body) VALUES (?, 'Client', ?)");
    $stmt->execute([$local_ticket_id, $message]);
    $local_reply_id = $db->lastInsertId();

    // 3. Handle File Upload (Local Save)
    $local_file_path = null;
    $mime_type = null;
    $original_name = null;

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['attachment']['tmp_name'];
        $original_name = basename($_FILES['attachment']['name']);
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($tmp_name);
        
        $allowed_mimes = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/webp' => '.webp', 'application/pdf' => '.pdf'];
        
        if (!array_key_exists($mime_type, $allowed_mimes)) {
            throw new Exception("Invalid file format. Please upload JPG, PNG, WEBP, or PDF.");
        }

        $extension = $allowed_mimes[$mime_type];
        $secure_filename = bin2hex(random_bytes(16)) . '_' . time() . $extension;
        $local_file_path = '/opt/panel/www/uploads/tickets/' . $secure_filename;

        if (!move_uploaded_file($tmp_name, $local_file_path)) {
            throw new Exception("Failed to save screenshot locally.");
        }

        // Save local attachment record
        $stmt = $db->prepare("INSERT INTO support_attachments (ticket_id, reply_id, file_name, file_path, mime_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$local_ticket_id, $local_reply_id, $original_name, '/uploads/tickets/' . $secure_filename, $mime_type]);
    }

    $db->commit();

    // ==========================================
    // 4. TRANSMIT TO STACKRIUM CENTRAL
    // ==========================================
    
    // Close the session lock early so the UI doesn't hang while cURL is transmitting
    session_write_close(); 

    $post_data = [
        'license_key' => $license_key,
        'subject' => $subject,
        'message' => $message,
        'priority' => $priority
    ];

    // If there is a file, append it natively to the cURL request as a Multipart Form
    if ($local_file_path && file_exists($local_file_path)) {
        $post_data['attachment'] = new CURLFile($local_file_path, $mime_type, $original_name);
    }

    $ch = curl_init(CENTRAL_API_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Don't hang the server if Central is offline

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // ==========================================
    // 5. PROCESS CENTRAL RESPONSE & LINK IDs
    // ==========================================
    $central_data = json_decode($response, true);

    if ($http_code === 200 && isset($central_data['success']) && $central_data['success']) {
        // Link the local ticket to the central database ticket ID
        $central_ticket_id = $central_data['central_ticket_id'];
        
        $updateStmt = $db->prepare("UPDATE support_tickets SET central_id = ? WHERE id = ?");
        $updateStmt->execute([$central_ticket_id, $local_ticket_id]);

        echo json_encode([
            'success' => true, 
            'message' => 'Ticket submitted securely to Stackrium Central.'
        ]);
    } else {
        // If central fails, we don't delete the local ticket, but we warn the user
        $error_msg = $central_data['error'] ?? 'Central server unreachable.';
        echo json_encode([
            'success' => true, 
            'warning' => 'Ticket saved locally, but failed to reach Central: ' . $error_msg
        ]);
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>