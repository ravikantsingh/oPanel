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
session_write_close();
?>