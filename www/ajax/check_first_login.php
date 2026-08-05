<?php
// /opt/panel/www/ajax/check_first_login.php
header('Content-Type: application/json');
require_once 'security.php';

try {
    // If the profile.json file does NOT exist, it means they haven't registered yet
    $is_first_login = !file_exists('/opt/panel/www/config/profile.json');
    
    echo json_encode(['success' => true, 'is_first_login' => $is_first_login]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}