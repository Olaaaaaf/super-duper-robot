<?php
// manage_settings.php

// Aktifkan error reporting untuk debugging (HAPUS DI PRODUCTION SAAT LIVE)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ==================================================================================================
// BAGIAN INI HARUS BERADA DI PALING ATAS FILE, SEBELUM OUTPUT APAPUN (TERMASUK HTML DARI HEADER.PHP)
// ==================================================================================================

// 1. MUAT CONFIG.PHP HANYA SEKALI DI SINI
// Ini akan mendefinisikan konstanta DB_SERVER, DB_USERNAME, dll.
require_once __DIR__ . "/../config.php";

// 2. Pastikan session dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 3. Include file koneksi database dan fungsi umum
// db_connection.php akan membuat objek $mysqli
require_once __DIR__ . "/../includes/db_connection.php";
require_once __DIR__ . "/../includes/functions.php"; // Ini penting karena ada fungsi get_pengaturan()

// 4. Inisialisasi variabel pesan
$message = "";
$message_type = "";

// 5. Cek hak akses SEBELUM ada output HTML
if (!isset($_SESSION["hak_akses"]) || $_SESSION["hak_akses"] !== 'Superadmin') {
    header("location: ../index.php");
    exit;
}

// --- PROSES UPDATE PENGATURAN / TAMBAH/HAPUS HARI LIBUR ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        // =====================================================================================================
        // PROSES UPDATE PENGATURAN UMUM
        // =====================================================================================================
        if ($action == 'update_settings') {
            $settings_to_update = [
                'Jam Masuk Default' => trim($_POST['jam_masuk_default']),
                'Jam Pulang Default' => trim($_POST['jam_pulang_default']),
                'Waktu Toleransi Keterlambatan' => filter_var($_POST['toleransi_keterlambatan'], FILTER_VALIDATE_INT),
                'Max Izin Tanpa Surat' => filter_var($_POST['max_izin_tanpa_surat'], FILTER_VALIDATE_INT),
                'Persentase Potongan Alpha' => filter_var($_POST['potongan_alpha'], FILTER_VALIDATE_FLOAT),
                'Persentase Potongan Izin' => filter_var($_POST['potongan_izin'], FILTER_VALIDATE_FLOAT),
                'Persentase Potongan Sakit' => filter_var($_POST['potongan_sakit'], FILTER_VALIDATE_FLOAT),
                'Persentase Potongan Tidak Disetujui' => filter_var($_POST['potongan_tidak_setuju'], FILTER_VALIDATE_FLOAT)
            ];

            $all_valid = true;
            foreach ($settings_to_update as $key => $value) {
                if ($value === false) { // Validasi filter_var
                    $message = "Input tidak valid untuk " . htmlspecialchars($key) . ". Mohon periksa nilai numerik.";
                    $message_type = "danger";
                    $all_valid = false;
                    break;
                }
            }

            if ($all_valid) {
                $mysqli->begin_transaction();
                try {
                    $stmt = $mysqli->prepare("INSERT INTO pengaturan (parameter, nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE nilai = ?");
                    if (!$stmt) {
                        throw new Exception("Gagal menyiapkan statement: " . $mysqli->error);
                    }

                    foreach ($settings_to_update as $param => $nilai) {
                        // Pastikan nilai float disimpan dengan titik sebagai desimal
                        if (is_float($nilai)) {
                            $nilai = str_replace(',', '.', (string)$nilai);
                        }
                        $stmt->bind_param("sss", $param, $nilai, $nilai);
                        if (!$stmt->execute()) {
                            throw new Exception("Gagal update pengaturan '" . htmlspecialchars($param) . "': " . $stmt->error);
                        }
                    }
                    $stmt->close();
                    $mysqli->commit();
                    $message = "Pengaturan berhasil diperbarui.";
                    $message_type = "success";

                    // Setelah pengaturan disimpan, Anda mungkin perlu memuat ulang konstanta dinamis
                    // atau cukup mengandalkan get_pengaturan() saat dibutuhkan.
                    // Untuk saat ini, kita tidak akan me-redefine konstanta di sini.
                    // Mereka akan diambil lagi saat halaman dimuat ulang atau saat get_pengaturan() dipanggil.

                } catch (Exception $e) {
                    $mysqli->rollback();
                    $message = "Gagal memperbarui pengaturan: " . $e->getMessage();
                    $message_type = "danger";
                    error_log("Error updating settings: " . $e->getMessage());
                }
            }
        }
        // =====================================================================================================
        // PROSES TAMBAH HARI LIBUR
        // =====================================================================================================
        elseif ($action == 'add_holiday') {
            $tanggal_libur = trim($_POST['tanggal_libur']);
            $keterangan = trim($_POST['keterangan']);

            if (empty($tanggal_libur) || empty($keterangan)) {
                $message = "Tanggal dan Keterangan hari libur wajib diisi.";
                $message_type = "danger";
            } elseif (!strtotime($tanggal_libur)) {
                $message = "Format tanggal tidak valid.";
                $message_type = "danger";
            } else {
                $sql_insert = "INSERT INTO hari_libur (tanggal, keterangan) VALUES (?, ?)";
                if ($stmt = $mysqli->prepare($sql_insert)) {
                    $stmt->bind_param("ss", $tanggal_libur, $keterangan);
                    if ($stmt->execute()) {
                        $message = "Hari libur berhasil ditambahkan.";
                        $message_type = "success";
                    } else {
                        if ($mysqli->errno == 1062) { // Duplicate entry
                            $message = "Tanggal tersebut sudah terdaftar sebagai hari libur.";
                        } else {
                            $message = "Gagal menambahkan hari libur: " . $stmt->error;
                            error_log("Error adding holiday: " . $stmt->error);
                        }
                        $message_type = "danger";
                    }
                    $stmt->close();
                } else {
                    $message = "Gagal menyiapkan query tambah hari libur: " . $mysqli->error;
                    $message_type = "danger";
                    error_log("Error preparing add holiday query: " . $mysqli->error);
                }
            }
        }
        // =====================================================================================================
        // PROSES HAPUS HARI LIBUR
        // =====================================================================================================
        elseif ($action == 'delete_holiday') {
            $id_libur = filter_var($_POST['id_libur'], FILTER_VALIDATE_INT);

            if ($id_libur === false) {
                $message = "ID Hari libur tidak valid.";
                $message_type = "danger";
            } else {
                $sql_delete = "DELETE FROM hari_libur WHERE id_libur = ?";
                if ($stmt = $mysqli->prepare($sql_delete)) {
                    $stmt->bind_param("i", $id_libur);
                    if ($stmt->execute()) {
                        $message = "Hari libur berhasil dihapus.";
                        $message_type = "success";
                    } else {
                        $message = "Gagal menghapus hari libur: " . $stmt->error;
                        $message_type = "danger";
                        error_log("Error deleting holiday: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    $message = "Gagal menyiapkan query hapus hari libur: " . $mysqli->error;
                    $message_type = "danger";
                    error_log("Error preparing delete holiday query: " . $mysqli->error);
                }
            }
        }
    }
    // REDIRECT UNTUK MENCEGAH FORM RESUBMISSION (WAJIB DI SINI!)
    header("Location: " . $_SERVER['PHP_SELF'] . "?status_msg=" . $message_type . "&msg=" . urlencode($message));
    exit();
}

