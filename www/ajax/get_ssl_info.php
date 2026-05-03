<?php
// /opt/panel/www/ajax/get_ssl_info.php
header('Content-Type: application/json');

// ---> REQUIRE THE GATEKEEPER FIRST (Handles Session & CSRF) <---
require_once 'security.php';
// ---------------------------------------------------------------

// ---> REQUIRE THE SINGLETON DATABASE <---
require_once '../classes/Database.php';

// Strict Method Verification
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed. Must use POST.']);
    exit;
}

// Bypassing removed FILTER_SANITIZE_STRING for PHP 8.3 compatibility
$domain = strtolower(trim(strip_tags($_POST['domain'] ?? '')));

if (empty($domain)) {
    echo json_encode(['success' => false, 'error' => 'Target domain is required.']);
    exit;
}

try {
    // 1. Check if the database says SSL is enabled using the Singleton connection
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT has_ssl FROM domains WHERE domain_name = ?");
    $stmt->execute([$domain]);
    $result = $stmt->fetch();

    if (!$result || $result['has_ssl'] == 0) {
        // State A: Unsecured
        echo json_encode(['success' => true, 'is_secured' => false]);
        exit;
    }

    // 2. SSL is enabled! Use the SRE Sudoers Bridge to parse the root-locked certificate directly.
    $certPath = "/etc/letsencrypt/live/{$domain}/cert.pem";
    
    // We suppress errors (2>/dev/null) so a missing file just returns empty string instead of crashing
    $output = shell_exec("sudo /usr/bin/openssl x509 -in " . escapeshellarg($certPath) . " -noout -issuer -dates 2>/dev/null");

    // If Let's Encrypt fails, try the custom oPanel SSL path
    if (empty(trim($output))) {
        $certPath = "/etc/nginx/ssl/{$domain}.crt";
        $output = shell_exec("sudo /usr/bin/openssl x509 -in " . escapeshellarg($certPath) . " -noout -issuer -dates 2>/dev/null");
    }

    // If it's STILL empty, the file literally doesn't exist.
    if (empty(trim($output))) {
        echo json_encode(['success' => false, 'error' => 'Certificate file missing or inaccessible on disk.']);
        exit;
    }

    // 3. Parse the OpenSSL text output
    // Example Output: 
    // issuer=C = US, O = Let's Encrypt, CN = R3
    // notBefore=May  3 00:00:00 2026 GMT
    // notAfter=Aug  1 00:00:00 2026 GMT

    $issuer = 'Unknown Authority';
    if (strpos($output, "Let's Encrypt") !== false || strpos($output, "R3") !== false || strpos($output, "E1") !== false) {
        $issuer = "Let's Encrypt";
    } elseif (preg_match('/O\s*=\s*([^,\n]+)/', $output, $matches)) {
        $issuer = trim($matches[1]);
    }

    preg_match('/notBefore=(.*)/', $output, $fromMatches);
    preg_match('/notAfter=(.*)/', $output, $toMatches);

    $validFromTimestamp = strtotime($fromMatches[1] ?? 'now');
    $validToTimestamp = strtotime($toMatches[1] ?? 'now');
    
    $validFromDate = date('M d, Y', $validFromTimestamp);
    $validToDate = date('M d, Y', $validToTimestamp);

    // 4. Calculate Days Remaining
    $now = time();
    $secondsRemaining = $validToTimestamp - $now;
    $daysRemaining = floor($secondsRemaining / (60 * 60 * 24));

    // Determine Status Colors for the UI
    $statusColor = 'success';
    if ($daysRemaining < 0) {
        $statusColor = 'danger';
        $daysRemaining = 0;
    } elseif ($daysRemaining <= 30) {
        $statusColor = 'warning';
    }

    // 5. Calculate Progress Bar Percentage
    $totalLifespan = max(1, $validToTimestamp - $validFromTimestamp); 
    $timeElapsed = max(0, $now - $validFromTimestamp);
    $percentUsed = ($timeElapsed / $totalLifespan) * 100;
    
    $percentRemaining = max(0, min(100, 100 - $percentUsed));

    // Return the payload back to the JS Modal
    echo json_encode([
        'success' => true,
        'is_secured' => true,
        'issuer' => $issuer,
        'valid_from' => $validFromDate,
        'valid_until' => $validToDate,
        'days_remaining' => $daysRemaining,
        'percent_remaining' => round($percentRemaining),
        'status_color' => $statusColor
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}
?>