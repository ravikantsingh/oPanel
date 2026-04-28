<?php
// /opt/panel/www/ajax/create_dns.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------

require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

$domain = strtolower(trim(filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL)));

// Fetch the actual public IP of your AWS server automatically
$server_ip = file_get_contents('http://checkip.amazonaws.com');
$server_ip = trim($server_ip);

if (empty($domain)) {
    echo json_encode(['success' => false, 'error' => 'Domain is required.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $taskId = $queue->dispatch('create_dns', [
        'domain'    => $domain,
        'server_ip' => $server_ip
    ]);

    echo json_encode(['success' => true, 'message' => "DNS Zone creation queued for $domain on IP $server_ip!"]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}