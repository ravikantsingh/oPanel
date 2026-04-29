<?php
// /opt/panel/www/ajax/upload_backup.php
header('Content-Type: application/json');
require_once 'security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$type = $_POST['type'] ?? ''; // 'Website' or 'Database'
$file = $_FILES['backup_file'] ?? null;

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Upload failed. File might be larger than PHP limits allows (check your PMA settings tab!).']);
    exit;
}

// Strict security: Only allow .gz files
$filename = basename($file['name']);
if (!preg_match('/\.gz$/', $filename)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only .tar.gz or .sql.gz allowed.']);
    exit;
}

// Force the manual prefix so the auto-deleter never touches it
if (strpos($filename, 'manual_') !== 0) {
    $filename = 'manual_uploaded_' . time() . '_' . $filename;
}

$targetDir = ($type === 'Website') ? '/opt/panel/backups/websites/' : '/opt/panel/backups/databases/';
$targetPath = $targetDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Ensure the web server user owns it so PHP can delete it later if requested,
    // and secure the permissions.
    chown($targetPath, 'www-data');
    chgrp($targetPath, 'www-data');
    chmod($targetPath, 0640);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to move file to the secure vault.']);
}
?>