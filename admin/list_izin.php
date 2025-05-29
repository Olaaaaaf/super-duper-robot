<?php
$page_title = "Daftar Pengajuan Izin/Sakit";
require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/functions.php"; // Pastikan ini hanya ada satu kali dan tidak dikomentari

// Aktifkan error reporting untuk debugging (opsional, hapus di production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Pastikan user memiliki hak akses yang sesuai
if (!in_array($_SESSION["hak_akses"], ['Superadmin', 'Admin Dinas', 'Bendahara Dinas', 'Bupati'])) {
    header("location: ../index.php");
    exit;
}

$hak_akses_user = $_SESSION["hak_akses"];
// Perbaikan: Gunakan null coalescing operator untuk menghindari undefined index jika dinas tidak ada
$dinas_user = $_SESSION["dinas"] ?? ''; 
$message = "";
$message_type = "";

// Handle perubahan status izin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $id_absensi = filter_var($_POST['id_absensi'], FILTER_VALIDATE_INT);
    $new_status = trim($_POST['new_status']);
    $catatan_admin = trim($_POST['catatan_admin']);

    // Validasi input
    if ($id_absensi === false || !in_array($new_status, ['Disetujui', 'Ditolak'])) {
        $message = "Input tidak valid.";
        $message_type = "danger";
    } else {
        // Periksa hak akses untuk mengupdate
        $can_update = false;
        $sql_check_access = "SELECT p.dinas FROM absensi a JOIN pegawai p ON a.nip = p.nip WHERE a.id_absensi = ?";
        if ($stmt_check = $mysqli->prepare($sql_check_access)) {
            $stmt_check->bind_param("i", $id_absensi);
            $stmt_check->execute();
            $stmt_check->bind_result($absensi_dinas);
            $stmt_check->fetch();
            $stmt_check->close();

            if ($hak_akses_user === 'Superadmin' || $hak_akses_user === 'Bupati') {
                $can_update = true; // Superadmin dan Bupati bisa update semua
            } elseif (($hak_akses_user === 'Admin Dinas' || $hak_akses_user === 'Bendahara Dinas') && $absensi_dinas === $dinas_user) {
                $can_update = true; // Admin Dinas dan Bendahara Dinas bisa update di dinasnya sendiri
            }
        }

        if ($can_update) {
            $sql_update_status = "UPDATE absensi SET status_izin = ?, catatan_admin = ? WHERE id_absensi = ?";
            if ($stmt = $mysqli->prepare($sql_update_status)) {
                $stmt->bind_param("ssi", $new_status, $catatan_admin, $id_absensi);
                if ($stmt->execute()) {
                    $message = "Status pengajuan berhasil diperbarui menjadi " . htmlspecialchars($new_status) . ".";
                    $message_type = "success";
                } else {
                    $message = "Gagal memperbarui status: " . $stmt->error;
                    $message_type = "danger";
                }
                $stmt->close();
            } else {
                $message = "Gagal menyiapkan query update status: " . $mysqli->error;
                $message_type = "danger";
            }
        } else {
            $message = "Anda tidak memiliki izin untuk mengubah status pengajuan ini.";
            $message_type = "danger";
        }
    }
    // Redirect untuk mencegah form resubmission
    header("Location: list_izin.php?status_msg=" . $message_type . "&msg=" . urlencode($message));
    exit();
}

// Ambil pesan dari URL setelah redirect
if (isset($_GET['status_msg']) && isset($_GET['msg'])) {
    $message_type = htmlspecialchars($_GET['status_msg']);
    $message = htmlspecialchars(urldecode($_GET['msg']));
}

$pengajuan_data = [];
$query_params = [];
$query_types = "";

// Query dasar
$sql_pengajuan = "SELECT a.id_absensi, a.tanggal, a.keterangan, a.status_izin, a.lampiran_izin_sakit, a.catatan_admin, p.nip, p.nama, p.dinas
                FROM absensi a
                JOIN pegawai p ON a.nip = p.nip
                WHERE a.keterangan IN ('Izin', 'Sakit', 'Cuti', 'Dinas Luar') "; // Hanya tampilkan yang berjenis pengajuan

// Filter berdasarkan hak akses
if ($hak_akses_user === 'Admin Dinas' || $hak_akses_user === 'Bendahara Dinas') {
    $sql_pengajuan .= " AND p.dinas = ? ";
    $query_params[] = $dinas_user;
    $query_types .= "s";
}

// Filter status (jika ada)
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';
if (!empty($filter_status)) {
    $sql_pengajuan .= " AND a.status_izin = ? ";
    $query_params[] = $filter_status;
    $query_types .= "s";
} else {
    // Default: hanya tampilkan yang 'Menunggu' untuk Admin Dinas/Bendahara Dinas jika tidak ada filter status yang dipilih
    if ($hak_akses_user === 'Admin Dinas' || $hak_akses_user === 'Bendahara Dinas') {
         $sql_pengajuan .= " AND a.status_izin = 'Menunggu' ";
    }
}


$sql_pengajuan .= " ORDER BY a.tanggal DESC";

