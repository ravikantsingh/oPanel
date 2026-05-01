<?php
// /opt/panel/www/ajax/get_mail_engine_status.php
header('Content-Type: application/json');
require_once 'security.php';

// Check if Postfix has generated its main configuration file
$is_installed = file_exists('/etc/postfix/main.cf');

echo json_encode(['success' => true, 'installed' => $is_installed]);
?>