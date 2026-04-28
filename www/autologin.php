<?php
// /opt/panel/www/autologin.php
require_once __DIR__ . '/classes/Database.php';

$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

if (empty($token) || strlen($token) !== 64) {
    die("Invalid or missing authentication token.");
}

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Verify the token exists
    $stmt = $db->prepare("SELECT * FROM panel_core.admin_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $validToken = $stmt->fetch();

    if ($validToken) {
        // 2. Destroy the token immediately so it can NEVER be reused
        $del = $db->prepare("DELETE FROM panel_core.admin_tokens WHERE token = ?");
        $del->execute([$token]);

        // 3. Configure the secure session exactly like index.php
        session_name('PANEL_SESSION');
        session_set_cookie_params([
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();

        // 4. Grant Admin Access and Generate CSRF
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // 5. Redirect to the dashboard
        header("Location: /index.php");
        exit;
    } else {
        die("Token expired or already used.");
    }

} catch (Exception $e) {
    die("Database Error.");
}
?>