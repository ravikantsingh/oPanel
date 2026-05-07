<?php
// /opt/panel/www/ajax/security.php

// ---> NEW: FORCE SECURE COOKIES <---
session_name('PANEL_SESSION');
session_set_cookie_params([
    'secure' => true,      // Only transmit over HTTPS
    'httponly' => true,    // Block Javascript from reading the cookie
    'samesite' => 'Strict' // Prevent Cross-Site Request Forgery
]);
// -----------------------------------
session_start();

// 1. Check if the user is actually logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Invalid Session.']);
    exit;
}

// 2. Extract the CSRF Token from the Nginx HTTP Headers
$request_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

// 3. Validate the Token
if (empty($request_token) || !hash_equals($_SESSION['csrf_token'], $request_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Security Error: CSRF Validation Failed.']);
    exit;
}

// ---> THE FIX: Unlock the session file instantly! <---
// This allows other AJAX requests to run concurrently without waiting
session_write_close();

// =================================================================
// 4. STACKRIUM COMMERCIAL LICENSE ENFORCEMENT
// =================================================================
$license_file = '/opt/panel/www/config/license_status.json';

if (file_exists($license_file)) {
    $license_data = json_decode(file_get_contents($license_file), true);
    
    // Check if the central server returned an expired or suspended status
    if (isset($license_data['status']) && $license_data['status'] !== 'active') {
        
        // Define specific error messages based on the state
        $error_msg = 'Stackrium Control License Error.';
        if ($license_data['status'] === 'expired') {
            $error_msg = 'Your Stackrium Control license has expired. Please visit stackrium.com to renew and unlock your panel.';
        } elseif ($license_data['status'] === 'suspended') {
            $error_msg = 'This server license has been suspended. Please contact Stackrium support.';
        } elseif ($license_data['status'] === 'invalid') {
            $error_msg = 'Invalid License Key detected. Panel locked.';
        }

        // Return a hard 403 Forbidden with the commercial error
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'error' => $error_msg,
            'license_status' => $license_data['status']
        ]);
        exit; // Terminate the script immediately, blocking the action
    }
}
?>