// 6. Ambil pesan dari URL setelah redirect (ini bisa di sini)
if (isset($_GET['status_msg']) && isset($_GET['msg'])) {
    $message_type = htmlspecialchars($_GET['status_msg']);
    $message = htmlspecialchars(urldecode($_GET['msg']));
}

// ==================================================================================================
// BAGIAN INI DIMULAI SETELAH SEMUA LOGIKA SERVER-SIDE SELESAI DAN REDIRECT SUDAH DILAKUKAN
// ==================================================================================================

// Set judul halaman sebelum memuat header.php
$page_title = "Pengaturan Sistem";

// Muat header.php
require_once __DIR__ . "/../includes/header.php";

// Ambil nilai pengaturan saat ini untuk ditampilkan di form
// Pastikan get_pengaturan() menggunakan $mysqli yang sudah ada
$jam_masuk_default = get_pengaturan($mysqli, 'Jam Masuk Default') ?? JAM_MASUK_DEFAULT;
$jam_pulang_default = get_pengaturan($mysqli, 'Jam Pulang Default') ?? JAM_PULANG_DEFAULT;
$toleransi_keterlambatan = get_pengaturan($mysqli, 'Waktu Toleransi Keterlambatan') ?? TOLERANSI_KETERLAMBATAN;
$max_izin_tanpa_surat = get_pengaturan($mysqli, 'Max Izin Tanpa Surat') ?? MAX_IZIN_TANPA_SURAT;
$potongan_alpha = get_pengaturan($mysqli, 'Persentase Potongan Alpha') ?? (POTONGAN_ALPHA * 100);
$potongan_izin = get_pengaturan($mysqli, 'Persentase Potongan Izin') ?? (POTONGAN_IZIN * 100);
$potongan_sakit = get_pengaturan($mysqli, 'Persentase Potongan Sakit') ?? (POTONGAN_Sakit * 100);
$potongan_tidak_setuju = get_pengaturan($mysqli, 'Persentase Potongan Tidak Disetujui') ?? (POTONGAN_ALPHA * 100); // Menggunakan alpha sebagai default jika tidak ada


// Ambil daftar hari libur
$hari_libur = [];
$sql_select_holiday = "SELECT id_libur, tanggal, keterangan FROM hari_libur ORDER BY tanggal ASC";
if ($result_holiday = $mysqli->query($sql_select_holiday)) {
    while ($row_holiday = $result_holiday->fetch_assoc()) {
        $hari_libur[] = $row_holiday;
    }
    $result_holiday->free();
} else {
    error_log("Error fetching holidays: " . $mysqli->error);
    if (empty($message)) {
        $message = "Gagal memuat daftar hari libur.";
        $message_type = "danger";
    }
}
?>

