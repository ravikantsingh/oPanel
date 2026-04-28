<?php
// /opt/panel/www/ajax/get_ssh_key.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------
require_once '../classes/Database.php';
require_once '../classes/TaskQueue.php';

// STRICT POST CHECK
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');

if (empty($username)) {
    echo json_encode(['success' => false, 'error' => 'Username is required to fetch SSH keys.']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT ssh_pub_key FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!empty($user['ssh_pub_key'])) {
        echo json_encode(['success' => true, 'key' => $user['ssh_pub_key']]);
        exit;
    }

    $queue = new TaskQueue();
    $queue->dispatch('generate_ssh_key', ['username' => $username]);
    
    echo json_encode(['success' => false, 'message' => 'Generating secure key... Please wait 5 seconds and try again.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}