<?php
// /opt/panel/www/views/header.php
require_once __DIR__ . '/../classes/Branding.php';
$brand = Branding::getSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    
    <title><?= htmlspecialchars($brand['title']) ?> | Unified Management</title>
    
    <?php if (!empty($brand['favicon_svg'])): ?>
        <link rel="icon" type="image/svg+xml" href="<?= $brand['favicon_svg'] ?>">
    <?php endif; ?>
    <?php if (!empty($brand['favicon_ico'])): ?>
        <link rel="alternate icon" href="<?= $brand['favicon_ico'] ?>">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --bs-primary: <?= $brand['theme_color'] ?>;
            --bs-primary-rgb: <?= implode(',', sscanf($brand['theme_color'], "#%02x%02x%02x")) ?>;
        }
        body { background-color: #f4f6f9; }
        .sidebar { min-height: 100vh; background-color: <?= $brand['sidebar_color'] ?>; padding-top: 20px;}
        .sidebar a { color: #8b8b9e; text-decoration: none; padding: 12px 20px; display: block; border-left: 3px solid transparent; transition: all 0.2s; }
        .sidebar a:hover { color: #fff; background-color: rgba(255,255,255,0.05); }
        .sidebar a.active { color: #fff; background-color: rgba(13, 110, 253, 0.1); border-left: 3px solid #0d6efd; }
        .main-content { padding: 30px; }
        .nav-tabs .nav-link { color: #6c757d; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Make Sidebar a flex-column to push the admin menu to the bottom -->
        <nav class="col-md-3 col-lg-2 d-md-flex flex-column sidebar collapse">
            <a href="<?= htmlspecialchars($brand['logo_url']) ?>" class="text-center d-block mb-4 text-decoration-none">
                <?php if (!empty($brand['logo'])): ?>
                    <img src="<?= $brand['logo'] ?>" alt="Logo" style="max-height: 40px; max-width: 80%;">
                <?php else: ?>
                    <h4 class="text-white"><i class="bi bi-hexagon-fill text-primary"></i> <?= htmlspecialchars($brand['title']) ?></h4>
                <?php endif; ?>
            </a>
            <ul class="nav flex-column mb-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="#" onclick="$('#overview-tab').tab('show'); $('.sidebar a').removeClass('active'); $(this).addClass('active');"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="$('#domains-tab').tab('show'); $('.sidebar a').removeClass('active'); $(this).addClass('active');"><i class="bi bi-globe me-2"></i> Web & Domains</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="$('#users-tab').tab('show'); $('.sidebar a').removeClass('active'); $(this).addClass('active');"><i class="bi bi-people me-2"></i> Users & DBs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="$('#security-tab').tab('show'); $('.sidebar a').removeClass('active'); $(this).addClass('active');"><i class="bi bi-shield-check me-2"></i> Security</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="$('#cron-tab').tab('show'); $('.sidebar a').removeClass('active'); $(this).addClass('active');"><i class="bi bi-clock-history me-2"></i> Cron Jobs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="$('#backups-tab').tab('show'); $('.sidebar a').removeClass('active'); $(this).addClass('active');"><i class="bi bi-archive me-2"></i> Backups</a>
                </li>
                <li class="nav-item mt-4 border-top border-secondary pt-3">
                    <a class="nav-link text-info" href="#" onclick="$('#docs-tab').tab('show'); $('.sidebar a').removeClass('active'); $(this).addClass('active');"><i class="bi bi-journal-text me-2"></i> User Manual</a>
                </li>
            </ul>

            <!-- Admin Profile Bottom Menu -->
            <hr class="border-secondary mt-4 mb-3">
            <div class="dropdown px-3 mb-4">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="adminMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-4 me-2 text-primary"></i>
                    <strong>Administrator</strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark shadow" aria-labelledby="adminMenu">
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#adminProfileModal"><i class="bi bi-key me-2"></i> Change Password</a></li>
                    <li><hr class="dropdown-divider border-secondary"></li>
                    <li><a class="dropdown-item text-danger" href="/logout.php"><i class="bi bi-box-arrow-left me-2"></i> Sign out</a></li>
                </ul>
            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 main-content">