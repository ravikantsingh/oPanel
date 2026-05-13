<?php
// /opt/panel/www/ajax/get_logs.php
header('Content-Type: application/json');
require_once 'security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$log_type = $_POST['type'] ?? '';
$log_file = "";

// ---> THE UNIVERSAL ROUTER <---
switch ($log_type) {
    
    // --- GLOBAL SYSTEM LOGS ---
    case 'daemon':
        $log_file = "/opt/panel/logs/daemon.log";
        break;
    case 'fail2ban':
        $log_file = "/var/log/fail2ban.log";
        break;
    case 'updater':
        $log_file = "/opt/panel/logs/auto_update.log";
        break;
    case 'syslog':
        $log_file = "/var/log/syslog";
        break;

    // --- CONTEXTUAL WEBSITE LOGS ---
    case 'error':
    case 'access':
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $domain = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL);

        if (empty($username) || empty($domain)) {
            echo json_encode(['success' => false, 'error' => 'Username and Domain are required to view website logs.']);
            exit;
        }

        $file_name = ($log_type === 'access') ? 'access.log' : 'error.log';
        $log_file = "/home/$username/web/$domain/logs/$file_name";
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid log type requested.']);
        exit;
}

// Ensure the target actually exists
if (!file_exists($log_file)) {
    echo json_encode(['success' => false, 'error' => "Log file is currently empty or does not exist at: $log_file"]);
    exit;
}

// Safely tail the log (increased to 100 lines for better debugging context)
$safe_path = escapeshellarg($log_file);
$output = shell_exec("tail -n 100 $safe_path");

if (empty(trim($output))) {
    $output = "File exists, but no logs have been recorded yet.";
}

echo json_encode([
    'success' => true, 
    'logs' => htmlspecialchars($output)
]);
?>