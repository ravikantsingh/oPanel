<?php
// /opt/panel/cli/migrate.php
require_once '/opt/panel/www/config/database.php'; // Your DB connection

$db = new PDO("mysql:host=localhost;dbname=panel_core", DB_USER, DB_PASS);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get all .sql files in the migrations folder
$files = glob(__DIR__ . '/migrations/*.sql');
sort($files); // Run them in order (1.0.5, 1.0.6, etc.)

foreach ($files as $file) {
    $version = basename($file, '.sql');

    // Check if this version was already applied
    $stmt = $db->prepare("SELECT version FROM migrations WHERE version = ?");
    $stmt->execute([$version]);
    
    if (!$stmt->fetch()) {
        echo "Applying database migration for v$version...\n";
        
        // Read and execute the SQL file
        $sql = file_get_contents($file);
        $db->exec($sql);
        
        // Log it as completed so it never runs again!
        $db->prepare("INSERT INTO migrations (version) VALUES (?)")->execute([$version]);
    }
}
echo "All migrations up to date.\n";
?>