<?php
// /opt/panel/www/ajax/save_branding.php
header('Content-Type: application/json');
require_once 'security.php';
require_once '../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$db = Database::getInstance()->getConnection();
$upload_dir = '/opt/panel/www/assets/custom/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

// 1. Process Text & Color Inputs
$updates = [
    'brand_title' => filter_input(INPUT_POST, 'brand_title', FILTER_SANITIZE_STRING),
    'brand_subtext' => filter_input(INPUT_POST, 'brand_subtext', FILTER_SANITIZE_STRING),
    'brand_logo_url' => filter_input(INPUT_POST, 'brand_logo_url', FILTER_SANITIZE_URL),
    'brand_theme_color' => filter_input(INPUT_POST, 'brand_theme_color', FILTER_SANITIZE_STRING),
    'brand_sidebar_color' => filter_input(INPUT_POST, 'brand_sidebar_color', FILTER_SANITIZE_STRING),
    'brand_login_bg_color' => filter_input(INPUT_POST, 'brand_login_bg_color', FILTER_SANITIZE_STRING),
    'brand_login_bg_fit' => filter_input(INPUT_POST, 'brand_login_bg_fit', FILTER_SANITIZE_STRING),
    'brand_hide_footer' => isset($_POST['brand_hide_footer']) ? '1' : '0'
];

try {
    $db->beginTransaction();
    $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    
    foreach ($updates as $key => $val) {
        if ($val !== null) $stmt->execute([$val, $key]);
    }

    // 2. Strict File Upload Processing
    $files_map = [
        'brand_logo' => ['prefix' => 'logo', 'mimes' => ['image/png', 'image/jpeg', 'image/svg+xml']],
        'brand_login_bg_image' => ['prefix' => 'bg', 'mimes' => ['image/png', 'image/jpeg']],
        'brand_favicon_ico' => ['prefix' => 'fav_ico', 'mimes' => ['image/x-icon', 'image/vnd.microsoft.icon']],
        'brand_favicon_svg' => ['prefix' => 'fav_svg', 'mimes' => ['image/svg+xml']]
    ];

    foreach ($files_map as $input_name => $rules) {
        if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES[$input_name]['tmp_name'];
            $mime = mime_content_type($tmp);
            
            if (!in_array($mime, $rules['mimes'])) {
                throw new Exception("Invalid file type for $input_name. Detected: $mime");
            }

            // Map mime to safe extension
            $ext = '.jpg';
            if ($mime === 'image/png') $ext = '.png';
            if ($mime === 'image/svg+xml') $ext = '.svg';
            if (strpos($mime, 'icon') !== false) $ext = '.ico';

            $filename = $rules['prefix'] . '_' . time() . $ext;
            $dest = $upload_dir . $filename;

            if (move_uploaded_file($tmp, $dest)) {
                $stmt->execute(['/assets/custom/' . $filename, $input_name]);
            }
        }
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>