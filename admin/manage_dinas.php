<?php
// manage_dinas.php

// Aktifkan error reporting untuk debugging (HAPUS DI PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ==================================================================================================
// BAGIAN INI HARUS BERADA DI PALING ATAS FILE, SEBELUM OUTPUT APAPUN (TERMASUK HTML DARI HEADER.PHP)
// ==================================================================================================
require_once __DIR__ . "/../config.php"; // Ini akan membuat $mysqli
// 1. Pastikan session dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Include file koneksi database dan fungsi umum
require_once __DIR__ . "/../includes/functions.php";

// 3. Inisialisasi variabel pesan
$message = "";
$message_type = "";

// Ambil pesan dari URL setelah redirect (jika ada)
if (isset($_GET['status_msg']) && isset($_GET['msg'])) {
    $message_type = htmlspecialchars($_GET['status_msg']);
    $message = htmlspecialchars($_GET['msg']);
}


// 4. Cek hak akses SEBELUM ada output HTML
// Hanya Superadmin yang boleh mengelola dinas
if (!isset($_SESSION["hak_akses"]) || $_SESSION["hak_akses"] !== 'Superadmin') {
    header("location: ../index.php");
    exit;
}

$page_title = "Manajemen Data Dinas"; // Set judul halaman
require_once __DIR__ . "/../includes/header.php"; // Muat header HTML

