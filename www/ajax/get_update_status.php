<?php
// /opt/panel/www/ajax/get_update_status.php
require_once 'security.php'; // Ensures only logged-in admins can check the status
header('Content-Type: application/json');

$status_file = '/opt/panel/www/config/update_status.json';

if (file_exists($status_file)) {
    echo file_get_contents($status_file);
} else {
    // Fallback if the bash script hasn't created it yet
    echo json_encode(['progress' => 0, 'step' => 'Waiting for engine to start...', 'status' => 'starting']);
}
?>