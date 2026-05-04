<?php
header('Content-Type: application/json');
require_once 'security.php';

// Suppress warnings in case Redis is completely down
error_reporting(E_ERROR | E_PARSE);

try {
    require_once '../config/redis.php';
    $redis = new Redis();
    
    if (!$redis->connect(REDIS_HOST, REDIS_PORT, 2)) {
        throw new Exception("Offline");
    }
    
    $redis->auth(REDIS_PASS);
    $info = $redis->info();
    
    // Calculate Memory SRE Limit (We hardcoded 128MB in install.sh)
    $maxMemoryLimit = 128 * 1024 * 1024; // 128MB in bytes
    $usedMemory = $info['used_memory'];
    $memoryPercent = min(100, round(($usedMemory / $maxMemoryLimit) * 100));
    
    // Determine Color based on usage
    $memColor = 'success';
    if ($memoryPercent > 70) $memColor = 'warning';
    if ($memoryPercent > 90) $memColor = 'danger';

    // Calculate Cache Hit Rate
    $hits = $info['keyspace_hits'] ?? 0;
    $misses = $info['keyspace_misses'] ?? 0;
    $totalQueries = $hits + $misses;
    $hitRate = ($totalQueries > 0) ? round(($hits / $totalQueries) * 100) : 0;

    echo json_encode([
        'success' => true,
        'status' => 'Online',
        'uptime_days' => $info['uptime_in_days'],
        'clients' => $info['connected_clients'],
        'used_memory_human' => $info['used_memory_human'],
        'memory_percent' => $memoryPercent,
        'memory_color' => $memColor,
        'hit_rate' => $hitRate . '%'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'status' => 'Offline',
        'used_memory_human' => '0B',
        'memory_percent' => 0,
        'memory_color' => 'danger',
        'clients' => 0,
        'hit_rate' => '0%'
    ]);
}
?>