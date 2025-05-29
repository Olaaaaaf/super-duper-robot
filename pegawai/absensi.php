<?php
// ==================================================================================================
// BAGIAN INI HARUS BERADA DI PALING ATAS FILE, SEBELUM OUTPUT APAPUN (TERMASUK HTML DARI HEADER.PHP)
// ==================================================================================================

// 1. AKTIFKAN ERROR REPORTING UNTUK DEBUGGING (HAPUS ATAU KOMENTARI SAAT LIVE DI PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Pastikan session dimulai (penting untuk $_SESSION)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 3. PENTING: Sertakan functions.php terlebih dahulu.
// Ini diperlukan karena config.php akan memanggil fungsi get_pengaturan().
require_once __DIR__ . "/../includes/functions.php";

// 4. Sertakan config.php secara eksplisit di sini.
// Ini akan memastikan $mysqli dan konstanta dari get_pengaturan() terdefinisi.
// Karena header.php juga akan meng-include config.php (via require_once),
// file tidak akan dieksekusi ulang, tetapi variabel dan konstanta akan tersedia.
require_once __DIR__ . "/../config.php";

// Set judul halaman
$page_title = "Absensi Harian";

// 5. Sertakan header.php.
// Header.php akan mencoba memuat config.php lagi, tapi require_once akan mencegah duplikasi.
require_once __DIR__ . "/../includes/header.php";

// Logika absensi
if ($_SESSION["hak_akses"] !== 'Pegawai') {
    header("location: ../index.php");
    exit;
}

$nip_pegawai = $_SESSION["nip"];
$tanggal_hari_ini = date('Y-m-d');
$jam_sekarang = date('H:i:s');
$absensi_message = "";
$absensi_type = "";
$status_absensi_masuk = false;
$status_absensi_pulang = false;
$jam_masuk_tercatat = null;
$jam_pulang_tercatat = null;
$keterangan_absensi = null;

// Cek apakah hari ini adalah hari libur
$hari_libur = get_hari_libur($mysqli);
$is_holiday = in_array($tanggal_hari_ini, $hari_libur);

// Cek hari kerja (Minggu/Sabtu)
$day_of_week = date('w', strtotime($tanggal_hari_ini));
$is_weekend = ($day_of_week == 0 || $day_of_week == 6); // 0=Minggu, 6=Sabtu

if ($is_holiday) {
    $absensi_message = "Hari ini adalah hari libur nasional. Tidak perlu absen.";
    $absensi_type = "info";
} elseif ($is_weekend) {
    $absensi_message = "Hari ini adalah hari Sabtu atau Minggu. Tidak perlu absen.";
    $absensi_type = "info";
} else {
    // Ambil jam masuk dan pulang dari konfigurasi atau default
    // Pastikan konstanta ini sudah didefinisikan di config.php atau jika tidak, gunakan default
    // Seharusnya sudah terdefinisi karena require_once config.php di atas.
    $jam_masuk_target = defined('JAM_MASUK_ABSEN') ? JAM_MASUK_ABSEN : '08:00:00';
    $jam_pulang_target = defined('JAM_PULANG_ABSEN') ? JAM_PULANG_ABSEN : '16:00:00';
    $toleransi_keterlambatan = defined('TOLERANSI_KETERLAMBATAN_AKTUAL') ? TOLERANSI_KETERLAMBATAN_AKTUAL : 15; // menit

    // Cek status absensi pegawai untuk hari ini
    $sql_absensi_hari_ini = "SELECT jam_masuk, jam_pulang, keterangan FROM absensi WHERE nip = ? AND tanggal = ?";
    if ($stmt = $mysqli->prepare($sql_absensi_hari_ini)) {
        $stmt->bind_param("ss", $nip_pegawai, $tanggal_hari_ini);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($jam_masuk, $jam_pulang, $keterangan);
            $stmt->fetch();
            $jam_masuk_tercatat = $jam_masuk;
            $jam_pulang_tercatat = $jam_pulang;
            $keterangan_absensi = $keterangan;

            if ($jam_masuk_tercatat) {
                $status_absensi_masuk = true;
            }
            if ($jam_pulang_tercatat) {
                $status_absensi_pulang = true;
            }
        }
        $stmt->close();
    } else {
        $absensi_message = "ERROR: Could not prepare query for checking absensi status. " . $mysqli->error;
        $absensi_type = "danger";
    }

    // Proses Absen Masuk/Pulang
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action == 'check_in' && !$status_absensi_masuk) {
            // Hitung status keterlambatan
            $waktu_masuk_pegawai = strtotime($jam_sekarang);
            $waktu_masuk_kantor = strtotime($jam_masuk_target);
            $selisih_detik = $waktu_masuk_pegawai - $waktu_masuk_kantor;
            $selisih_menit = floor($selisih_detik / 60);

            $status_kehadiran = 'Hadir';
            $catatan = '';

            if ($selisih_menit > $toleransi_keterlambatan) {
                $status_kehadiran = 'Terlambat';
                $catatan = "Terlambat " . ($selisih_menit) . " menit.";
            }

            $sql_insert_absensi = "INSERT INTO absensi (nip, tanggal, jam_masuk, keterangan, catatan_keterlambatan) VALUES (?, ?, ?, ?, ?)";
            if ($stmt = $mysqli->prepare($sql_insert_absensi)) {
                $stmt->bind_param("sssss", $nip_pegawai, $tanggal_hari_ini, $jam_sekarang, $status_kehadiran, $catatan);
                if ($stmt->execute()) {
                    $absensi_message = "Absen masuk berhasil pada pukul " . $jam_sekarang . ".";
                    if ($status_kehadiran === 'Terlambat') {
                        $absensi_message .= " Anda " . $catatan;
                        $absensi_type = "warning";
                    } else {
                        $absensi_type = "success";
                    }
                    $status_absensi_masuk = true;
                    $jam_masuk_tercatat = $jam_sekarang;
                    // Refresh keterangan_absensi jika ini absensi pertama hari ini
                    if (!$keterangan_absensi) {
                         $keterangan_absensi = $status_kehadiran;
                    }
                } else {
                    $absensi_message = "ERROR: Gagal mencatat absen masuk. " . $stmt->error;
                    $absensi_type = "danger";
                }
                $stmt->close();
            } else {
                $absensi_message = "ERROR: Could not prepare statement for check-in. " . $mysqli->error;
                $absensi_type = "danger";
            }
        } elseif ($action == 'check_out' && $status_absensi_masuk && !$status_absensi_pulang) {
            $sql_update_absensi = "UPDATE absensi SET jam_pulang = ? WHERE nip = ? AND tanggal = ?";
            if ($stmt = $mysqli->prepare($sql_update_absensi)) {
                $stmt->bind_param("sss", $jam_sekarang, $nip_pegawai, $tanggal_hari_ini);
                if ($stmt->execute()) {
                    $absensi_message = "Absen pulang berhasil pada pukul " . $jam_sekarang . ".";
                    $absensi_type = "success";
                    $status_absensi_pulang = true;
                    $jam_pulang_tercatat = $jam_sekarang;
                } else {
                    $absensi_message = "ERROR: Gagal mencatat absen pulang. " . $stmt->error;
                    $absensi_type = "danger";
                }
                $stmt->close();
            } else {
                $absensi_message = "ERROR: Could not prepare statement for check-out. " . $mysqli->error;
                $absensi_type = "danger";
            }
        }
    }
}
?>

