<?php
// /opt/panel/www/ajax/get_connection_info.php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------

require_once '../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['domain'])) {
    echo json_encode(['success' => false, 'error' => 'Domain is required.']);
    exit;
}

$domain = strtolower(trim(filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL)));

try {
    $db = Database::getInstance()->getConnection();
    
    // Fetch Domain & User Info
    $stmt = $db->prepare("SELECT domain_name, username, php_version FROM domains WHERE domain_name = ?");
    $stmt->execute([$domain]);
    $domainData = $stmt->fetch();

    if (!$domainData) {
        throw new Exception("Domain not found.");
    }

    $username = $domainData['username'];

    // Try to get public IP, fallback to server local IP
    $serverIp = file_get_contents('https://api.ipify.org') ?: $_SERVER['SERVER_ADDR'];

    // Assemble the "More Info" payload
    $info = [
        'server_ip'    => $serverIp,
        'domain'       => $domainData['domain_name'],
        'username'     => $username,
        'ssh_command'  => "ssh {$username}@{$serverIp}",
        'web_root'     => "/home/{$username}/web/{$domainData['domain_name']}/public_html",
        'nginx_conf'   => "/etc/nginx/sites-available/{$domainData['domain_name']}.conf",
        'php_socket'   => "/run/php/php{$domainData['php_version']}-fpm-{$username}.sock",
        'db_host'      => '127.0.0.1:3306 (MariaDB)'
    ];

    echo json_encode(['success' => true, 'data' => $info]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}