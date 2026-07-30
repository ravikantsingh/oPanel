<?php
// /opt/panel/www/ajax/get_fm_sso.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$domain = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL);

if (empty($domain)) {
    echo json_encode(['success' => false, 'error' => 'Missing domain.']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT d.username, d.has_ssl, u.webhook_token FROM panel_core.domains d JOIN panel_core.users u ON d.username = u.username WHERE d.domain_name = ?");
    $stmt->execute([$domain]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) throw new Exception("Domain not found.");

    $timestamp = time();
    $secret = $data['webhook_token'];
    $hash = hash_hmac('sha256', $domain . '|' . $timestamp, $secret);

    // STACKRIUM SMART ROUTING: Check if domain is pointing to this server
    $server_ip = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
    $domain_ip = gethostbyname($domain);

    if ($domain_ip === $server_ip) {
        // LIVE DOMAIN -> Use Native Route (No permission conflicts)
        $protocol = $data['has_ssl'] ? 'https://' : 'http://';
        $url = $protocol . $domain . '/filemanager/index.php?sso_t=' . $timestamp . '&sso_h=' . $hash;
    } else {
        // FAKE/UNPOINTED DOMAIN -> Use IP Fallback Route
        $panel_host = preg_replace('/:[0-9]+$/', '', $_SERVER['HTTP_HOST']); 
        $url = 'http://' . $panel_host . '/~' . $domain . '/filemanager/index.php?sso_t=' . $timestamp . '&sso_h=' . $hash;
    }

    echo json_encode(['success' => true, 'url' => $url]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}