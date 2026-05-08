<?php
// /opt/panel/www/classes/Branding.php
require_once __DIR__ . '/Database.php';

class Branding {
    public static function getSettings() {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'brand_%'");
            
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            // Safe fallbacks in case the database is missing keys
            return [
                'title' => $settings['brand_title'] ?: 'oPanel',
                'subtext' => $settings['brand_subtext'] ?: 'Unified Server Management',
                'logo' => $settings['brand_logo'] ?: '',
                'logo_url' => $settings['brand_logo_url'] ?: '/index.php',
                'favicon_ico' => $settings['brand_favicon_ico'] ?: '',
                'favicon_svg' => $settings['brand_favicon_svg'] ?: '',
                'theme_color' => $settings['brand_theme_color'] ?: '#0d6efd',
                'sidebar_color' => $settings['brand_sidebar_color'] ?: '#1e1e2f',
                'login_bg_color' => $settings['brand_login_bg_color'] ?: '#1e1e2f',
                'login_bg_image' => $settings['brand_login_bg_image'] ?: '',
                'login_bg_fit' => $settings['brand_login_bg_fit'] ?: 'cover',
                'hide_footer' => $settings['brand_hide_footer'] == '1' ? true : false
            ];
        } catch (Exception $e) {
            return []; // Fail gracefully
        }
    }
}
?>