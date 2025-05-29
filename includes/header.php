<?php
// Start the session if not already started (should be started by the calling script)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include config file
require_once __DIR__ . "/../config.php";

// Cek jika user tidak login, redirect ke halaman login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$current_page_base = basename($_SERVER['PHP_SELF']);
$current_folder = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? "Sistem Absensi Pegawai"); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #e9ecef; min-height: 100vh; display: flex; }
        .sidebar {
            background-color: #343a40; /* Dark background for sidebar */
            color: #ffffff;
            width: 250px;
            flex-shrink: 0; /* Prevent sidebar from shrinking */
            position: sticky;
            top: 0;
            height: 100vh; /* Full height */
            overflow-y: auto; /* Enable scrolling for long content */
            padding-top: 20px;
        }
        .sidebar .nav-link {
            color: #adb5bd; /* Light grey text */
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 5px;
        }
        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background-color: #495057; /* Slightly lighter dark for active/hover */
            color: #ffffff;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .main-content {
            flex-grow: 1; /* Allow main content to take remaining space */
            padding: 20px;
        }
        .sidebar-header {
            text-align: center;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #495057;
        }
        .sidebar-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }
        .sidebar-header h5 {
            color: #ffffff;
            margin-bottom: 5px;
        }
        .sidebar-user-info {
            font-size: 0.85em;
            color: #adb5bd;
        }
        /* Custom styles for card icons */
        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        /* Color themes for cards */
        .bg-primary-light { background-color: #cfe2ff !important; color: #084298 !important; }
        .bg-success-light { background-color: #d1e7dd !important; color: #0f5132 !important; }
        .bg-danger-light { background-color: #f8d7da !important; color: #842029 !important; }
        .bg-warning-light { background-color: #fff3cd !important; color: #664d03 !important; }
        .bg-info-light { background-color: #cff4fc !important; color: #055160 !important; }
        .bg-secondary-light { background-color: #e2e3e5 !important; color: #41464b !important; }
    </style>
</head>
<body>

    <div class="sidebar d-flex flex-column d-none d-md-flex" id="sidebarMenu">
        <div class="sidebar-header">
            <img src="https://new.deiyaikab.go.id/media/ckeditor/2022/08/05/pemkab-deiyai.png" alt="Logo">
            <h5>Sistem Absensi</h5>
            <p class="sidebar-user-info">
                <?php echo htmlspecialchars($_SESSION['hak_akses'] ?? 'Guest Role'); ?> - 
                <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Guest User'); ?> 
                <?php if (!empty($_SESSION['dinas'])): ?>
                    - <?php echo htmlspecialchars($_SESSION['dinas'] ?? 'No Dinas'); ?>
                <?php endif; ?>
            </p>
        </div>
        <ul class="nav flex-column mb-auto">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page_base === 'index.php' && $current_folder === 'admin') ? 'active' : ''; ?>" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            </li>
            <?php if (isset($_SESSION['hak_akses']) && in_array($_SESSION['hak_akses'], ['Superadmin', 'Admin Dinas'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page_base === 'manage_users.php') ? 'active' : ''; ?>" href="manage_users.php"><i class="fas fa-users"></i> Kelola Pegawai</a>
                </li>
            <?php endif; ?>
            <?php if (isset($_SESSION['hak_akses']) && in_array($_SESSION['hak_akses'], ['Superadmin', 'Admin Dinas', 'Bupati', 'Bendahara Dinas'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page_base === 'list_absensi.php') ? 'active' : ''; ?>" href="list_absensi.php"><i class="fas fa-clipboard-check"></i> Daftar Absensi</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page_base === 'list_izin.php') ? 'active' : ''; ?>" href="list_izin.php"><i class="fas fa-file-alt"></i> Pengajuan Izin/Sakit</a>
                </li>
            <?php endif; ?>
            <?php if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Superadmin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page_base === 'manage_settings.php') ? 'active' : ''; ?>" href="manage_settings.php"><i class="fas fa-cogs"></i> Pengaturan Sistem</a>
                </li>
            <?php endif; ?>
            <?php if (isset($_SESSION['hak_akses']) && in_array($_SESSION['hak_akses'], ['Superadmin', 'Bupati', 'Bendahara Dinas'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page_base === 'rekap_bulanan.php') ? 'active' : ''; ?>" href="rekap_bulanan.php"><i class="fas fa-chart-bar"></i> Rekapitulasi Bulanan</a>
                </li>
            <?php endif; ?>
             
            <li class="nav-item">
                <a class="nav-link" href="<?php echo ($_SESSION['hak_akses'] === 'Pegawai' ? '../logout.php' : '../../logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </div>

    <nav class="navbar navbar-dark bg-primary d-md-none w-100">
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
            <span class="navbar-toggler-icon"></span> Menu
        </button>
        <a class="navbar-brand" href="#"><?php echo htmlspecialchars($page_title ?? "Sistem Absensi Pegawai"); ?></a>
    </nav>

    <div class="main-content w-100">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>