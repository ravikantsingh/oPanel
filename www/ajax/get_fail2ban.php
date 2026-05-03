<?php
// /opt/panel/www/ajax/get_fail2ban.php
header('Content-Type: application/json');
require_once 'security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

try {
    // 1. Fetch the list of active jails using our NOPASSWD sudoers rule
    $jails_output = shell_exec('sudo /usr/bin/fail2ban-client status 2>/dev/null');
    
    preg_match('/Jail list:\s+(.*)/', $jails_output, $matches);
    $jails = explode(', ', $matches[1] ?? '');
    
    $banned_data = [];

    // 2. Iterate through each jail and fetch the specific banned IPs
    foreach ($jails as $jail) {
        $jail = trim($jail);
        if (empty($jail)) continue;

        $status = shell_exec("sudo /usr/bin/fail2ban-client status " . escapeshellarg($jail) . " 2>/dev/null");
        
        preg_match('/Banned IP list:\s+(.*)/', $status, $ip_matches);
        $ips = explode(' ', $ip_matches[1] ?? '');
        
        foreach ($ips as $ip) {
            $ip = trim($ip);
            if (!empty($ip)) {
                $banned_data[] = [
                    'jail' => $jail,
                    'ip'   => $ip
                ];
            }
        }
    }

    echo json_encode(['success' => true, 'bans' => $banned_data]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}
?>