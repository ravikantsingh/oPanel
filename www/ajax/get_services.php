<?php
// /opt/panel/www/ajax/get_services.php
header('Content-Type: application/json');
require_once 'security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

// Define services and their UI display names
$services = [
    'Reverse Proxy Server (Nginx)' => 'nginx',
    'Database Server (MariaDB)'    => 'mariadb',
    'PHP FastCGI (PHP 8.3 FPM)'    => 'php8.3-fpm',
    'In-Memory Cache (Redis)'      => 'redis-server',
    'FTP Server (Pure-FTPd)'       => 'pure-ftpd',
    'DNS Server (BIND9)'           => 'bind9',
    'Intrusion Prevention (Fail2ban)' => 'fail2ban',
    'SMTP Server (Postfix)'        => 'postfix',
    'IMAP/POP3 Server (Dovecot)'   => 'dovecot',
    'PM2 Process Manager'          => 'pm2-root',
    'oPanel Background Daemon'     => 'panel-daemon'
];

$core_services = ['nginx', 'mariadb', 'panel-daemon'];
$results = [];

foreach ($services as $name => $svc) {
    // systemctl is-active returns 'active', 'inactive', 'failed', or 'unknown' (if not installed)
    $status = trim(shell_exec("systemctl is-active " . escapeshellarg($svc) . " 2>/dev/null"));
    
    $results[] = [
        'name'     => $name,
        'service'  => $svc,
        'status'   => $status,
        'can_stop' => !in_array($svc, $core_services) // The UI uses this flag to hide the stop button
    ];
}

echo json_encode(['success' => true, 'services' => $results]);
?>