<?php
// /opt/panel/www/ajax/manage_https_routing.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

$domain = strtolower(trim(strip_tags($_POST['domain'] ?? '')));

// Cast the checkbox values to the exact strings expected by our Bash parser
$force_https = isset($_POST['force_https']) ? 'true' : 'false';
$enable_hsts = isset($_POST['enable_hsts']) ? 'true' : 'false';
$hsts_max_age = (int)($_POST['hsts_max_age'] ?? 15552000);
$hsts_subdomains = isset($_POST['hsts_subdomains']) ? 'true' : 'false';
$hsts_preload = isset($_POST['hsts_preload']) ? 'true' : 'false';

if (!$domain) {
    echo json_encode(['success' => false, 'error' => 'Domain is required.']);
    exit;
}

try {
    $queue = new TaskQueue();
    
    // ---> THE FIX: Dispatch to 'create_vhost' so it routes to vhost_manager.sh <---
    $taskId = $queue->dispatch('create_vhost', [
        'sub_action'      => 'update_routing',
        'domain'          => $domain,
        'force_https'     => $force_https,
        'hsts_enabled'    => $enable_hsts,
        'hsts_max_age'    => $hsts_max_age,
        'hsts_subdomains' => $hsts_subdomains,
        'hsts_preload'    => $hsts_preload
    ]);

    echo json_encode([
        'success' => true, 
        'message' => 'Routing rules queued for Nginx compilation.',
        'task_id' => $taskId
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>