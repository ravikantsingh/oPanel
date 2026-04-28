<?php
// /opt/panel/www/ajax/manage_waf_rules.php
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
// We use $_POST directly here because filter_input strips newlines/formatting
$custom_rules = $_POST['custom_rules'] ?? ''; 

if (empty($domain)) {
    echo json_encode(['success' => false, 'error' => 'Domain is required.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $queue->dispatch('create_vhost', [ 
        'sub_action'   => 'update_waf_rules',
        'domain'       => $domain,
        'custom_rules' => $custom_rules
    ]);

    echo json_encode(['success' => true, 'message' => "Custom WAF rules queued for compilation."]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}