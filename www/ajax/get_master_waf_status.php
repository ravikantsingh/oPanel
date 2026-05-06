<?php
// /opt/panel/www/ajax/get_master_waf_status.php
header('Content-Type: application/json');
require_once 'security.php'; // handles CSRF and Session checks

$status = $_POST['status'] ?? '';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$config_file = '/etc/nginx/sites-available/default';

if (!file_exists($config_file)) {
    echo json_encode(['success' => false, 'error' => 'Config not found.']);
    exit;
}

$config = file_get_contents($config_file);

// Use Regex to find "modsecurity on;" ensuring there is NO "#" before it on the same line
if (preg_match('/^[^#]*modsecurity on;/m', $config)) {
    echo json_encode(['success' => true, 'status' => 'on']);
} else {
    echo json_encode(['success' => true, 'status' => 'off']);
}
?>