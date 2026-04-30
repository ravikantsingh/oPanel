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

// 1. Capture Pagination Parameters (Defaults: Page 1, 5 items per page)
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 5;
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;

// Safety bounds
if ($limit < 1 || $limit > 100) $limit = 5;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

try {
    $db = Database::getInstance()->getConnection();
    
    // 2. Get the Total Count for UI Math
    $countStmt = $db->query("SELECT COUNT(*) FROM tasks_queue");
    $totalTasks = $countStmt->fetchColumn();
    
    // 3. Fetch the specific slice of data using secure PDO bindings
    $stmt = $db->prepare("SELECT id, action, payload, status, created_at FROM tasks_queue ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Calculate total pages
    $totalPages = ceil($totalTasks / $limit);

    // Return the data AND the pagination metrics
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