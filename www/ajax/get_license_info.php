<?php
// /opt/panel/www/ajax/get_license_info.php
require_once 'security.php';
header('Content-Type: application/json');

$response = [
    'success' => true,
    'key' => 'Unknown',
    'status' => 'Unknown',
    'ip' => 'Unknown',
    'owner_name' => 'Unregistered',
    'owner_email' => 'N/A'
];

// 1. Get the Key
$key_file = '/opt/panel/license.key';
if (file_exists($key_file)) {
    $response['key'] = trim(file_get_contents($key_file));
}

// 2. Get the Status and IP
$status_file = '/opt/panel/www/config/license_status.json';
if (file_exists($status_file)) {
    $status_data = json_decode(file_get_contents($status_file), true);
    if ($status_data) {
        $response['status'] = $status_data['status'] ?? 'Unknown';
        $response['ip'] = $status_data['authorized_ip'] ?? $_SERVER['SERVER_ADDR'];
        $response['expiry'] = $status_data['expiry'] ?? 'Unknown'; // <-- ADD THIS LINE
    }
}

// 3. Get the Owner Profile (From the First-Login Gatekeeper)
$profile_file = '/opt/panel/www/config/profile.json';
if (file_exists($profile_file)) {
    $profile_data = json_decode(file_get_contents($profile_file), true);
    if ($profile_data) {
        $response['owner_name'] = $profile_data['name'] ?? 'Unregistered';
        $response['owner_email'] = $profile_data['email'] ?? 'N/A';
    }
}

echo json_encode($response);
?>