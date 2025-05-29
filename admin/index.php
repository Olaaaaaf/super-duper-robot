<?php
$page_title = "Dashboard Admin"; // Variabel ini akan digunakan di header.php
require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/functions.php"; // Memastikan functions.php dimuat
require_once __DIR__ . "/../config.php"; // Memastikan $mysqli tersedia jika functions.php membutuhkannya

// Aktifkan error reporting untuk debugging (opsional, hapus di production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Pastikan user yang login memiliki hak akses admin
if (!isset($_SESSION["hak_akses"]) || !in_array($_SESSION["hak_akses"], ['Superadmin', 'Admin Dinas', 'Bendahara Dinas', 'Bupati'])) {
    header("location: ../index.php");
    exit;
}

$hak_akses_user = $_SESSION["hak_akses"];
// Gunakan nama_pegawai dari sesi, karena sudah diambil dari tabel pegawai
$nama_user = htmlspecialchars($_SESSION["nama_pegawai"] ?? 'Pengguna'); // Fallback jika tidak ada
// NIP tidak selalu ada di sesi untuk semua tipe user (misal Superadmin mungkin tidak punya NIP)
$nip_user = htmlspecialchars($_SESSION["nip"] ?? '-');
// Nama dinas terkait, penting untuk Admin Dinas dan Bendahara Dinas
$nama_dinas_user = htmlspecialchars($_SESSION["nama_dinas_terkait"] ?? '-');
$id_dinas_user = $_SESSION["id_dinas_terkait"] ?? null;


// Set dashboard title based on access level
$dashboard_title = "Dashboard " . $hak_akses_user;

// Anda bisa menambahkan pengambilan data statistik di sini jika diperlukan
// Contoh: total pegawai, total dinas, absensi hari ini, dll.
// Namun, untuk sekarang kita fokus pada struktur dashboard.

?>
 
