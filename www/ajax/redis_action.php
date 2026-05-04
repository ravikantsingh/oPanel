<?php
header('Content-Type: application/json');
require_once 'security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
$action = $_POST['action'] ?? '';

try {
    if ($action === 'flush') {
        require_once '../config/redis.php';
        $redis = new Redis();
        $redis->connect(REDIS_HOST, REDIS_PORT);
        $redis->auth(REDIS_PASS);
        $redis->flushAll();
        echo json_encode(['success' => true, 'message' => 'RAM Cache Flushed Successfully']);
    } 
    elseif ($action === 'restart') {
        // Uses the SRE Bridge to restart the daemon
        shell_exec("sudo /bin/systemctl restart redis-server 2>/dev/null");
        echo json_encode(['success' => true, 'message' => 'Redis Daemon Restarted']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>