<?php
// /opt/panel/www/ajax/manage_https_routing.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

$domain = strtolower(trim(strip_tags($_POST['domain'] ?? '')));
$force_https = isset($_POST['force_https']) ? 1 : 0;
$enable_hsts = isset($_POST['enable_hsts']) ? 1 : 0;
$hsts_max_age = (int)($_POST['hsts_max_age'] ?? 15552000);
$hsts_subdomains = isset($_POST['hsts_subdomains']) ? 1 : 0;
$hsts_preload = isset($_POST['hsts_preload']) ? 1 : 0;

if (!$domain) {
    echo json_encode(['success' => false, 'error' => 'Domain is required.']);
    exit;
}

try {
    $queue = new TaskQueue();
    
    // Dispatch to the backend Bash Engine
    $taskId = $queue->dispatch('https_routing_manager', [
        'domain'          => $domain,
        'force_https'     => $force_https,
        'enable_hsts'     => $enable_hsts,
        'hsts_max_age'    => $hsts_max_age,
        'hsts_subdomains' => $hsts_subdomains,
        'hsts_preload'    => $hsts_preload
    ]);

    echo json_encode(['success' => true, 'message' => "Routing rules queued for application!"]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>