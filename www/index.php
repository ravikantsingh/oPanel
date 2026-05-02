<?php
// /opt/panel/www/index.php
// ---> NEW: FORCE SECURE COOKIES <---
session_name('PANEL_SESSION');
session_set_cookie_params([
    'secure' => true,      // Only transmit over HTTPS
    'httponly' => true,    // Block Javascript from reading the cookie
    'samesite' => 'Strict' // Prevent Cross-Site Request Forgery
]);
// -----------------------------------
session_start();

// 1. Existing Login Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: /login.php");
    exit;
}

// 2. CSRF Token Generation (NEW)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$load = sys_getloadavg();
$cpu_load = $load[0];

include 'views/header.php';
?>
<!-- 1. Title Dashboard -->
<?php include 'views/components/title.php'; ?>

<!-- 2. Stats Bar (Extracted) -->
<?php include 'views/components/stats-bar.php'; ?>

<!-- 3. Panel Tabs -->
<?php include 'views/components/panel-tabs.php'; ?>

<!-- 4. Tab Content Loader -->
<div class="tab-content" id="panelTabsContent">
    <?php 
        include 'views/components/tab-overview.php'; 
        include 'views/components/tab-domains.php'; 
        include 'views/components/tab-security.php'; 
        include 'views/components/tab-users.php'; 
        include 'views/components/tab-cron.php'; 
        include 'views/components/tab-backups.php'; 
        include 'views/components/tab-docs.php';
    ?>
</div>

<!-- 5. Global Modals -->
<?php include 'views/components/modals.php'; ?>

<?php include 'views/footer.php'; ?>