<?php
// /opt/panel/www/ajax/run_update.php
require_once 'security.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$url = filter_var($_POST['url'] ?? '', FILTER_VALIDATE_URL);
$channel = preg_replace('/[^a-z0-9-]/', '', strtolower($_POST['channel'] ?? 'stable'));

if (empty($url)) {
    echo json_encode(['success' => false, 'error' => 'Invalid download URL.']);
    exit;
}

// 1. Reset the progress JSON file so the UI starts at 0%
$status_file = '/opt/panel/www/config/update_status.json';
file_put_contents($status_file, json_encode(['progress' => 0, 'step' => 'Preparing...', 'status' => 'starting']));
chown($status_file, 'www-data');

// 2. Launch the Bash script in the background!
// The > /dev/null 2>&1 & detaches it from PHP so Nginx can close the connection immediately.
$cmd = "sudo /bin/bash /opt/panel/scripts/updater.sh " . escapeshellarg($url) . " " . escapeshellarg($channel) . " > /dev/null 2>&1 &";
exec($cmd);

echo json_encode(['success' => true]);
?>