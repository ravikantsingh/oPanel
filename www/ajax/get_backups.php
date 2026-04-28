<?php
header('Content-Type: application/json');
require_once 'security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$backups = [];

// Helper to read directories safely
function scanVault($dir, $type) {
    global $backups;
    
    // Safety check to prevent PHP Fatal Errors if permissions are wrong
    if (!is_dir($dir) || !is_readable($dir)) return;
    
    $scanned = scandir($dir);
    if ($scanned === false) return; // Abort if directory is locked
    
    $files = array_diff($scanned, ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        
        // Skip if we don't have permission to read the file
        if (!is_file($path) || !is_readable($path)) continue;
        
        $size = round(filesize($path) / 1048576, 2) . ' MB'; 
        
        // Extract target and timestamp safely
        if (preg_match('/^(.*)_([0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}-[0-9]{2}-[0-9]{2})/', $file, $matches)) {
            $target = $matches[1];
            $time = str_replace('_', ' ', $matches[2]);
        } else {
            $target = 'Unknown';
            $time = 'Unknown';
        }

        $backups[] = [
            'type' => $type,
            'target' => $target,
            'filename' => $file,
            'time' => $time,
            'size' => $size
        ];
    }
}

scanVault('/opt/panel/backups/websites', 'Website');
scanVault('/opt/panel/backups/databases', 'Database');

// Sort newest first
usort($backups, function($a, $b) { return strcmp($b['time'], $a['time']); });

echo json_encode(['success' => true, 'backups' => $backups]);