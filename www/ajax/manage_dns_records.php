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

// Bypassing removed FILTER_SANITIZE_STRING for PHP 8.3 compatibility
$action = strip_tags($_POST['action'] ?? '');
$domain = strtolower(trim(strip_tags($_POST['domain'] ?? '')));
$type = strtoupper(trim(strip_tags($_POST['type'] ?? '')));
$name = strtolower(trim(strip_tags($_POST['name'] ?? '')));
$value = trim(strip_tags($_POST['value'] ?? ''));

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