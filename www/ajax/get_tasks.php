<?php
// /opt/panel/www/ajax/get_tasks.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/Database.php';

// STRICT POST CHECK
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 5;
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;

if ($limit < 1 || $limit > 100) $limit = 5;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

try {
    $db = Database::getInstance()->getConnection();
    
    $countStmt = $db->query("SELECT COUNT(*) FROM `tasks_queue`");
    $totalTasks = $countStmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT `id`, `action`, `payload`, `status`, `created_at` FROM `tasks_queue` ORDER BY `id` DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pre-process the JSON payload to extract a human-readable target
    foreach ($tasks as &$task) {
        $task['target_name'] = 'Global Server'; // Default fallback
        
        if (!empty($task['payload'])) {
            $payloadObj = json_decode($task['payload'], true);
            if (is_array($payloadObj)) {
                if (!empty($payloadObj['domain'])) {
                    $task['target_name'] = $payloadObj['domain'];
                } elseif (!empty($payloadObj['db_name'])) {
                    $task['target_name'] = 'Database: ' . $payloadObj['db_name'];
                } elseif (!empty($payloadObj['username'])) {
                    $task['target_name'] = 'User: ' . $payloadObj['username'];
                } elseif (!empty($payloadObj['port'])) {
                    $task['target_name'] = 'Port: ' . $payloadObj['port'];
                }
            }
        }
    }
    
    $totalPages = ceil($totalTasks / $limit);

    echo json_encode([
        'success' => true, 
        'tasks' => $tasks,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_tasks' => $totalTasks,
            'limit' => $limit
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>