<div class="container-fluid py-4">
    <h2 class="mb-4 text-center text-primary"><?php echo $dashboard_title; ?></h2>
    <p class="lead text-center">Selamat datang, <b class="text-dark"><?php echo $nama_user; ?></b>. Ini adalah ringkasan tugas dan informasi penting Anda.</p>
    <?php if ($hak_akses_user === 'Admin Dinas' || $hak_akses_user === 'Bendahara Dinas'): ?>
        <p class="lead text-center">Anda adalah <?php echo $hak_akses_user; ?> dari Dinas <b class="text-info"><?php echo $nama_dinas_user; ?></b>.</p>
    <?php endif; ?>


    <?php if (in_array($hak_akses_user, ['Superadmin', 'Admin Dinas'])): // Superadmin & Admin Dinas untuk Manage Pegawai Honorer ?>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 shadow-sm border-warning">
                <div class="card-body text-center">
                    <div class="icon-circle bg-warning text-white mb-3 mx-auto">
                        <i class="fas fa-user-friends fa-2x"></i>
                    </div>
                    <h5 class="card-title text-warning">Manajemen Pegawai Honorer</h5>
                    <p class="card-text fs-5">Tambah dan lihat data pegawai honorer.</p>
                    <a href="manage_pegawai_honorer.php" class="btn btn-outline-warning btn-lg mt-3">Kelola Honorer <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
            </div>
        </div>
    <?php endif; ?>


    <div class="row mt-5 justify-content-center">

        <?php if (in_array($hak_akses_user, ['Superadmin', 'Admin Dinas'])): // Akses Absensi dan Izin ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm border-info">
                    <div class="card-body text-center">
                        <div class="icon-circle bg-info text-white mb-3 mx-auto">
                            <i class="fas fa-calendar-check fa-2x"></i>
                        </div>
                        <h5 class="card-title text-info">Daftar Absensi</h5>
                        <p class="card-text fs-5">Lihat dan kelola catatan absensi semua pegawai.</p>
                        <a href="list_absensi.php" class="btn btn-outline-info btn-lg mt-3">Lihat Absensi <i class="fas fa-arrow-right ms-2"></i></a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm border-warning">
                    <div class="card-body text-center">
                        <div class="icon-circle bg-warning text-white mb-3 mx-auto">
                            <i class="fas fa-file-alt fa-2x"></i>
                        </div>
                        <h5 class="card-title text-warning">Pengajuan Izin/Sakit</h5>
                        <p class="card-text fs-5">Tinjau dan proses pengajuan izin/sakit pegawai.</p>
                        <a href="list_izin.php" class="btn btn-outline-warning btn-lg mt-3">Kelola Izin <i class="fas fa-arrow-right ms-2"></i></a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array($hak_akses_user, ['Superadmin', 'Admin Dinas', 'Bendahara Dinas', 'Bupati'])): // Akses Rekap Bulanan ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm border-success">
                    <div class="card-body text-center">
                        <div class="icon-circle bg-success text-white mb-3 mx-auto">
                            <i class="fas fa-chart-bar fa-2x"></i>
                        </div>
                        <h5 class="card-title text-success">Rekapitulasi Bulanan</h5>
                        <p class="card-text fs-5">Lihat rekapitulasi absensi dan perhitungan TPP bulanan.</p>
                        <a href="rekap_bulanan.php" class="btn btn-outline-success btn-lg mt-3">Lihat Rekap <i class="fas fa-arrow-right ms-2"></i></a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($hak_akses_user === 'Superadmin'): // Akses Superadmin ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm border-primary">
                    <div class="card-body text-center">
                        <div class="icon-circle bg-primary text-white mb-3 mx-auto">
                            <i class="fas fa-building fa-2x"></i>
                        </div>
                        <h5 class="card-title text-primary">Manajemen Dinas</h5>
                        <p class="card-text fs-5">Tambah, edit, atau hapus data dinas.</p>
                        <a href="manage_dinas.php" class="btn btn-outline-primary btn-lg mt-3">Kelola Dinas <i class="fas fa-arrow-right ms-2"></i></a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array($hak_akses_user, ['Superadmin'])): // Hanya Superadmin untuk Manage Users (termasuk hak akses) ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm border-danger">
                    <div class="card-body text-center">
                        <div class="icon-circle bg-danger text-white mb-3 mx-auto">
                            <i class="fas fa-users-cog fa-2x"></i>
                        </div>
                        <h5 class="card-title text-danger">Manajemen Pengguna Aplikasi</h5>
                        <p class="card-text fs-5">Kelola akun pengguna dan hak akses sistem.</p>
                        <a href="manage_users.php" class="btn btn-outline-danger btn-lg mt-3">Kelola Pengguna <i class="fas fa-arrow-right ms-2"></i></a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array($hak_akses_user, ['Superadmin', 'Admin Dinas'])): // Superadmin & Admin Dinas untuk Manage Pegawai ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm border-secondary">
                    <div class="card-body text-center">
                        <div class="icon-circle bg-secondary text-white mb-3 mx-auto">
                            <i class="fas fa-user-tie fa-2x"></i>
                        </div>
                        <h5 class="card-title text-secondary">Manajemen Data Pegawai</h5>
                        <p class="card-text fs-5">Tambah, edit, atau hapus data informasi pegawai.</p>
                        <a href="manage_pegawai.php" class="btn btn-outline-secondary btn-lg mt-3">Kelola Pegawai <i class="fas fa-arrow-right ms-2"></i></a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($hak_akses_user === 'Superadmin'): // Hanya Superadmin untuk Pengaturan Sistem ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm border-dark">
                    <div class="card-body text-center">
                        <div class="icon-circle bg-dark text-white mb-3 mx-auto">
                            <i class="fas fa-cogs fa-2x"></i>
                        </div>
                        <h5 class="card-title text-dark">Pengaturan Sistem</h5>
                        <p class="card-text fs-5">Konfigurasi jam kerja, hari libur, dan parameter sistem lainnya.</p>
                        <a href="manage_settings.php" class="btn btn-outline-dark btn-lg mt-3">Kelola Pengaturan <i class="fas fa-arrow-right ms-2"></i></a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>