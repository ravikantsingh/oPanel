<?php
// /opt/panel/www/ajax/get_ssl_info.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$domain = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL);
if (!$domain) { echo json_encode(['success' => false, 'error' => 'Domain required.']); exit; }

$is_secured = false;
$issuer = ''; $valid_from = ''; $valid_until = ''; $days_remaining = 0; $percent = 0; $color = 'danger';

// --- 1. CERTIFICATE TELEMETRY (Using the Root Sudo Bridge) ---
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT has_ssl FROM domains WHERE domain_name = ?");
$stmt->execute([$domain]);
$result = $stmt->fetch();

if ($result && $result['has_ssl'] == 1) {
    
    $certPath = "/etc/letsencrypt/live/{$domain}/cert.pem";
    $output = shell_exec("sudo /usr/bin/openssl x509 -in " . escapeshellarg($certPath) . " -noout -issuer -dates 2>/dev/null");

    if (empty(trim($output))) {
        // Fallback to custom SSL path
        $certPath = "/etc/nginx/ssl/custom/{$domain}/fullchain.pem";
        $output = shell_exec("sudo /usr/bin/openssl x509 -in " . escapeshellarg($certPath) . " -noout -issuer -dates 2>/dev/null");
    }

    if (!empty(trim($output))) {
        $is_secured = true;
        
        if (strpos($output, "Let's Encrypt") !== false || strpos($output, "R3") !== false || strpos($output, "E1") !== false) {
            $issuer = "Let's Encrypt";
        } elseif (preg_match('/O\s*=\s*([^,\n]+)/', $output, $matches)) {
            $issuer = trim($matches[1]);
        } else {
            $issuer = "Custom / Unknown";
        }

        preg_match('/notBefore=(.*)/', $output, $fromMatches);
        preg_match('/notAfter=(.*)/', $output, $toMatches);

        $validFromTimestamp = strtotime($fromMatches[1] ?? 'now');
        $validToTimestamp = strtotime($toMatches[1] ?? 'now');
        
        $valid_from = date('M d, Y', $validFromTimestamp);
        $valid_until = date('M d, Y', $validToTimestamp);

        $now = time();
        $days_remaining = floor(($validToTimestamp - $now) / 86400);
        if ($days_remaining < 0) $days_remaining = 0;

        if ($days_remaining > 30) $color = 'success';
        elseif ($days_remaining > 10) $color = 'warning';

        $totalLifespan = max(1, $validToTimestamp - $validFromTimestamp); 
        $timeElapsed = max(0, $now - $validFromTimestamp);
        $percent = max(0, min(100, 100 - (($timeElapsed / $totalLifespan) * 100)));
    }
}

// --- 2. NGINX ROUTING PARSER (Syncs the UI Switches) ---
$vhost = "/etc/nginx/sites-available/$domain.conf";
$force_https = false; $hsts_enabled = false; 
$hsts_max = 15552000; $hsts_sub = false; $hsts_pre = false;

if (file_exists($vhost)) {
    $vhost_content = file_get_contents($vhost);
    if (strpos($vhost_content, '#force_https') !== false) $force_https = true;
    if (preg_match('/add_header Strict-Transport-Security "([^"]+)" always; #hsts/', $vhost_content, $matches)) {
        $hsts_enabled = true;
        $hsts_str = $matches[1];
        if (preg_match('/max-age=(\d+)/', $hsts_str, $m)) $hsts_max = (int)$m[1];
        if (strpos($hsts_str, 'includeSubDomains') !== false) $hsts_sub = true;
        if (strpos($hsts_str, 'preload') !== false) $hsts_pre = true;
    }
}

// --- 3. AUTO-RENEWAL STATE (Using Root Sudo Bridge) ---
$disabled_conf = escapeshellarg("/etc/letsencrypt/renewal/$domain.conf.disabled");
$auto_renew = (trim(shell_exec("sudo ls $disabled_conf 2>/dev/null")) !== "") ? false : true;

echo json_encode([
    'success' => true,
    'is_secured' => $is_secured,
    'issuer' => $issuer,
    'valid_from' => $valid_from,
    'valid_until' => $valid_until,
    'days_remaining' => $days_remaining,
    'percent_remaining' => round($percent),
    'status_color' => $color,
    'force_https' => $force_https,
    'hsts_enabled' => $hsts_enabled,
    'hsts_max_age' => $hsts_max,
    'hsts_subdomains' => $hsts_sub,
    'hsts_preload' => $hsts_pre,
    'auto_renew' => $auto_renew
]);
?>