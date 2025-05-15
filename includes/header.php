<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Digital Printing</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: bold;
            color: #007bff;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #343a40;
            color: white;
        }
        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            transition: all 0.3s;
        }
        .sidebar a:hover {
            color: white;
            background-color: #495057;
        }
        .sidebar a.active {
            color: white;
            background-color: #007bff;
        }
        .content {
            padding: 20px;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>index.php">
                <i class="fas fa-print me-2"></i>Digital Printing
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if(isset($_SESSION['user'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-2"></i><?php echo $_SESSION['user']['nama_lengkap']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if($_SESSION['user']['role'] == 'admin'): ?>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/master/cabang.php">Kelola Cabang</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>laporan/laporan.php">Laporan</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">Logout</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>login.php">Login</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <?php if(isset($_SESSION['user'])): ?>
            <div class="col-md-3 col-lg-2 p-0 sidebar">
                <div class="d-flex flex-column">
                    <?php if($_SESSION['user']['role'] == 'admin'): ?>
                    <a href="<?php echo BASE_URL; ?>index.php" class="<?php echo (basename($_SERVER['SCRIPT_FILENAME']) == 'index.php') ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/master/cabang.php" class="<?php echo (basename($_SERVER['SCRIPT_FILENAME']) == 'cabang.php') ? 'active' : ''; ?>">
                        <i class="fas fa-building me-2"></i>Cabang
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/master/mesin.php" class="<?php echo (basename($_SERVER['SCRIPT_FILENAME']) == 'mesin.php') ? 'active' : ''; ?>">
                        <i class="fas fa-print me-2"></i>Mesin
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/master/bahan.php" class="<?php echo (basename($_SERVER['SCRIPT_FILENAME']) == 'bahan.php') ? 'active' : ''; ?>">
                        <i class="fas fa-box me-2"></i>Bahan
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/master/jenis_cetak.php" class="<?php echo (basename($_SERVER['SCRIPT_FILENAME']) == 'jenis_cetak.php') ? 'active' : ''; ?>">
                        <i class="fas fa-list me-2"></i>Jenis Cetak
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/master/reject.php" class="<?php echo (basename($_SERVER['SCRIPT_FILENAME']) == 'reject.php') ? 'active' : ''; ?>">
                        <i class="fas fa-exclamation-triangle me-2"></i>Reject Mesin
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/master/users.php" class="<?php echo (basename($_SERVER['SCRIPT_FILENAME']) == 'users.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users me-2"></i>Pengguna
                    </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo BASE_URL; ?>modules/order/orders.php" class="<?php echo (in_array(basename($_SERVER['SCRIPT_FILENAME']), ['orders.php', 'order_add.php', 'order_edit.php', 'order_detail.php'])) ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart me-2"></i>Order
                    </a>
                    
                    <?php if($_SESSION['user']['role'] == 'admin'): ?>
                    <a href="<?php echo BASE_URL; ?>laporan/laporan.php" class="<?php echo (basename($_SERVER['SCRIPT_FILENAME']) == 'laporan.php') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar me-2"></i>Laporan
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-9 col-lg-10 content">
            <?php else: ?>
            <div class="col-12 content">
            <?php endif; ?>