// Proses form Tambah/Edit Dinas
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_dinas' || $action === 'edit_dinas') {
        $id_dinas = filter_var($_POST['id_dinas'] ?? null, FILTER_VALIDATE_INT);
        $nama_dinas = trim($_POST['nama_dinas']);
        $lokasi = trim($_POST['lokasi']);
        $latitude = trim($_POST['latitude']); // Biarkan string karena bisa desimal
        $longitude = trim($_POST['longitude']); // Biarkan string karena bisa desimal
        $radius_absensi = filter_var($_POST['radius_absensi'], FILTER_VALIDATE_INT);

        // Validasi dasar
        if (empty($nama_dinas) || empty($lokasi) || $radius_absensi === false) {
            $message = "Semua field wajib diisi (Nama Dinas, Lokasi, Radius Absensi).";
            $message_type = "danger";
        } elseif (!is_numeric($latitude) || !is_numeric($longitude)) {
             $message = "Latitude dan Longitude harus berupa angka.";
             $message_type = "danger";
        }
        else {
            // Cek apakah nama dinas sudah ada (kecuali saat edit dinas itu sendiri)
            $sql_check_dinas = "SELECT id_dinas FROM dinas WHERE nama_dinas = ?";
            if ($action === 'edit_dinas' && $id_dinas) {
                $sql_check_dinas .= " AND id_dinas != ?";
            }
            if ($stmt_check = $mysqli->prepare($sql_check_dinas)) {
                if ($action === 'edit_dinas' && $id_dinas) {
                    $stmt_check->bind_param("si", $nama_dinas, $id_dinas);
                } else {
                    $stmt_check->bind_param("s", $nama_dinas);
                }
                $stmt_check->execute();
                $stmt_check->store_result();
                if ($stmt_check->num_rows > 0) {
                    $message = "Nama dinas ini sudah terdaftar. Mohon gunakan nama lain.";
                    $message_type = "danger";
                    $stmt_check->close();
                } else {
                    $stmt_check->close(); // Tutup statement check dinas

                    if ($action === 'add_dinas') {
                        // Tambah Dinas Baru
                        $sql = "INSERT INTO dinas (nama_dinas, lokasi, latitude, longitude, radius_absensi) VALUES (?, ?, ?, ?, ?)";
                        if ($stmt = $mysqli->prepare($sql)) {
                            $stmt->bind_param("sssdi", $nama_dinas, $lokasi, $latitude, $longitude, $radius_absensi);
                            if ($stmt->execute()) {
                                $message = "Dinas berhasil ditambahkan.";
                                $message_type = "success";
                                header("location: manage_dinas.php?status_msg=success&msg=" . urlencode($message));
                                exit;
                            } else {
                                $message = "Gagal menambahkan dinas: " . $stmt->error;
                                $message_type = "danger";
                            }
                            $stmt->close();
                        } else {
                            $message = "Gagal menyiapkan statement: " . $mysqli->error;
                            $message_type = "danger";
                        }
                    } elseif ($action === 'edit_dinas' && $id_dinas) {
                        // Edit Dinas
                        $sql = "UPDATE dinas SET nama_dinas = ?, lokasi = ?, latitude = ?, longitude = ?, radius_absensi = ? WHERE id_dinas = ?";
                        if ($stmt = $mysqli->prepare($sql)) {
                            $stmt->bind_param("sssdi", $nama_dinas, $lokasi, $latitude, $longitude, $radius_absensi, $id_dinas);
                            if ($stmt->execute()) {
                                $message = "Dinas berhasil diperbarui.";
                                $message_type = "success";
                                header("location: manage_dinas.php?status_msg=success&msg=" . urlencode($message));
                                exit;
                            } else {
                                $message = "Gagal memperbarui dinas: " . $stmt->error;
                                $message_type = "danger";
                            }
                            $stmt->close();
                        } else {
                            $message = "Gagal menyiapkan statement: " . $mysqli->error;
                            $message_type = "danger";
                        }
                    }
                }
            } else {
                $message = "Gagal menyiapkan statement cek dinas: " . $mysqli->error;
                $message_type = "danger";
            }
        }
    } elseif ($action === 'delete_dinas') {
        $id_dinas = filter_var($_POST['id_dinas'], FILTER_VALIDATE_INT);

        if ($id_dinas === false) {
            $message = "ID dinas tidak valid.";
            $message_type = "danger";
        } else {
            // Cek apakah ada pegawai atau user yang terkait dengan dinas ini
            $has_related_data = false;
            $sql_check_pegawai = "SELECT COUNT(*) FROM pegawai WHERE id_dinas = ?";
            if ($stmt_check = $mysqli->prepare($sql_check_pegawai)) {
                $stmt_check->bind_param("i", $id_dinas);
                $stmt_check->execute();
                $stmt_check->bind_result($count);
                $stmt_check->fetch();
                $stmt_check->close();
                if ($count > 0) $has_related_data = true;
            }

            if (!$has_related_data) {
                $sql_check_users = "SELECT COUNT(*) FROM users WHERE id_dinas_terkait = ?";
                if ($stmt_check = $mysqli->prepare($sql_check_users)) {
                    $stmt_check->bind_param("i", $id_dinas);
                    $stmt_check->execute();
                    $stmt_check->bind_result($count);
                    $stmt_check->fetch();
                    $stmt_check->close();
                    if ($count > 0) $has_related_data = true;
                }
            }
            
            if ($has_related_data) {
                $message = "Tidak dapat menghapus dinas ini karena masih ada pegawai atau akun pengguna yang terkait dengannya. Mohon pindahkan atau hapus pegawai/akun terkait terlebih dahulu.";
                $message_type = "danger";
            } else {
                // Hapus dinas
                $sql = "DELETE FROM dinas WHERE id_dinas = ?";
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param("i", $id_dinas);
                    if ($stmt->execute()) {
                        $message = "Dinas berhasil dihapus.";
                        $message_type = "success";
                        header("location: manage_dinas.php?status_msg=success&msg=" . urlencode($message));
                        exit;
                    } else {
                        $message = "Gagal menghapus dinas: " . $stmt->error;
                        $message_type = "danger";
                    }
                    $stmt->close();
                } else {
                    $message = "Gagal menyiapkan statement: " . $mysqli->error;
                    $message_type = "danger";
                }
            }
        }
    }
}