<div class="wrapper">
    <div class="container-fluid py-4">
        <h2 class="mb-4 text-center text-primary"><?php echo htmlspecialchars($page_title); ?></h2>

        <?php if (!empty($absensi_message)): ?>
            <div class="alert alert-<?php echo $absensi_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $absensi_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($is_holiday || $is_weekend): ?>
            <div class="alert alert-info text-center" role="alert">
                Hari ini adalah <?php echo $is_holiday ? "hari libur nasional" : "akhir pekan"; ?>. Tidak perlu melakukan absensi.
            </div>
        <?php else: ?>
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white text-center">
                            <h4 class="mb-0">Waktu Saat Ini</h4>
                        </div>
                        <div class="card-body text-center">
                            <h1 class="display-3 fw-bold text-dark" id="current-time"></h1>
                            <p class="lead mb-0">Tanggal: <?php echo date('d M Y'); ?></p>
                            <p class="text-muted">Jam Masuk Target: <?php echo $jam_masuk_target; ?></p>
                            <p class="text-muted">Jam Pulang Target: <?php echo $jam_pulang_target; ?></p>
                            <p class="text-muted">Toleransi Keterlambatan: <?php echo $toleransi_keterlambatan; ?> menit</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center mt-4">
                <div class="col-md-4 col-lg-3 text-center">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center">
                            <h5 class="card-title text-primary">Absen Masuk</h5>
                            <?php if ($status_absensi_masuk): ?>
                                <p class="text-success fs-4"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($jam_masuk_tercatat); ?></p>
                                <button class="btn btn-success btn-lg" disabled><i class="fas fa-check-circle"></i> Sudah Absen Masuk</button>
                            <?php elseif ($keterangan_absensi && in_array($keterangan_absensi, ['Izin', 'Sakit', 'Cuti', 'Dinas Luar'])): ?>
                                <button class="btn btn-secondary btn-lg" disabled>Status: <?php echo $keterangan_absensi; ?></button>
                            <?php else: ?>
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <input type="hidden" name="action" value="check_in">
                                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-arrow-circle-right"></i> Absen Masuk</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-3 text-center">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center">
                            <h5 class="card-title text-info">Absen Pulang</h5>
                            <?php if ($status_absensi_pulang): ?>
                                <p class="text-success fs-4"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($jam_pulang_tercatat); ?></p>
                                <button class="btn btn-success btn-lg" disabled><i class="fas fa-check-circle"></i> Sudah Absen Pulang</button>
                            <?php elseif ($keterangan_absensi && in_array($keterangan_absensi, ['Izin', 'Sakit', 'Cuti', 'Dinas Luar'])): ?>
                                 <button class="btn btn-secondary btn-lg" disabled>Status: <?php echo $keterangan_absensi; ?></button>
                            <?php else: ?>
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <input type="hidden" name="action" value="check_out">
                                    <button type="submit" class="btn btn-info btn-lg"><i class="fas fa-arrow-circle-left"></i> Absen Pulang</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Real-time clock
    function updateTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('current-time').innerText = `${hours}:${minutes}:${seconds}`;
    }
    setInterval(updateTime, 1000);
    updateTime(); // Initial call to display time immediately
</script>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>