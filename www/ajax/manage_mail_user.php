<?php
header('Content-Type: application/json');
require_once 'security.php'; // The Gatekeeper
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
$domain = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL);

if (!$domain || !$action) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters provided.']);
    exit;
}

try {
    $queue = new TaskQueue();

    if ($action === 'add') {
        $prefix = preg_replace('/[^a-zA-Z0-9.-_]/', '', $_POST['prefix']);
        $email = $prefix . '@' . $domain;
        $password = $_POST['password'];

        if(empty($prefix) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Prefix and Password are required.']);
            exit;
        }

        $queue->dispatch('manage_mail_user', [
            'action'   => 'add',
            'email'    => $email,
            'password' => $password
        ]);

        echo json_encode(['success' => true, 'message' => "Mailbox $email provisioning queued!"]);
    } 
    elseif ($action === 'delete') {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        
        $queue->dispatch('manage_mail_user', [
            'action' => 'delete',
            'email'  => $email
        ]);

        echo json_encode(['success' => true, 'message' => "Deletion queued. Physical files will be wiped."]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}
?>