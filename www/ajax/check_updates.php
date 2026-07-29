<?php
// /opt/panel/www/ajax/check_updates.php
require_once 'security.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

// 1. Get the local License Key
$key_file = '/opt/panel/license.key';
if (!file_exists($key_file)) {
    echo json_encode(['success' => false, 'error' => 'License key missing.']);
    exit;
}
$license_key = trim(file_get_contents($key_file));

// 2. Fetch data from Stackrium Central
$ch = curl_init('https://stackrium.com/api/updates.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'license_key' => $license_key,
    'is_auto' => 'false' // Manual check from the UI
]));
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $http_code !== 200) {
    echo json_encode(['success' => false, 'error' => 'Failed to reach Stackrium Central.']);
    exit;
}

$data = json_decode($response, true);

// 3. Get the Auto-Update Preference locally
$settings_file = '/opt/panel/www/config/settings.json';
$auto_update_enabled = false;
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
    $auto_update_enabled = $settings['auto_update_stable'] ?? false;
}

// 4. Inject the local settings into the central response and pass it to JS
if (isset($data['success']) && $data['success']) {
    $data['local_auto_update'] = $auto_update_enabled;
    echo json_encode($data);
} else {
    echo json_encode(['success' => false, 'error' => $data['error'] ?? 'Central API Error']);
}
?>