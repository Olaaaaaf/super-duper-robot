<?php
$page_title = "Pengajuan Izin/Sakit";
require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/functions.php"; // Sertakan functions.php

if ($_SESSION["hak_akses"] !== 'Pegawai') {
    header("location: ../index.php");
    exit;
}

$nip_pegawai = $_SESSION["nip"];
$pengajuan_message = "";
$pengajuan_type = "";

// Proses Pengajuan Izin/Sakit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tanggal_mulai = trim($_POST['tanggal_mulai']);
    $tanggal_selesai = trim($_POST['tanggal_selesai']);
    $keterangan = trim($_POST['keterangan']);
    $catatan_pegawai = trim($_POST['catatan_pegawai']);
    $lampiran_path = null;

    if (empty($tanggal_mulai) || empty($tanggal_selesai) || empty($keterangan)) {
        $pengajuan_message = "Tanggal mulai, tanggal selesai, dan jenis keterangan tidak boleh kosong.";
        $pengajuan_type = "danger";
    } elseif (strtotime($tanggal_mulai) > strtotime($tanggal_selesai)) {
        $pengajuan_message = "Tanggal mulai tidak boleh lebih dari tanggal selesai.";
        $pengajuan_type = "danger";
    } else {
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $max_size_kb = 2048; // 2MB
        $upload_dir = '../uploads/izin/'; // Path relatif dari script PHP

        // Hanya proses upload jika ada file
        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file_validation = validate_uploaded_file($_FILES['lampiran'], $allowed_extensions, $max_size_kb);

            if ($file_validation['success']) {
                $upload_result = upload_file($_FILES['lampiran'], $upload_dir);
                if ($upload_result['success']) {
                    $lampiran_path = $upload_result['path']; // Ini adalah path relatif dari htdocs
                } else {
                    $pengajuan_message = "Gagal mengunggah lampiran: " . $upload_result['message'];
                    $pengajuan_type = "danger";
                }
            } else {
                $pengajuan_message = "Validasi lampiran gagal: " . $file_validation['message'];
                $pengajuan_type = "danger";
            }
        }

        // Jika tidak ada error upload atau tidak ada file yang diunggah
        if (empty($pengajuan_message)) {
            $status_izin = 'Menunggu';

            // Hitung durasi izin
            $start_date = new DateTime($tanggal_mulai);
            $end_date = new DateTime($tanggal_selesai);
            $interval = $start_date->diff($end_date);
            $durasi_hari = $interval->days + 1;

            // Logika untuk "Alpha" (jika diajukan sebagai Alpha, tidak perlu lampiran)
            // Atau jika Keterangan adalah "Hadir", itu bukan pengajuan izin, mungkin error form
            if ($keterangan === 'Hadir' || $keterangan === 'Alpha') {
                $pengajuan_message = "Keterangan 'Hadir' atau 'Alpha' tidak bisa diajukan sebagai izin. Pilih Izin, Sakit, Cuti, atau Dinas Luar.";
                $pengajuan_type = "danger";
            } else {
                // Jika pengajuan adalah izin tanpa lampiran dan melebihi batas
                if ($keterangan === 'Izin' && empty($lampiran_path) && $durasi_hari > MAX_IZIN_TANPA_SURAT_ACTUAL) {
                     $pengajuan_message = "Pengajuan Izin tanpa lampiran maksimal " . MAX_IZIN_TANPA_SURAT_ACTUAL . " hari. Mohon lampirkan bukti atau hubungi admin.";
                     $pengajuan_type = "danger";
                } else {
                    // Insert atau update data absensi untuk setiap hari dalam rentang pengajuan
                    $current_date = $start_date;
                    while ($current_date <= $end_date) {
                        $current_date_str = $current_date->format('Y-m-d');

                        // Cek apakah sudah ada record absensi untuk hari ini
                        $sql_check_exist = "SELECT COUNT(*) FROM absensi WHERE nip = ? AND tanggal = ?";
                        $count = 0;
                        if ($stmt_check = $mysqli->prepare($sql_check_exist)) {
                            $stmt_check->bind_param("ss", $nip_pegawai, $current_date_str);
                            $stmt_check->execute();
                            $stmt_check->bind_result($count);
                            $stmt_check->fetch();
                            $stmt_check->close();
                        }

                        if ($count > 0) {
                            // Update record yang sudah ada
                            $sql_update = "UPDATE absensi SET keterangan = ?, lampiran_izin_sakit = ?, status_izin = ? WHERE nip = ? AND tanggal = ?";
                            if ($stmt = $mysqli->prepare($sql_update)) {
                                $stmt->bind_param("sssss", $keterangan, $lampiran_path, $status_izin, $nip_pegawai, $current_date_str);
                                $stmt->execute();
                                $stmt->close();
                            }
                        } else {
                            // Insert record baru
                            $sql_insert = "INSERT INTO absensi (nip, tanggal, keterangan, lampiran_izin_sakit, status_izin) VALUES (?, ?, ?, ?, ?)";
                            if ($stmt = $mysqli->prepare($sql_insert)) {
                                $stmt->bind_param("sssss", $nip_pegawai, $current_date_str, $keterangan, $lampiran_path, $status_izin);
                                $stmt->execute();
                                $stmt->close();
                            }
                        }
                        $current_date->modify('+1 day');
                    }
                    $pengajuan_message = "Pengajuan " . htmlspecialchars($keterangan) . " untuk tanggal " . format_tanggal($tanggal_mulai) . " s/d " . format_tanggal($tanggal_selesai) . " berhasil diajukan. Menunggu persetujuan.";
                    $pengajuan_type = "success";
                    // Bersihkan form setelah submit berhasil
                    $tanggal_mulai = $tanggal_selesai = $catatan_pegawai = "";
                }
            }
        }
    }
}

