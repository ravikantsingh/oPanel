<?php
// /opt/panel/www/ajax/get_waf_settings.php
header('Content-Type: application/json');
require_once 'security.php'; // Inherits Session validation & CSRF checks

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$config_file = '/opt/panel/www/config/waf_settings.json';

if (file_exists($config_file)) {
    $json_data = file_get_contents($config_file);
    $data = json_decode($json_data, true);
    
    if (isset($data['waf_branch'])) {
        echo json_encode(['success' => true, 'branch' => $data['waf_branch']]);
        exit;
    }
}

// Fallback if file doesn't exist yet
echo json_encode(['success' => true, 'branch' => 'v3.3/master']);
?>