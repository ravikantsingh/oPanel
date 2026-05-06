<?php
// /opt/panel/www/ajax/toggle_master_waf.php
header('Content-Type: application/json');
require_once 'security.php'; // handles CSRF and Session checks

$status = $_POST['status'] ?? '';

if (empty($status) || !in_array($status, ['on', 'off'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid status provided.']); 
    exit;
}

try {
    // Execute the bash script securely using escaped arguments
    $command = "sudo /opt/panel/scripts/toggle_master_waf.sh " . escapeshellarg($status);
    exec($command, $output, $return_var);

    if ($return_var === 0) {
        echo json_encode(['success' => true]);
    } else {
        // Throw an exception to route to your standardized error output
        throw new Exception('Bash script execution failed. ' . implode(" ", $output));
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}
?>