<?php
header('Content-Type: application/json');
require_once 'security.php';

try {
    $configPath = '/opt/panel/www/config/redis.php';
    $redisPass = '';
    
    // Safely parse the password without executing the file
    if (file_exists($configPath)) {
        $content = file_get_contents($configPath);
        if (preg_match("/define\('REDIS_PASS',\s*'([^']+)'\);/", $content, $matches)) {
            $redisPass = $matches[1];
        }
    }

    if (!$redisPass) {
        throw new Exception("Unable to read secure cache keys from SRE vault.");
    }

    echo json_encode([
        'success' => true,
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => $redisPass
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>