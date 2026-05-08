<?php
// /opt/panel/www/ajax/sync_license.php
require_once 'security.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

// Execute the bash script securely using our new sudoers rule
$output = shell_exec('sudo /bin/bash /opt/panel/scripts/heartbeat.sh 2>&1');

echo json_encode([
    'success' => true,
    'message' => 'License synchronized successfully.',
    'log' => trim($output)
]);
?>