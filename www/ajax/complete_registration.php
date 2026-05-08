<?php
// /opt/panel/www/ajax/complete_registration.php

// 1. Strict Security Inclusion (This safely handles Session Auth & CSRF)
require_once 'security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Request Method.']);
    exit;
}

// 2. Capture POST data securely (PHP 8.3 Safe)
$name = isset($_POST['owner_name']) ? trim(strip_tags($_POST['owner_name'])) : '';
$email = isset($_POST['owner_email']) ? filter_var(trim($_POST['owner_email']), FILTER_SANITIZE_EMAIL) : '';
$company = isset($_POST['company']) ? trim(strip_tags($_POST['company'])) : '';
$country = isset($_POST['country']) ? trim(strip_tags($_POST['country'])) : '';

$key_file = '/opt/panel/license.key';
if (!file_exists($key_file)) {
    echo json_encode(['success' => false, 'error' => 'License key file not found on VPS.']);
    exit;
}

$license_key = trim(file_get_contents($key_file));
if (empty($license_key)) {
    echo json_encode(['success' => false, 'error' => 'License key is blank on VPS.']);
    exit;
}

// 3. Ping Stackrium Central API
$api_endpoint = 'https://stackrium.com/api/claim_license.php';
$post_data = http_build_query([
    'license_key' => $license_key,
    'owner_name' => $name,
    'owner_email' => $email,
    'company' => $company,
    'country' => $country
]);

$ch = curl_init($api_endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
// Temporarily bypass strict SSL validation in case your central SSL isn't propagating yet
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// 4. Aggressive Error Handling (Helps with debugging central server)
if ($response === false) {
    echo json_encode(['success' => false, 'error' => 'VPS cURL Error: ' . $curl_error]);
    exit;
}

if ($http_code !== 200) {
    echo json_encode(['success' => false, 'error' => 'Central API returned HTTP ' . $http_code . '. Response: ' . strip_tags($response)]);
    exit;
}

$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'Central API returned invalid JSON: ' . strip_tags($response)]);
    exit;
}

// 5. Success Output
if (isset($data['success']) && $data['success'] === true) {
    $profile_data = json_encode([
        'name' => $name,
        'email' => $email,
        'company' => $company,
        'country' => $country,
        'registered_on' => time()
    ]);
    
    // Save the profile so the modal never shows again
    file_put_contents('/opt/panel/www/config/profile.json', $profile_data);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $data['error'] ?? 'Central server rejected registration.']);
}
?>