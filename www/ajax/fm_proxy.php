<?php
// /opt/panel/www/ajax/fm_proxy.php
// Stackrium Master Fallback Routing Gateway

$domain = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $_GET['domain'] ?? '');
$path   = preg_replace('/[^a-zA-Z0-9\-\.\/\_\?=&]/', '', $_GET['path'] ?? '');

if (empty($domain) || empty($path)) {
    http_response_code(404);
    die("Invalid routing request.");
}

require_once '../classes/Database.php';
try {
    $db = Database::getInstance()->getConnection();
    // Fetch the webhook_token for SSO cryptographic verification
    $stmt = $db->prepare("SELECT d.username, u.webhook_token FROM panel_core.domains d JOIN panel_core.users u ON d.username = u.username WHERE d.domain_name = ?");
    $stmt->execute([$domain]);
    $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tenant) {
        http_response_code(404);
        die("Domain not registered on this server.");
    }
    
    $user = $tenant['username'];
    $secret = $tenant['webhook_token'];
    
} catch (Exception $e) {
    http_response_code(500);
    die("Routing database error.");
}

$physical_path = "/home/{$user}/web/{$domain}/{$path}";
$clean_path = explode('?', $physical_path)[0];

if (!file_exists($clean_path)) {
    http_response_code(404);
    die("File not found.");
}

$extension = strtolower(pathinfo($clean_path, PATHINFO_EXTENSION));

if ($extension === 'php') {
    
    // --- STACKRIUM PROXY SSO INJECTION ---
    if (isset($_GET['sso_t']) && isset($_GET['sso_h'])) {
        $expected = hash_hmac('sha256', $domain . '|' . $_GET['sso_t'], $secret);
        
        if (hash_equals($expected, $_GET['sso_h']) && (time() - $_GET['sso_t'] < 60)) {
            // Token is valid! Force the session state for Tiny File Manager
            if (session_status() === PHP_SESSION_NONE) {
                session_name('filemanager');
                session_start();
            }
            $_SESSION['filemanager']['logged'] = $user;
            
            // Clean the tokens from the URL bar so they aren't visible
            $clean_uri = strtok($_SERVER["REQUEST_URI"], '?');
            header("Location: " . $clean_uri);
            exit;
        }
    }
    // -------------------------------------

    $_SERVER['SCRIPT_FILENAME'] = $clean_path;
    $_SERVER['SCRIPT_NAME'] = "/~{$domain}/{$path}";
    $_SERVER['PHP_SELF'] = "/~{$domain}/{$path}";
    $_SERVER['DOCUMENT_ROOT'] = "/home/{$user}/web/{$domain}/public_html";
    
    chdir(dirname($clean_path));
    require_once $clean_path;
} else {
    $mime_types = [
        'css' => 'text/css', 'js'  => 'application/javascript',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'svg' => 'image/svg+xml'
    ];
    $mime = $mime_types[$extension] ?? 'text/plain';
    
    header("Content-Type: {$mime}");
    readfile($clean_path);
}