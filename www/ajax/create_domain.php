<?php
// /opt/panel/www/ajax/create_domain.php
header('Content-Type: application/json');

// ---> REQUIRE THE GATEKEEPER FIRST <---\
require_once 'security.php';
// --------------------------------------

require_once '../classes/Database.php';
require_once '../classes/TaskQueue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// 1. Sanitize Core Inputs (PHP 8.3 Native Approach)
$username = trim(strip_tags($_POST['username'] ?? ''));
$php_version = trim(strip_tags($_POST['php_version'] ?? '8.3'));

// 2. Subdomain Logic Routing
$is_subdomain = isset($_POST['is_subdomain']) && ($_POST['is_subdomain'] === 'true' || $_POST['is_subdomain'] === '1');

if ($is_subdomain) {
    // Extract prefix and parent domain using native string cleaning
    $prefix = strtolower(trim(strip_tags($_POST['prefix'] ?? '')));
    $parent_domain = strtolower(trim(strip_tags($_POST['parent_domain'] ?? '')));
    
    if (empty($prefix) || empty($parent_domain)) {
        echo json_encode(['success' => false, 'error' => 'Subdomain prefix and parent domain are required.']);
        exit;
    }
    
    // Construct the absolute domain
    $domain = $prefix . '.' . $parent_domain;

    // SRE SECURITY: Verify the user actually owns the parent domain!
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id FROM domains WHERE domain_name = ? AND username = ?");
    $stmt->execute([$parent_domain, $username]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Security Violation: You do not own the parent domain.']);
        exit;
    }
} else {
    // Primary Domain Path
    $domain = strtolower(trim(strip_tags($_POST['domain'] ?? '')));
    $parent_domain = null;
    $prefix = null;
}

// 3. Strict Validation (Source of Truth for formatting)
if (!preg_match('/^(?!\-)(?:(?:[a-zA-Z\d][a-zA-Z\d\-]{0,61})?[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/', $domain)) {
    echo json_encode(['success' => false, 'error' => 'Invalid domain name format.']);
    exit;
}

if (!preg_match('/^[a-z0-9]+$/', $username)) {
    echo json_encode(['success' => false, 'error' => 'Invalid username format.']);
    exit;
}

// 4. Dispatch to the Python/Bash Worker
try {
    $queue = new TaskQueue();
    
    $payload = [
        'sub_action'    => 'create',
        'domain'        => $domain,
        'username'      => $username,
        'php_version'   => $php_version,
        'is_subdomain'  => $is_subdomain ? "true" : "false",
        'parent_domain' => $parent_domain,
        'prefix'        => $prefix
    ];

    $taskId = $queue->dispatch('create_vhost', $payload);

    echo json_encode([
        'success' => true, 
        'message' => "Environment for $domain queued for provisioning! (Task ID: $taskId)"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>