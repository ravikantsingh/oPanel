<?php
// /opt/panel/www/ajax/manage_waf.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$domain = strtolower(trim(filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL)));
$status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING); // 'on' or 'off'

if (empty($domain) || !in_array($status, ['on', 'off'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $queue->dispatch('create_vhost', [ // We use create_vhost because it routes to vhost_manager.sh
        'sub_action' => 'update_waf',
        'domain'     => $domain,
        'status'     => $status
    ]);

    $state = ($status === 'on') ? 'Enable' : 'Disable';
    echo json_encode(['success' => true, 'message' => "WAF $state queued for $domain."]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}