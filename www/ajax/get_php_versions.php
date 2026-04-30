<?php
// /opt/panel/www/ajax/get_php_versions.php
header('Content-Type: application/json');

// ---> REQUIRE THE GATEKEEPER <---
require_once 'security.php';
// --------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

try {
    $versions = [];
    
    // Scan the /etc/php directory for version folders (e.g., 8.1, 8.2, 8.3)
    $php_dirs = glob('/etc/php/*', GLOB_ONLYDIR);
    
    foreach ($php_dirs as $dir) {
        $version = basename($dir);
        // Verify that FPM is actually installed for this specific version
        if (is_dir("$dir/fpm")) {
            $versions[] = $version;
        }
    }
    
    // Sort descending so the highest PHP version is always at the top of the list
    rsort($versions);
    
    echo json_encode(['success' => true, 'versions' => $versions]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>