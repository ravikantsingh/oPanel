<?php
// /opt/panel/www/logout.php
session_name('PANEL_SESSION');
session_start();

// Destroy the session data
$_SESSION = array();
session_destroy();

// Destroy the secure cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header("Location: /login.php");
exit;
?>