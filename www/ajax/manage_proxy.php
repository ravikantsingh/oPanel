<?php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

$domain = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL);
$type = $_POST['proxy_type'] ?? 'direct';
$custom_ips = $_POST['custom_ips'] ?? '';
$custom_header = $_POST['custom_header'] ?? 'X-Forwarded-For';

if (empty($domain)) {
    echo json_encode(['success' => false, 'error' => 'Domain is required.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $queue->dispatch('manage_proxy', [
        'domain'        => $domain,
        'proxy_type'    => $type,
        'custom_ips'    => $custom_ips,
        'custom_header' => $custom_header
    ]);
    
    echo json_encode(['success' => true, 'message' => "Proxy configuration queued."]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}