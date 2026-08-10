<?php
// /opt/panel/www/pma/signon.php
declare(strict_types=1);

session_name('SignonSession');
session_start();

$user = $_SESSION['PMA_single_signon_user'] ?? null;
$password = $_SESSION['PMA_single_signon_password'] ?? null;

session_write_close();

if (empty($user) || empty($password)) {
    die("<div style='background:#111;color:red;padding:20px;font-family:sans-serif;'>Signon Authentication Error: Session context missing or expired. Please launch from the Stackrium panel.</div>");
}

// Redirect straight into the main phpMyAdmin interface
header('Location: index.php');
exit;
?>