// Ambil riwayat pengajuan izin/sakit pegawai
$riwayat_pengajuan = [];
$sql_riwayat = "SELECT tanggal, keterangan, status_izin, lampiran_izin_sakit FROM absensi WHERE nip = ? AND keterangan IN ('Izin', 'Sakit', 'Cuti', 'Dinas Luar') ORDER BY tanggal DESC LIMIT 20"; // Ambil 20 riwayat terakhir
if ($stmt = $mysqli->prepare($sql_riwayat)) {
    $stmt->bind_param("s", $nip_pegawai);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $riwayat_pengajuan[] = $row;
    }
    $stmt->close();
}
?>

<div class="wrapper">
    <h2 class="mb-4">Pengajuan Izin / Sakit</h2>

    <?php if (!empty($pengajuan_message)): ?>
        <div class="alert alert-<?php echo $pengajuan_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $pengajuan_message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            Form Pengajuan
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="tanggal_mulai">Tanggal Mulai:</label>
                    <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="<?php echo $tanggal_mulai; ?>" required>
                </div>
                <div class="form-group">
                    <label for="tanggal_selesai">Tanggal Selesai:</label>
                    <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" value="<?php echo $tanggal_selesai; ?>" required>
                </div>
                <div class="form-group">
                    <label for="keterangan">Jenis Keterangan:</label>
                    <select class="form-control" id="keterangan" name="keterangan" required>
                        <option value="">Pilih...</option>
                        <option value="Izin" <?php echo ($keterangan == 'Izin') ? 'selected' : ''; ?>>Izin</option>
                        <option value="Sakit" <?php echo ($keterangan == 'Sakit') ? 'selected' : ''; ?>>Sakit</option>
                        <option value="Cuti" <?php echo ($keterangan == 'Cuti') ? 'selected' : ''; ?>>Cuti</option>
                        <option value="Dinas Luar" <?php echo ($keterangan == 'Dinas Luar') ? 'selected' : ''; ?>>Dinas Luar</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="catatan_pegawai">Catatan/Keterangan Tambahan:</label>
                    <textarea class="form-control" id="catatan_pegawai" name="catatan_pegawai" rows="3"><?php echo $catatan_pegawai; ?></textarea>
                </div>
                <div class="form-group">
                    <label for="lampiran">Lampiran (Surat Dokter/Surat Izin, dll.) - Max 2MB, format PDF, JPG, PNG:</label>
                    <input type="file" class="form-control-file" id="lampiran" name="lampiran" accept=".pdf,.jpg,.jpeg,.png">
                    <small class="form-text text-muted">Untuk izin lebih dari <?php echo MAX_IZIN_TANPA_SURAT_ACTUAL; ?> hari, lampiran wajib diunggah.</small>
                </div>
                <button type="submit" class="btn btn-primary">Ajukan</button>
            </form>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            Riwayat Pengajuan Saya
        </div>
        <div class="card-body">
            <?php if (empty($riwayat_pengajuan)): ?>
                <div class="alert alert-info text-center">Belum ada riwayat pengajuan.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th>Status</th>
                                <th>Lampiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayat_pengajuan as $row): ?>
                            <tr>
                                <td><?php echo format_tanggal($row['tanggal']); ?></td>
                                <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                <td>
                                    <?php
                                    $status_color = '';
                                    switch ($row['status_izin']) {
                                        case 'Menunggu':
                                            $status_color = 'badge-secondary';
                                            break;
                                        case 'Disetujui':
                                            $status_color = 'badge-success';
                                            break;
                                        case 'Ditolak':
                                            $status_color = 'badge-danger';
                                            break;
                                    }
                                    echo '<span class="badge ' . $status_color . '">' . htmlspecialchars($row['status_izin']) . '</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['lampiran_izin_sakit'])): ?>
                                        <a href="<?php echo htmlspecialchars($row['lampiran_izin_sakit']); ?>" target="_blank" class="btn btn-sm btn-primary"><i class="fas fa-paperclip"></i> Lihat</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>