<div class="wrapper">
    <h2 class="mb-4">Pengaturan Sistem</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            Pengaturan Umum Absensi
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="action" value="update_settings">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="jam_masuk_default" class="form-label">Jam Masuk Default</label>
                        <input type="time" class="form-control" id="jam_masuk_default" name="jam_masuk_default" value="<?php echo htmlspecialchars($jam_masuk_default); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="jam_pulang_default" class="form-label">Jam Pulang Default</label>
                        <input type="time" class="form-control" id="jam_pulang_default" name="jam_pulang_default" value="<?php echo htmlspecialchars($jam_pulang_default); ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="toleransi_keterlambatan" class="form-label">Waktu Toleransi Keterlambatan (menit)</label>
                        <input type="number" class="form-control" id="toleransi_keterlambatan" name="toleransi_keterlambatan" value="<?php echo htmlspecialchars($toleransi_keterlambatan); ?>" min="0" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="max_izin_tanpa_surat" class="form-label">Maksimal Izin Tanpa Surat (hari)</label>
                        <input type="number" class="form-control" id="max_izin_tanpa_surat" name="max_izin_tanpa_surat" value="<?php echo htmlspecialchars($max_izin_tanpa_surat); ?>" min="0" required>
                    </div>
                </div>
                <hr>
                <h6>Persentase Potongan TPP (dalam %)</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="potongan_alpha" class="form-label">Alpha</label>
                        <input type="number" step="0.01" class="form-control" id="potongan_alpha" name="potongan_alpha" value="<?php echo htmlspecialchars($potongan_alpha); ?>" min="0" max="100" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="potongan_izin" class="form-label">Izin Disetujui</label>
                        <input type="number" step="0.01" class="form-control" id="potongan_izin" name="potongan_izin" value="<?php echo htmlspecialchars($potongan_izin); ?>" min="0" max="100" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="potongan_sakit" class="form-label">Sakit Disetujui</label>
                        <input type="number" step="0.01" class="form-control" id="potongan_sakit" name="potongan_sakit" value="<?php echo htmlspecialchars($potongan_sakit); ?>" min="0" max="100" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="potongan_tidak_setuju" class="form-label">Tidak Disetujui (Izin/Sakit)</label>
                        <input type="number" step="0.01" class="form-control" id="potongan_tidak_setuju" name="potongan_tidak_setuju" value="<?php echo htmlspecialchars($potongan_tidak_setuju); ?>" min="0" max="100" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Pengaturan</button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            Manajemen Hari Libur Nasional
        </div>
        <div class="card-body">
            <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#modalAddHoliday">
                <i class="fas fa-plus"></i> Tambah Hari Libur
            </button>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($hari_libur)): ?>
                            <tr>
                                <td colspan="3" class="text-center">Tidak ada hari libur yang terdaftar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($hari_libur as $libur): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($libur['tanggal']); ?></td>
                                <td><?php echo htmlspecialchars($libur['keterangan']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalDeleteHoliday"
                                        data-id="<?php echo $libur['id_libur']; ?>"
                                        data-tanggal="<?php echo htmlspecialchars($libur['tanggal']); ?>"
                                        data-keterangan="<?php echo htmlspecialchars($libur['keterangan']); ?>">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddHoliday" tabindex="-1" aria-labelledby="modalAddHolidayLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="modalAddHolidayLabel">Tambah Hari Libur Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="action" value="add_holiday">
            <div class="mb-3">
                <label for="add_tanggal_libur" class="form-label">Tanggal</label>
                <input type="date" class="form-control" id="add_tanggal_libur" name="tanggal_libur" required>
            </div>
            <div class="mb-3">
                <label for="add_keterangan" class="form-label">Keterangan</label>
                <input type="text" class="form-control" id="add_keterangan" name="keterangan" required>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">Tambah</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalDeleteHoliday" tabindex="-1" aria-labelledby="modalDeleteHolidayLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="modalDeleteHolidayLabel">Konfirmasi Hapus Hari Libur</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="delete_holiday">
          <input type="hidden" name="id_libur" id="delete_holiday_id">
          <p>Anda yakin ingin menghapus hari libur <b><span id="delete_holiday_date"></span></b> (<span id="delete_holiday_keterangan"></span>)?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Hapus</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    // Handle Delete Holiday Modal
    $('#modalDeleteHoliday').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id_libur = button.data('id');
        var tanggal_libur = button.data('tanggal');
        var keterangan_libur = button.data('keterangan');
        var modal = $(this);
        modal.find('#delete_holiday_id').val(id_libur);
        modal.find('#delete_holiday_date').text(tanggal_libur);
        modal.find('#delete_holiday_keterangan').text(keterangan_libur);
    });
});
</script>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>