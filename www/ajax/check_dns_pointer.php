<?php
// /opt/panel/www/ajax/check_dns_pointer.php
header('Content-Type: application/json');
require_once 'security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['domain'])) {
    echo json_encode(['success' => false, 'error' => 'Domain required']);
    exit;
}

$domain = trim(strip_tags($_POST['domain']));

// 1. Fetch the server's true public IP
// We use a fast 2-second timeout to prevent the script from hanging if the network lags
$context = stream_context_create(['http' => ['timeout' => 2]]);
$server_ip = @file_get_contents('https://api.ipify.org', false, $context);

// Fallback to local server IP if external fetch fails
if (!$server_ip) {
    $server_ip = $_SERVER['SERVER_ADDR'];
}

// 2. Perform the DNS Lookup
$records = @dns_get_record($domain, DNS_A);
$pointing = false;
$resolved_ip = null;

if ($records) {
    foreach ($records as $record) {
        if ($record['ip'] === $server_ip) {
            $pointing = true;
            $resolved_ip = $record['ip'];
            break;
        }
    }
    // If not pointing to us, grab whatever IP it is pointing to for debugging
    if (!$pointing && isset($records[0]['ip'])) {
        $resolved_ip = $records[0]['ip'];
    }
}

// 3. Return the exact state to the JS frontend
echo json_encode([
    'success' => true,
    'domain' => $domain,
    'pointing' => $pointing,
    'resolved_ip' => $resolved_ip,
    'server_ip' => $server_ip
]);
?>