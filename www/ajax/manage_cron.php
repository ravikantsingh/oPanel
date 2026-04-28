<?php
header('Content-Type: application/json');
// ---> REQUIRE THE GATEKEEPER FIRST <---
require_once 'security.php';
// --------------------------------------

require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$action   = $_POST['action'] ?? ''; // 'add' or 'delete'
$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$minute   = $_POST['minute'] ?? '*';
$hour     = $_POST['hour'] ?? '*';
$day      = $_POST['day'] ?? '*';
$month    = $_POST['month'] ?? '*';
$weekday  = $_POST['weekday'] ?? '*';
$command  = $_POST['command'] ?? '';

if (empty($action) || empty($username) || empty($command)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit;
}

try {
    $queue = new TaskQueue();
    // Dispatch to a new queue task name that we will link to the cron script
    $queue->dispatch('manage_cron', [
        'sub_action' => $action,
        'username'   => $username,
        'minute'     => $minute,
        'hour'       => $hour,
        'day'        => $day,
        'month'      => $month,
        'weekday'    => $weekday,
        'command'    => $command
    ]);

    echo json_encode(['success' => true, 'message' => "Cron task queued for execution."]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}