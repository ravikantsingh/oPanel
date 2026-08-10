<?php
// /opt/panel/www/ajax/setup_first_admin.php
header('Content-Type: application/json');

require_once '../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// 1. Sanitize all inputs from the Gatekeeper Modal
$name     = trim(filter_input(INPUT_POST, 'owner_name', FILTER_SANITIZE_STRING));
$email    = trim(filter_input(INPUT_POST, 'owner_email', FILTER_SANITIZE_EMAIL));
$company  = trim(filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING));
$country  = trim(filter_input(INPUT_POST, 'country', FILTER_SANITIZE_STRING));
$password = $_POST['new_password'] ?? '';

if (empty($name) || empty($email) || empty($country) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Please fill in all required fields.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters.']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Start a database transaction so everything saves together safely
    $db->beginTransaction();

    // 2. Hash the new secure password
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    // 3. TARGET THE CORRECT TABLE: Only update the auth columns!
    $stmtAdmin = $db->prepare("UPDATE panel_core.panel_admins SET password_hash = ? WHERE username = 'admin'");
    $stmtAdmin->execute([$hash]);

    // SAFETY NET: If the 'admin' user doesn't exist in panel_admins yet, insert it!
    if ($stmtAdmin->rowCount() === 0) {
        // Notice we do NOT include the email column here anymore
        $stmtInsert = $db->prepare("INSERT INTO panel_core.panel_admins (username, password_hash, is_2fa_enabled) VALUES ('admin', ?, 0)");
        $stmtInsert->execute([$hash]);
    }

    // 4. Save Profile Data and Flag Setup as Complete to the SETTINGS table
    $settings = [
        'owner_name'      => $name,
        'owner_email'     => $email,
        'owner_company'   => $company,
        'owner_country'   => $country,
        'setup_completed' => '1'
    ];

    $stmtSet = $db->prepare("INSERT INTO panel_core.settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    
    foreach ($settings as $key => $value) {
        $stmtSet->execute([$key, $value]);
    }

    // Commit the transaction
    $db->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
}