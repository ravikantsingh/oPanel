<?php
header('Content-Type: application/json');
require_once 'security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

try {
    $jail_file = '/etc/fail2ban/jail.local';
    
    if (!file_exists($jail_file)) {
        throw new Exception("jail.local configuration file not found.");
    }

    $content = file_get_contents($jail_file);
    
    // Extract using regex
    preg_match('/^bantime\s*=\s*(\d+)([mhd])/m', $content, $bantime);
    preg_match('/^findtime\s*=\s*(\d+)([mhd])/m', $content, $findtime);
    preg_match('/^maxretry\s*=\s*(\d+)/m', $content, $maxretry);

    echo json_encode([
        'success' => true,
        'bantime_val' => $bantime[1] ?? '1',
        'bantime_unit' => $bantime[2] ?? 'h',
        'findtime_val' => $findtime[1] ?? '10',
        'findtime_unit' => $findtime[2] ?? 'm',
        'maxretry' => $maxretry[1] ?? '5'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>