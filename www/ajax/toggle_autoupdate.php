<?php
// /opt/panel/www/ajax/toggle_autoupdate.php
require_once 'security.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$enable = filter_var($_POST['enable'] ?? false, FILTER_VALIDATE_BOOLEAN);
$settings_file = '/opt/panel/www/config/settings.json';

// Read existing or create new
$settings = file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];

// Update the flag
$settings['auto_update_stable'] = $enable;

// Save back to file
file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT));
chown($settings_file, 'www-data');

echo json_encode(['success' => true, 'enabled' => $enable]);
?>