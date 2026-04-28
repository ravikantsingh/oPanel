<?php
// /opt/panel/www/ajax/system_stats.php
header('Content-Type: application/json');

// 1. Get CPU Load (1-minute average)
$load = sys_getloadavg();
$cpu_load = round($load[0], 2);

// 2. Get RAM Usage (Reading Ubuntu's /proc/meminfo)
$free = shell_exec('free -m');
$free = (string)trim($free);
$free_arr = explode("\n", $free);
$mem = explode(" ", $free_arr[1]);
$mem = array_filter($mem);
$mem = array_merge($mem);

$total_ram = $mem[1];
$used_ram = $mem[2];
$ram_percent = round(($used_ram / $total_ram) * 100);

// 3. Get Disk Space (For the root partition "/")
$total_disk = disk_total_space("/");
$free_disk = disk_free_space("/");
$used_disk = $total_disk - $free_disk;
$disk_percent = round(($used_disk / $total_disk) * 100);

// Convert bytes to Gigabytes for clean display
$total_disk_gb = round($total_disk / 1073741824, 1);
$used_disk_gb = round($used_disk / 1073741824, 1);

echo json_encode([
    'cpu_load' => $cpu_load,
    'ram_percent' => $ram_percent,
    'ram_used' => $used_ram,
    'ram_total' => $total_ram,
    'disk_percent' => $disk_percent,
    'disk_used' => $used_disk_gb,
    'disk_total' => $total_disk_gb
]);