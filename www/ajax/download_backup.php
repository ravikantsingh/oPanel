<?php
// ---> NEW: SECURE SESSION ISOLATION <---
session_name('PANEL_SESSION');
session_set_cookie_params([
    'secure' => true,      
    'httponly' => true,    
    'samesite' => 'Strict' 
]);
// ---------------------------------------
// We still check session security, but we use GET because downloads happen via standard links
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Unauthorized Access.");
}

$file = basename($_GET['file'] ?? '');
$type = $_GET['type'] ?? '';

if (empty($file) || !in_array($type, ['Website', 'Database'])) die("Invalid request.");

$dir = ($type === 'Website') ? '/opt/panel/backups/websites/' : '/opt/panel/backups/databases/';
$path = $dir . $file;

if (!file_exists($path)) die("File not found.");

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.basename($path).'"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;