<?php
// /opt/panel/www/ajax/get_pma_sso.php
header('Content-Type: application/json');
require_once 'security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$target_db = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['db'] ?? '');

if (empty($target_db)) {
    echo json_encode(['success' => false, 'error' => 'Missing database name.']);
    exit;
}

try {
    // Generate a secure temporary MySQL user for absolute native isolation
    $tmp_user = 'sso_' . substr(md5(session_id() . $target_db . time()), 0, 8);
    $tmp_pass = bin2hex(random_bytes(16));

    // Connect to MySQL using the Master Admin Credentials to spawn the temp user
    $pdo = new PDO('mysql:host=localhost', 'pma_sso', 'PmaMasterKey998877');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create user with strict privileges exclusively to the requested database
    $pdo->exec("CREATE USER IF NOT EXISTS '$tmp_user'@'localhost' IDENTIFIED BY '$tmp_pass'");
    $pdo->exec("GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, CREATE TEMPORARY TABLES, LOCK TABLES ON `$target_db`.* TO '$tmp_user'@'localhost'");
    $pdo->exec("FLUSH PRIVILEGES");

    // Initialize the enterprise Signon Session namespace
    session_name('SignonSession');
    
    // Set a strict cookie path so phpMyAdmin can read it natively
    session_set_cookie_params(['path' => '/pma/', 'samesite' => 'Lax']);
    session_start();
    
    // Store the credentials securely in RAM
    $_SESSION['PMA_single_signon_user'] = $tmp_user;
    $_SESSION['PMA_single_signon_password'] = $tmp_pass;
    $_SESSION['PMA_single_signon_host'] = 'localhost';
    session_write_close();

    // Hand the trampoline URL back to the Javascript
    echo json_encode(['success' => true, 'url' => '/pma/signon.php']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>