<?php
// /opt/panel/www/ajax/get_components.php
header('Content-Type: application/json');
require_once 'security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

// The exact Ubuntu APT package names oPanel relies on
$packages = [
    'Nginx Web Server'      => 'nginx',
    'MariaDB Server'        => 'mariadb-server',
    'PHP 8.3 FPM'           => 'php8.3-fpm',
    'Redis Cache Server'    => 'redis-server',
    'PHP Redis Extension'   => 'php8.3-redis',
    'Pure-FTPd'             => 'pure-ftpd',
    'BIND9 DNS Server'      => 'bind9',
    'Fail2ban Intrusion'    => 'fail2ban',
    'Certbot (Let\'s Encrypt)'=> 'certbot',
    'Node.js Environment'   => 'nodejs',
    'Postfix Mail Server'   => 'postfix',
    'Dovecot IMAP/POP3'     => 'dovecot-core',
    'ModSecurity WAF'       => 'libnginx-mod-http-modsecurity'
];

$results = [];

foreach ($packages as $displayName => $pkgName) {
    // dpkg-query cleanly fetches just the version string (e.g., "1.18.0-0ubuntu1.4")
    $version = trim(shell_exec("dpkg-query -W -f='\${Version}' " . escapeshellarg($pkgName) . " 2>/dev/null"));
    
    $results[] = [
        'name'    => $displayName,
        'package' => $pkgName,
        'version' => !empty($version) ? $version : 'Not Installed'
    ];
}

// ---> SRE FIX: Inject PM2 Version Tracking <---
$pm2_version = trim(shell_exec('pm2 -v 2>/dev/null'));
$results[] = [
    'name'    => 'PM2 Process Manager',
    'package' => 'npm pm2',
    'version' => !empty($pm2_version) ? $pm2_version : 'Not Installed'
];

echo json_encode(['success' => true, 'components' => $results]);
?>