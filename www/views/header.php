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
        .main-content { padding: 30px; }
        
        /* --- Stackrium Premium SaaS UI Overrides --- */
        .card {
            border: none !important;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .modal-header {
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background-color: #f8f9fa !important;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }
        .table-light th {
            background-color: #f8f9fa;
            border-bottom: none;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding-top: 1rem;
            padding-bottom: 1rem;
        }
        .table-hover tbody tr { transition: background-color 0.2s ease; }
        .btn {
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.2px;
            transition: all 0.2s;
        }
        .btn:active { transform: scale(0.97); }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 0.6rem 1rem;
            box-shadow: none !important;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.1) !important;
        }
        
        /* --- Sidebar Polish --- */
        .sidebar { 
            min-height: 100vh; 
            background-color: <?= $brand['sidebar_color'] ?>; 
            padding-top: 20px;
            box-shadow: 4px 0 24px rgba(0,0,0,0.02);
            z-index: 10;
        }
        .sidebar a { 
            color: #8b8b9e; 
            text-decoration: none; 
            padding: 12px 20px; 
            display: block; 
            border-left: 3px solid transparent; 
            transition: all 0.2s; 
            border-radius: 8px;
            margin: 0 10px;
        }
        .sidebar a:hover { color: #fff; background-color: rgba(255,255,255,0.05); }
        .sidebar a.active { color: #fff; background-color: rgba(13, 110, 253, 0.1); }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-flex flex-column sidebar collapse">
            <a href="<?= htmlspecialchars($brand['logo_url']) ?>" class="text-center d-block mb-4 text-decoration-none">
                <?php if (!empty($brand['logo'])): ?>
                    <img src="<?= $brand['logo'] ?>" alt="Logo" style="max-height: 40px; max-width: 80%;">
                <?php else: ?>
                    <h4 class="text-white"><i class="bi bi-hexagon-fill text-primary"></i> <?= htmlspecialchars($brand['title']) ?></h4>
                <?php endif; ?>
            </a>
            
            <ul class="nav flex-column mb-auto" id="sidebarNav" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview" href="#overview" role="tab"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" data-bs-target="#domains" href="#domains" role="tab"><i class="bi bi-globe me-2"></i> Web & Domains</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" data-bs-target="#users" href="#users" role="tab"><i class="bi bi-people me-2"></i> Users & DBs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" data-bs-target="#security" href="#security" role="tab"><i class="bi bi-shield-check me-2"></i> Security & DNS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" data-bs-target="#redis" href="#redis" role="tab"><i class="bi bi-lightning-charge me-2"></i> Performance</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" data-bs-target="#cron" href="#cron" role="tab"><i class="bi bi-clock-history me-2"></i> Cron Jobs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" data-bs-target="#backups" href="#backups" role="tab"><i class="bi bi-archive me-2"></i> Backups</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" data-bs-target="#license-updates" href="#license-updates" role="tab"><i class="bi bi-arrow-repeat me-2"></i> Updates</a>
                </li>
                <li class="nav-item mt-4 border-top border-secondary pt-3">
                    <a class="nav-link text-info" data-bs-toggle="tab" data-bs-target="#docs" href="#docs" role="tab"><i class="bi bi-journal-text me-2"></i> User Manual</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-success" id="support-tab" data-bs-toggle="tab" data-bs-target="#support" href="#support" role="tab"><i class="bi bi-life-preserver me-2"></i> Support Desk</a>
                </li>
            </ul>

            <hr class="border-secondary mt-4 mb-3">
            <div class="dropdown px-3 mb-4">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="adminMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-4 me-2 text-primary"></i>
                    <strong>Administrator</strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark shadow" aria-labelledby="adminMenu">
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#adminProfileModal"><i class="bi bi-key me-2"></i> Change Password</a></li>
                    <li><hr class="dropdown-divider border-secondary"></li>
                    <li><a class="dropdown-item text-danger" href="/logout"><i class="bi bi-box-arrow-left me-2"></i> Sign out</a></li>
                </ul>
            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 main-content">