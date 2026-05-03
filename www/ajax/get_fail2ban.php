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
    $jail_stats = []; // New array to hold the rich telemetry for the modal

    // 2. Iterate through each jail and fetch the specific telemetry
    foreach ($jails as $jail) {
        $jail = trim($jail);
        if (empty($jail)) continue;

        $status = shell_exec("sudo /usr/bin/fail2ban-client status " . escapeshellarg($jail) . " 2>/dev/null");
        
        // --- NEW: Parse the rich telemetry using Regex ---
        preg_match('/Currently failed:\s+(\d+)/', $status, $cur_failed);
        preg_match('/Total failed:\s+(\d+)/', $status, $tot_failed);
        preg_match('/File list:\s+(.*)/', $status, $file_list);
        preg_match('/Currently banned:\s+(\d+)/', $status, $cur_banned);
        preg_match('/Total banned:\s+(\d+)/', $status, $tot_banned);
        
        $jail_stats[] = [
            'name' => $jail,
            'currently_failed' => $cur_failed[1] ?? 0,
            'total_failed' => $tot_failed[1] ?? 0,
            'currently_banned' => $cur_banned[1] ?? 0,
            'total_banned' => $tot_banned[1] ?? 0,
            'file_list' => trim($file_list[1] ?? 'Unknown')
        ];
        // -----------------------------------------------

        // Extract IPs just like before
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

    // Return both arrays to the frontend
    echo json_encode([
        'success' => true, 
        'bans' => $banned_data,
        'stats' => $jail_stats
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}
?>