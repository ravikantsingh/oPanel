<?php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/Database.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$domain = trim($_POST['domain'] ?? '');
$action = trim($_POST['action'] ?? ''); // toggle_hotlink, add_redirect, del_redirect, add_mime, del_mime

if (!$domain || !$action) { echo json_encode(['success' => false, 'error' => 'Missing parameters.']); exit; }

try {
    $db = Database::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($action === 'toggle_hotlink') {
        $status = ($_POST['status'] == 'true') ? 1 : 0;
        $stmt = $db->prepare("UPDATE domains SET hotlink_protection = ? WHERE domain_name = ?");
        $stmt->execute([$status, $domain]);
    }
    elseif ($action === 'add_redirect') {
        $source = trim($_POST['source']);
        $target = trim($_POST['target']);
        $type = (int)$_POST['type'];
        $stmt = $db->prepare("INSERT INTO domain_redirects (domain_name, source_path, target_url, redirect_type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$domain, $source, $target, $type]);
    }
    elseif ($action === 'del_redirect') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM domain_redirects WHERE id = ? AND domain_name = ?");
        $stmt->execute([$id, $domain]);
    }
    elseif ($action === 'add_mime') {
        $ext = ltrim(trim($_POST['extension']), '.'); // Strip leading dot if user typed it
        $mime = trim($_POST['mime_type']);
        $stmt = $db->prepare("INSERT INTO domain_mimes (domain_name, extension, mime_type) VALUES (?, ?, ?)");
        $stmt->execute([$domain, $ext, $mime]);
    }
    elseif ($action === 'del_mime') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM domain_mimes WHERE id = ? AND domain_name = ?");
        $stmt->execute([$id, $domain]);
    }

    // Ping the Python Daemon to rebuild Nginx files
    $queue = new TaskQueue();
    $taskId = $queue->dispatch('adv_web_compile', ['domain' => $domain]);

    echo json_encode(['success' => true, 'message' => "Update queued (Task #$taskId)."]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>