// Ambil semua dinas
$dinas_data = [];
$sql = "SELECT id_dinas, nama_dinas FROM dinas ORDER BY nama_dinas ASC";
if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $dinas_data[] = $row;
    }
    $result->free();
} else {
    $message = "Gagal mengambil data dinas: " . $mysqli->error;
    $message_type = "danger";
}

?>

<div class="container-fluid py-4">
    <h2 class="mb-4">Manajemen Data Dinas</h2>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            Daftar Dinas
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddEditDinas" data-action="add">
                <i class="fas fa-plus"></i> Tambah Dinas
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dinasTable">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Dinas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dinas_data)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada data dinas ditemukan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($dinas_data as $dinas): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($dinas['nama_dinas']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info me-1"
                                            data-bs-toggle="modal" data-bs-target="#modalAddEditDinas"
                                            data-action="edit"
                                            data-id="<?php echo htmlspecialchars($dinas['id_dinas']); ?>"
                                            data-nama_dinas="<?php echo htmlspecialchars($dinas['nama_dinas']); ?>"
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            data-bs-toggle="modal" data-bs-target="#modalDeleteDinas"
                                            data-id="<?php echo htmlspecialchars($dinas['id_dinas']); ?>"
                                            data-nama_dinas="<?php echo htmlspecialchars($dinas['nama_dinas']); ?>">
                                            <i class="fas fa-trash-alt"></i> Hapus
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

<div class="modal fade" id="modalAddEditDinas" tabindex="-1" aria-labelledby="modalAddEditDinasLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formAddEditDinas" action="" method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="modalAddEditDinasLabel">Tambah Dinas Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="action" id="dinas_action">
            <input type="hidden" name="id_dinas" id="dinas_id">

            <div class="mb-3">
                <label for="nama_dinas" class="form-label">Nama Dinas</label>
                <input type="text" class="form-control" id="nama_dinas" name="nama_dinas" required>
            </div>
            </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="submitDinasBtn">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalDeleteDinas" tabindex="-1" aria-labelledby="modalDeleteDinasLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="" method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="modalDeleteDinasLabel">Konfirmasi Hapus Dinas</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="delete_dinas">
          <input type="hidden" name="id_dinas" id="delete_dinas_id">
          <p>Anda yakin ingin menghapus dinas <b><span id="delete_dinas_nama"></span></b>?</p>
          <div class="alert alert-warning small mt-3">
              <i class="fas fa-exclamation-triangle"></i> Menghapus dinas akan gagal jika masih ada pegawai atau akun pengguna yang terkait dengannya.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Hapus</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>

<script>
$(document).ready(function() {
    // Inisialisasi DataTable untuk Dinas
    $('#dinasTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json"
        }
    });

    // Handle modal show event untuk Tambah/Edit Dinas
    var modalAddEditDinas = document.getElementById('modalAddEditDinas');
    modalAddEditDinas.addEventListener('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var action = button.data('action');

        var modal = $(this);
        var form = modal.find('#formAddEditDinas');
        var modalTitle = modal.find('#modalAddEditDinasLabel');

        // Reset form
        form[0].reset();
        
        if (action === 'add') {
            modalTitle.text('Tambah Dinas Baru');
            modal.find('#dinas_action').val('add_dinas');
            modal.find('#dinas_id').val('');
        } else if (action === 'edit') {
            modalTitle.text('Edit Dinas');
            modal.find('#dinas_action').val('edit_dinas');
            
            var id_dinas = button.data('id');
            var nama_dinas = button.data('nama_dinas');
            
            modal.find('#dinas_id').val(id_dinas);
            modal.find('#nama_dinas').val(nama_dinas);
            }
    });

    // Handle Delete Dinas Modal
    var modalDeleteDinas = document.getElementById('modalDeleteDinas');
    modalDeleteDinas.addEventListener('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id_dinas = button.data('id');
        var nama_dinas = button.data('nama_dinas');
        var modal = $(this);
        modal.find('#delete_dinas_id').val(id_dinas);
        modal.find('#delete_dinas_nama').text(nama_dinas);
    });
});
</script>