<?php
// /opt/panel/www/ajax/secure_panel.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/TaskQueue.php';
require_once '../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING) ?: 'bind';
$db = Database::getInstance()->getConnection();
$queue = new TaskQueue();

try {
    if ($action === 'bind') {
        $domain = strtolower(trim(filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL)));
        
        // SRE Check: Ensure domain exists and has SSL
        $stmt = $db->prepare("SELECT has_ssl FROM domains WHERE domain_name = ?");
        $stmt->execute([$domain]);
        $res = $stmt->fetch();
        
        if (!$res || $res['has_ssl'] == 0) {
            echo json_encode(['success' => false, 'error' => 'Selected domain must exist and have an active SSL certificate.']);
            exit;
        }

        // Update Source of Truth
        $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('panel_domain', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$domain, $domain]);
        
        $queue->dispatch('secure_panel', ['sub_action' => 'bind', 'domain' => $domain]);
        echo json_encode(['success' => true, 'domain' => $domain]);

    } elseif ($action === 'unbind') {
        // Clear Source of Truth
        $db->query("DELETE FROM settings WHERE setting_key = 'panel_domain'");
        
        $queue->dispatch('secure_panel', ['sub_action' => 'unbind']);
        // Fetch server IP for JS redirect
        $ip = trim(shell_exec("curl -s ifconfig.me"));
        echo json_encode(['success' => true, 'ip' => $ip]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>