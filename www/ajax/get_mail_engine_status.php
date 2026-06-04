<?php
// /opt/panel/www/ajax/get_mail_engine_status.php
header('Content-Type: application/json');
require_once 'security.php';

// Check if Postfix has generated its main configuration file
$is_installed = file_exists('/etc/postfix/main.cf');

// Force the domain to be cleanly trimmed and lowercase
$domain = isset($_POST['domain']) ? strtolower(trim($_POST['domain'])) : '';
$mailDomain = 'mail.' . $domain;

$targetCertPath = "/etc/letsencrypt/live/$mailDomain/fullchain.pem";

// FIX: Added '-subject' so a successful check returns a valid identification string
$certCheck = shell_exec('sudo /usr/bin/openssl x509 -in ' . escapeshellarg($targetCertPath) . ' -subject -noout 2>/dev/null');

// Evaluate active status based on the presence of the subject identifier
$isSslActive = false;
if (!empty($certCheck) && strpos(strtolower($certCheck), 'subject') !== false) {
    $isSslActive = true;
}

echo json_encode([
    'success' => true,
    'installed' => $is_installed, 
    'ssl_active' => $isSslActive
]);
?>