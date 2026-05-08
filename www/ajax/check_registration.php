<?php
header('Content-Type: application/json');
require_once 'security.php';

$profile_file = '/opt/panel/www/config/profile.json';

if (file_exists($profile_file)) {
    echo json_encode(['success' => true, 'is_registered' => true]);
} else {
    echo json_encode(['success' => true, 'is_registered' => false]);
}
?>