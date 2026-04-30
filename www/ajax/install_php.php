<?php
// /opt/panel/www/ajax/install_php.php
header('Content-Type: application/json');

// ---> REQUIRE THE GATEKEEPER <---
require_once 'security.php';
require_once '../classes/TaskQueue.php';
// --------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$sub_action = trim(strip_tags($_POST['sub_action'] ?? ''));
$version = trim(strip_tags($_POST['version'] ?? ''));

// Strict validation
if (!in_array($sub_action, ['install', 'remove']) || empty($version)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters provided.']);
    exit;
}

try {
    $queue = new TaskQueue();
    
    // Dispatch to Python worker using the 'install_php' action we whitelisted earlier!
    $queue->dispatch('install_php', [
        'sub_action' => $sub_action,
        'version' => $version
    ]);

    echo json_encode(['success' => true, 'message' => "Task queued successfully."]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>