if ($stmt = $mysqli->prepare($sql_pengajuan)) {
    if (!empty($query_types)) {
        $stmt->bind_param($query_types, ...$query_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pengajuan_data[] = $row;
    }
    $stmt->close();
} else {
    // Tambahkan error logging jika query gagal disiapkan
    $message = "Gagal menyiapkan query pengajuan: " . $mysqli->error;
    $message_type = "danger";
}
?>

<div class="wrapper">
    <h2 class="mb-4">Daftar Pengajuan Izin/Sakit Pegawai</h2>

 <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">Filter Pengajuan</div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="row g-3 align-items-center">
                <div class="form-group mr-3 mb-2">
                    <label for="filter_status" class="mr-2">Status:</label>
                    <select class="form-control" id="filter_status" name="filter_status">
                        <option value="">Semua Status</option>
                        <option value="Menunggu" <?php echo ($filter_status == 'Menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                        <option value="Disetujui" <?php echo ($filter_status == 'Disetujui') ? 'selected' : ''; ?>>Disetujui</option>
                        <option value="Ditolak" <?php echo ($filter_status == 'Ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mb-2"><i class="fas fa-filter"></i> Filter</button>
                <a href="list_izin.php" class="btn btn-secondary mb-2 ms-2"><i class="fas fa-sync-alt"></i> Reset</a>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <p>Ini adalah daftar pengajuan yang perlu Anda tinjau atau yang sudah diproses.</p>
        </div>
    </div>


    <?php if (empty($pengajuan_data)): ?>
        <div class="alert alert-info text-center">Tidak ada pengajuan izin/sakit untuk kriteria yang dipilih.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>Tanggal</th>
                        <th>NIP</th>
                        <th>Nama</th>
                        <th>Dinas</th>
                        <th>Keterangan</th>
                        <th>Status</th>
                        <th>Lampiran</th>
                        <?php if (in_array($hak_akses_user, ['Superadmin', 'Admin Dinas', 'Bendahara Dinas'])): ?>
                        <th>Catatan Admin</th>
                        <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pengajuan_data as $row): ?>
                    <tr>
                        <td><?php echo format_tanggal($row['tanggal']); ?></td>
                        <td><?php echo htmlspecialchars($row['nip']); ?></td>
                        <td><?php echo htmlspecialchars($row['nama']); ?></td>
                        <td><?php echo htmlspecialchars($row['dinas']); ?></td>
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
                        <?php if (in_array($hak_akses_user, ['Superadmin', 'Admin Dinas', 'Bendahara Dinas'])): ?>
                        <td><?php echo htmlspecialchars($row['catatan_admin'] ?? '-'); ?></td>
                        <td>
                            <?php if ($row['status_izin'] === 'Menunggu'): ?>
                                <button type="button" class="btn btn-sm btn-success me-1" data-bs-toggle="modal" data-bs-target="#modalApproveReject"
                                    data-id="<?php echo $row['id_absensi']; ?>" data-status="Disetujui" data-catatan="<?php echo htmlspecialchars($row['catatan_admin'] ?? ''); ?>">
                                    <i class="fas fa-check"></i> Setujui
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalApproveReject"
                                    data-id="<?php echo $row['id_absensi']; ?>" data-status="Ditolak" data-catatan="<?php echo htmlspecialchars($row['catatan_admin'] ?? ''); ?>">
                                    <i class="fas fa-times"></i> Tolak
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary" disabled>Sudah Diproses</button>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalApproveReject" tabindex="-1" role="dialog" aria-labelledby="modalApproveRejectLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="modalApproveRejectLabel">Konfirmasi Aksi Pengajuan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="update_status">
          <input type="hidden" name="id_absensi" id="modal_id_absensi">
          <input type="hidden" name="new_status" id="modal_new_status">
          <p id="modal_confirmation_text" class="lead"></p>
          <div class="form-group">
            <label for="catatan_admin">Catatan Admin (Opsional):</label>
            <textarea class="form-control" id="modal_catatan_admin" name="catatan_admin" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button> <button type="submit" class="btn btn-primary" id="modal_submit_btn">Konfirmasi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
    // Menggunakan jQuery (jika Anda masih memuat jQuery)
    // Jika tidak menggunakan jQuery, ganti dengan vanilla JS atau pastikan Bootstrap JS sudah di-load
    $(document).ready(function() {
        var modalApproveReject = document.getElementById('modalApproveReject');
        modalApproveReject.addEventListener('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var id_absensi = button.data('id');
            var new_status = button.data('status');
            var catatan_admin = button.data('catatan'); // Ambil catatan yang sudah ada

            var modal = $(this);
            modal.find('#modal_id_absensi').val(id_absensi);
            modal.find('#modal_new_status').val(new_status);
            modal.find('#modal_catatan_admin').val(catatan_admin); // Set catatan yang sudah ada

            var confirmation_text = `Anda yakin ingin <b>${new_status}</b> pengajuan ini?`;
            modal.find('#modal_confirmation_text').html(confirmation_text);

            // Ubah warna tombol konfirmasi sesuai status
            if (new_status === 'Disetujui') {
                modal.find('#modal_submit_btn').removeClass('btn-danger').addClass('btn-success').html('<i class="fas fa-check"></i> Setujui');
            } else if (new_status === 'Ditolak') {
                modal.find('#modal_submit_btn').removeClass('btn-success').addClass('btn-danger').html('<i class="fas fa-times"></i> Tolak');
            }
        });
    });
</script>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>