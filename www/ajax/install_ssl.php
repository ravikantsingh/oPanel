<?php
// /opt/panel/www/ajax/install_ssl.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$domain = strtolower(trim(filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL)));
// The form should pass 'action_type' (e.g., 'letsencrypt' or 'custom')
$action_type = $_POST['action_type'] ?? 'letsencrypt'; 

if (!preg_match('/^(?!\\-)(?:(?:[a-zA-Z\\d][a-zA-Z\\d\\-]{0,61})?[a-zA-Z\\d]\\.){1,126}(?!\\d+)[a-zA-Z\\d]{1,63}$/', $domain)) {
    echo json_encode(['success' => false, 'error' => 'Invalid domain name format.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $payload = ['domain' => $domain];

    if ($action_type === 'letsencrypt') {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email address for Let\'s Encrypt.']);
            exit;
        }
        $payload['sub_action'] = 'letsencrypt';
        $payload['email'] = $email;
        
    } elseif ($action_type === 'custom') {
        $cert = trim($_POST['custom_cert'] ?? '');
        $key = trim($_POST['custom_key'] ?? '');
        
        // Basic validation to ensure they actually pasted a certificate
        if (strpos($cert, 'BEGIN CERTIFICATE') === false || strpos($key, 'BEGIN PRIVATE KEY') === false && strpos($key, 'BEGIN RSA PRIVATE KEY') === false) {
            echo json_encode(['success' => false, 'error' => 'Invalid certificate or private key format.']);
            exit;
        }
        
        $payload['sub_action'] = 'custom';
        $payload['custom_cert'] = $cert;
        $payload['custom_key'] = $key;
    } else {
        echo json_encode(['success' => false, 'error' => 'Unknown SSL installation type.']);
        exit;
    }

    $taskId = $queue->dispatch('install_ssl', $payload);

    echo json_encode([
        'success' => true, 
        'message' => 'SSL provisioning task queued successfully!',
        'task_id' => $taskId
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>