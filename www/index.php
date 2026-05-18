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
    header("Location: /login");
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
<?php include 'views/components/title.php'; ?>

<?php //include 'views/components/stats-bar.php'; ?>

<?php include 'views/components/panel-tabs.php'; ?>

<?php 
$license_file = '/opt/panel/www/config/license_status.json';
$global_license_status = 'active';
if (file_exists($license_file)) {
    $ld = json_decode(file_get_contents($license_file), true);
    if (isset($ld['status']) && $ld['status'] !== 'active') {
        $global_license_status = $ld['status'];
    }
}
?>
<div class="alert alert-danger shadow-sm border-danger border-2 align-items-center mb-4 <?= $global_license_status === 'active' ? 'd-none' : 'd-flex' ?>" id="globalLicenseBanner">
    <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-danger"></i>
    <div>
        <h5 class="alert-heading fw-bold mb-1" id="bannerHeading">CRITICAL: Panel Locked (License <?= ucfirst($global_license_status) ?>)</h5>
        <p class="mb-2 small">Your Stackrium Control license has encountered an issue. System tasks have been paused. Please update your billing on Stackrium Central to unlock your server.</p>
        
        <hr class="border-danger opacity-25 my-2">
        
        <p class="mb-0 small fw-bold text-dark">
            <i class="bi bi-emoji-laughing-fill text-danger me-1"></i> HAHA! We're just kidding. Put your credit card away! 
            Stackrium is completely free to use. Your license will automatically renew for another year. Hope you enjoyed the mini heart attack! 😅
        </p>
    </div>
    <a href="https://stackrium.com" target="_blank" class="btn btn-danger fw-bold ms-auto text-nowrap shadow-sm">
        Auto-Renew for Free <i class="bi bi-arrow-repeat ms-1"></i>
    </a>
</div>

<div class="tab-content" id="panelTabsContent">
    <?php 
        include 'views/components/tab-overview.php'; 
        include 'views/components/tab-domains.php'; 
        include 'views/components/tab-security.php'; 
        include 'views/components/tab-users.php'; 
        include 'views/components/tab-redis.php';
        include 'views/components/tab-cron.php'; 
        include 'views/components/tab-backups.php'; 
        include 'views/components/tab-license.php'; 
        include 'views/components/tab-docs.php';
        include 'views/components/tab-support.php';
    ?>
</div>

<?php include 'views/components/modals.php'; ?>

<?php include 'views/footer.php'; ?>