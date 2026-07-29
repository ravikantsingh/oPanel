<?php
// /opt/panel/scripts/auto_update.php
// This script is designed to be run by a root cron job at 3:00 AM

require_once '/opt/panel/www/version.php';

// 1. Check if the user even WANTS auto-updates
$settings_file = '/opt/panel/www/config/settings.json';
$settings = file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];

if (empty($settings['auto_update_stable']) || $settings['auto_update_stable'] == false) {
    echo "Auto-updates are disabled by the user.\n";
    exit;
}

// 2. Get the local License Key
$license_key = trim(file_get_contents('/opt/panel/license.key'));

// 3. Ping Stackrium Central (Telling it this is an AUTOMATED request)
$ch = curl_init('https://stackrium.com/api/updates.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'license_key' => $license_key,
    'is_auto' => 'true' // THIS triggers your staggered rollout logic!
]));
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (!$data || !$data['success']) {
    echo "Failed to fetch update data.\n";
    exit;
}

// 4. The Version Comparison
$current_version = PANEL_VERSION;
$latest_stable = $data['stable']['version'];

// version_compare() is a built-in PHP function that safely compares semantic versions (e.g., 1.0.4 vs 1.0.5)
if (version_compare($current_version, $latest_stable, '<')) {
    
    // We need an update! But wait, check the Stagger Status first.
    if ($data['stagger_status'] === 'delayed') {
        echo "Update available ($latest_stable), but staggered rollout delayed until tomorrow.\n";
        exit;
    }

    echo "Update required. Upgrading from $current_version to $latest_stable...\n";
    
    // 5. Trigger the Bash Engine!
    $download_url = $data['stable']['url'];
    $cmd = "sudo /bin/bash /opt/panel/scripts/updater.sh " . escapeshellarg($download_url) . " stable > /opt/panel/logs/cron_update.log 2>&1 &";
    exec($cmd);
    
    echo "Update Engine launched in background.\n";

} else {
    echo "System is already up-to-date (Version $current_version).\n";
}
?>