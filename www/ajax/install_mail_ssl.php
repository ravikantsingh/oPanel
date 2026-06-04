<?php
// /opt/panel/www/ajax/install_mail_ssl.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$domain = strtolower(trim(filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL)));
$mailDomain = strtolower(trim(filter_input(INPUT_POST, 'mail_domain', FILTER_SANITIZE_URL)));

// Strict regex validation for the mail subdomain
if (!preg_match('/^(?!\\-)(?:(?:[a-zA-Z\\d][a-zA-Z\\d\\-]{0,61})?[a-zA-Z\\d]\\.){1,126}(?!\\d+)[a-zA-Z\\d]{1,63}$/', $mailDomain)) {
    echo json_encode(['success' => false, 'error' => 'Invalid mail domain format.']);
    exit;
}

try {
    $queue = new TaskQueue();
    $payload = [
        'domain' => $domain, 
        'mail_domain' => $mailDomain,
        'sub_action' => 'mail_letsencrypt',
        'email' => 'admin@' . $domain
    ];

    // We dispatch this to the existing 'install_ssl' bash script
    $taskId = $queue->dispatch('install_ssl', $payload);

    echo json_encode([
        'success' => true, 
        'message' => "SSL provisioning queued for $mailDomain!",
        'task_id' => $taskId
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>