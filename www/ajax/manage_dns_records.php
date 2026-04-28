<?php
// /opt/panel/www/ajax/manage_dns_records.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING); // 'add' or 'delete'
$domain = strtolower(trim(filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL)));
$type = strtoupper(trim(filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING))); // A, CNAME, TXT, MX
$name = strtolower(trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING)));
$value = trim(filter_input(INPUT_POST, 'value', FILTER_SANITIZE_STRING));

// Basic Validation
$allowed_types = ['A', 'CNAME', 'TXT', 'MX'];
if (!in_array($type, $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Invalid DNS record type.']);
    exit;
}

if (empty($domain) || empty($name) || empty($value)) {
    echo json_encode(['success' => false, 'error' => 'Domain, Name, and Value are required.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $queue->dispatch('manage_dns_record', [
        'action' => $action,
        'domain' => $domain,
        'type'   => $type,
        'name'   => $name,
        'value'  => $value
    ]);

    $verb = ($action === 'add') ? 'Addition' : 'Deletion';
    echo json_encode(['success' => true, 'message' => "DNS $verb queued for $name.$domain!"]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}