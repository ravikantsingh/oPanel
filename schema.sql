-- schema.sql
-- Master Database Initialization for the oPanel

CREATE DATABASE IF NOT EXISTS panel_core;
USE panel_core;

-- ==========================================
-- 1. SYSTEM ADMINS (Panel Login)
-- ==========================================
CREATE TABLE IF NOT EXISTS `panel_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `username` varchar(50) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `totp_secret` varchar(32) DEFAULT NULL,
  `is_2fa_enabled` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert the default admin user (Password: admin123)
-- Users will be forced to change this upon first login!
INSERT IGNORE INTO `panel_admins` (`username`, `password_hash`) VALUES
('admin', '$2y$10$MELTj/VZ10rG.U6aoEF5tu1gwsk0kK99m/AF29Qf5NJuaEQhoN3KS');

-- ==========================================
-- 2. PANEL SETTINGS
-- ==========================================
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` varchar(50) NOT NULL PRIMARY KEY,
  `setting_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('panel_name', 'oPanel'),
('brand_title', 'oPanel'),
('brand_logo', ''), 
('brand_logo_url', '/index.php'),
('brand_favicon_ico', ''),
('brand_favicon_svg', ''),
('brand_theme_color', '#0d6efd'), 
('brand_sidebar_color', '#1e1e2f'),
('brand_login_bg_color', '#1e1e2f'),
('brand_login_bg_image', ''),
('brand_login_bg_fit', 'cover'),
('brand_login_subtext', 'Unified Server Management'),
('brand_hide_footer', '0');

-- ==========================================
-- 3. CLIENT USERS
-- ==========================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `username` varchar(32) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','client') DEFAULT 'client',
  `status` enum('active','suspended') DEFAULT 'active',
  `disk_quota_mb` int(11) DEFAULT 1024,
  `disk_quota` int(11) DEFAULT 1024,
  `ssh_pub_key` text DEFAULT NULL,
  `webhook_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 4. DOMAINS & WEB ENVIRONMENTS
-- ==========================================
CREATE TABLE IF NOT EXISTS `domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `domain_name` varchar(255) NOT NULL UNIQUE,
  `status` VARCHAR(20) DEFAULT 'active',
  `username` varchar(50) NOT NULL,
  `php_version` varchar(10) DEFAULT '8.3',
  `git_repo` varchar(255) DEFAULT 'Not Configured',
  `git_branch` varchar(100) DEFAULT 'main',
  `latest_commits` text DEFAULT NULL,
  `has_ssl` tinyint(1) DEFAULT 0,
  `force_https` tinyint(1) DEFAULT 0,
  `hsts_enabled` tinyint(1) DEFAULT 0,
  `waf_enabled` tinyint(1) DEFAULT 0,
  `waf_custom_rules` text DEFAULT NULL,
  `php_memory_limit` varchar(10) DEFAULT '128M',
  `php_max_exec_time` int(11) DEFAULT 30,
  `php_max_input_time` int(11) DEFAULT 60,
  `php_post_max_size` varchar(10) DEFAULT '8M',
  `php_upload_max_filesize` varchar(10) DEFAULT '2M',
  `php_opcache_enable` varchar(3) DEFAULT 'on',
  `php_disable_functions` text DEFAULT NULL,
  `php_include_path` text DEFAULT NULL,
  `php_session_save_path` text DEFAULT NULL,
  `php_mail_params` text DEFAULT NULL,
  `php_open_basedir` text DEFAULT NULL,
  `php_error_reporting` varchar(100) DEFAULT 'E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED',
  `php_display_errors` varchar(3) DEFAULT 'off',
  `php_log_errors` varchar(3) DEFAULT 'on',
  `php_allow_url_fopen` varchar(3) DEFAULT 'on',
  `php_file_uploads` varchar(3) DEFAULT 'on',
  `php_short_open_tag` varchar(3) DEFAULT 'off',
  `fpm_max_children` int(11) DEFAULT 12,
  `fpm_max_requests` int(11) DEFAULT 500,
  `fpm_pm` varchar(20) DEFAULT 'dynamic',
  `fpm_start_servers` int(11) DEFAULT 3,
  `fpm_min_spare_servers` int(11) DEFAULT 2,
  `fpm_max_spare_servers` int(11) DEFAULT 5,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 5. DATABASES
-- ==========================================
CREATE TABLE IF NOT EXISTS `databases` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `db_name` varchar(64) NOT NULL UNIQUE,
  `db_user` varchar(64) NOT NULL,
  `owner_username` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 6. FTP ACCOUNTS
-- ==========================================
CREATE TABLE IF NOT EXISTS `ftp_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `domain_name` varchar(255) NOT NULL,
  `ftp_user` varchar(100) NOT NULL UNIQUE,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 7. DNS ZONES & RECORDS
-- ==========================================
CREATE TABLE IF NOT EXISTS `dns_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `domain_name` varchar(255) NOT NULL,
  `record_name` varchar(255) NOT NULL,
  `record_type` varchar(10) NOT NULL,
  `record_value` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 8. FIREWALL RULES
-- ==========================================
CREATE TABLE IF NOT EXISTS `firewall_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `port` int(11) NOT NULL,
  `protocol` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  UNIQUE KEY `port` (`port`,`protocol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 9. CRON JOBS
-- ==========================================
CREATE TABLE IF NOT EXISTS `cron_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `username` varchar(50) NOT NULL,
  `minute` varchar(10) NOT NULL,
  `hour` varchar(10) NOT NULL,
  `day` varchar(10) NOT NULL,
  `month` varchar(10) NOT NULL,
  `weekday` varchar(10) NOT NULL,
  `command` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 10. PYTHON DAEMON TASK QUEUE
-- ==========================================
CREATE TABLE IF NOT EXISTS `tasks_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `action` varchar(50) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `output_log` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 11. AUTOMATIC BACKUP
-- ==========================================
CREATE TABLE IF NOT EXISTS backup_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    target VARCHAR(255) NOT NULL,
    backup_type ENUM('web', 'db') NOT NULL,
    frequency ENUM('daily', 'weekly', 'monthly') NOT NULL,
    run_hour INT DEFAULT 2, -- Default to 2 AM
    retention_days INT DEFAULT 3, -- How many automated backups to keep
    last_run DATETIME NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS mail_domains (
    name VARCHAR(255) NOT NULL PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Table for physical email accounts */
CREATE TABLE IF NOT EXISTS mail_users (
    email VARCHAR(255) NOT NULL PRIMARY KEY,
    domain VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    quota INT DEFAULT 1024,
    FOREIGN KEY (domain) REFERENCES mail_domains(name) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Table for email forwarders/aliases */
CREATE TABLE IF NOT EXISTS mail_aliases (
    source VARCHAR(255) NOT NULL PRIMARY KEY,
    domain VARCHAR(255) NOT NULL,
    destination TEXT NOT NULL,
    FOREIGN KEY (domain) REFERENCES mail_domains(name) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;