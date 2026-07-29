<?php
// /opt/panel/www/ajax/get_mail_engine_status.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../config/database.php'; 

// Check if Postfix has generated its main configuration file
$is_installed = file_exists('/etc/postfix/main.cf');

// Force the domain to be cleanly trimmed and lowercase
$domain = isset($_POST['domain']) ? strtolower(trim($_POST['domain'])) : '';
$mailDomain = 'mail.' . $domain;

$targetCertPath = "/etc/letsencrypt/live/$mailDomain/fullchain.pem";

$certCheck = shell_exec('sudo /usr/bin/openssl x509 -in ' . escapeshellarg($targetCertPath) . ' -subject -noout 2>/dev/null');

$isSslActive = false;
if (!empty($certCheck) && strpos(strtolower($certCheck), 'subject') !== false) {
    $isSslActive = true;
}

// --- NEW RELAY STATUS LOGIC ---
$relay_active = false;
$relay_host = '';

try {
    // Instantiate PDO
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch Global SMTP Relay Status safely
    $relay_stmt = $pdo->query("SELECT * FROM mail_global_settings WHERE id = 1");
    $relay_data = $relay_stmt->fetch(PDO::FETCH_ASSOC);

    if ($relay_data) {
        $relay_active = (bool)$relay_data['smtp_relay_active'];
        $relay_host = $relay_data['relay_host'];
    }
} catch (PDOException $e) {
    // Fail silently for the relay status if the DB query fails so the modal still opens
}
// ------------------------------

echo json_encode([
    'success' => true,
    'installed' => $is_installed,
    'ssl_active' => $isSslActive,
    'relay_active' => $relay_active,
    'relay_host' => $relay_host
]);
?>