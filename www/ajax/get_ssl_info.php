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

    // 2. SSL is enabled! Let's read the actual certificate file from the disk.
    // Let's Encrypt default path is our primary check
    $certPath = "/etc/letsencrypt/live/{$domain}/cert.pem";
    
    // If not found in Let's Encrypt, check the custom oPanel path
    if (!file_exists($certPath)) {
        $certPath = "/etc/nginx/ssl/{$domain}.crt";
    }

    if (!file_exists($certPath)) {
        // Edge case: Database says yes, but file is missing from disk!
        echo json_encode(['success' => false, 'error' => 'Certificate file missing from server disk.']);
        exit;
    }

    // 3. Parse the certificate using OpenSSL
    $certData = openssl_x509_parse(file_get_contents($certPath));

    if (!$certData) {
        echo json_encode(['success' => false, 'error' => 'Failed to parse physical certificate data.']);
        exit;
    }

    // 4. Extract Issuer and Dates safely
    $issuer = $certData['issuer']['O'] ?? 'Unknown Authority';
    if (strpos($issuer, 'Let\'s Encrypt') !== false) { 
        $issuer = "Let's Encrypt"; 
    }

    $validFromTimestamp = $certData['validFrom_time_t'] ?? time();
    $validToTimestamp = $certData['validTo_time_t'] ?? time();
    
    $validFromDate = date('M d, Y', $validFromTimestamp);
    $validToDate = date('M d, Y', $validToTimestamp);

    // 5. Calculate Days Remaining
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

    // 6. Calculate Progress Bar Percentage
    $totalLifespan = max(1, $validToTimestamp - $validFromTimestamp); // Prevent division by zero
    $timeElapsed = max(0, $now - $validFromTimestamp);
    $percentUsed = ($timeElapsed / $totalLifespan) * 100;
    
    // We want the bar to shrink as it gets closer to 0
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
    // Catch database or other exceptions cleanly
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}
?>