<?php
// /opt/panel/www/ajax/manage_mail_dns.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

$domain = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL);
$provider = filter_input(INPUT_POST, 'provider', FILTER_SANITIZE_STRING);

if (!$domain || !$provider) {
    echo json_encode(['success' => false, 'error' => 'Domain and Provider are required.']);
    exit;
}

try {
    $queue = new TaskQueue();
    
    // Dispatch to the Phase 1 script we wrote: /opt/panel/scripts/mail_dns_manager.sh
    $queue->dispatch('manage_mail_dns', [
        'domain'   => $domain,
        'provider' => $provider
    ]);

    echo json_encode(['success' => true, 'message' => ucfirst($provider) . " DNS integration queued successfully!"]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}
?>