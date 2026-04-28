<?php
// /opt/panel/www/views/header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>Server Control Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; }
        .sidebar { min-height: 100vh; background-color: #212529; color: white; padding-top: 20px;}
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 10px 20px; display: block; }
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: #343a40; }
        .main-content { padding: 30px; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <h4 class="text-center mb-4"><i class="bi bi-server"></i> MyPanel</h4>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="bi bi-people"></i> Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="bi bi-globe"></i> Domains</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/filemanager/" target="_blank"><i class="bi bi-folder2-open"></i> File Manager <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 0.75rem;"></i></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="bi bi-database"></i> Databases</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/pma/" target="_blank"><i class="bi bi-table"></i> phpMyAdmin <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 0.75rem;"></i></a>
                </li>
            </ul>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 main-content">