<?php
// manage_pegawai.php

// Aktifkan error reporting untuk debugging (HAPUS DI PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ==================================================================================================
// BAGIAN INI HARUS BERADA DI PALING ATAS FILE, SEBELUM OUTPUT APAPUN (TERMASUK HTML DARI HEADER.PHP)
// ==================================================================================================
require_once __DIR__ . "/../config.php"; // Ini akan membuat $mysqli
// 1. Pastikan session dimulai (jika belum dimulai oleh file lain yang di-include)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Include file koneksi database dan fungsi umum
// Pastikan file ini mendefinisikan $mysqli sebagai objek koneksi database

require_once __DIR__ . "/../includes/functions.php";

// 3. Inisialisasi variabel pesan (penting sebelum redirect)
$message = "";
$message_type = "";

// Ambil pesan dari URL setelah redirect (jika ada)
if (isset($_GET['status_msg']) && isset($_GET['msg'])) {
    $message_type = htmlspecialchars($_GET['status_msg']);
    $message = htmlspecialchars($_GET['msg']);
}


// 4. Cek hak akses SEBELUM ada output HTML
// Admin Dinas dan Superadmin boleh mengelola data pegawai
if (!isset($_SESSION["hak_akses"]) || !in_array($_SESSION["hak_akses"], ['Superadmin', 'Admin Dinas'])) {
    header("location: ../index.php");
    exit;
}

$hak_akses_user = $_SESSION["hak_akses"];
$dinas_user = $_SESSION["dinas"] ?? ''; // Ambil dinas user jika ada

// ----------------------------------------------------
// LOGIKA FILTER DINAS DAN FETCH DATA PEGAWAI
// ----------------------------------------------------
$filter_dinas_id = ''; // Sekarang kita akan filter berdasarkan ID Dinas
if (isset($_GET['filter_dinas_id'])) {
    $filter_dinas_id = filter_var($_GET['filter_dinas_id'], FILTER_VALIDATE_INT);
    if ($filter_dinas_id === false) {
        $filter_dinas_id = ''; // Reset jika tidak valid
    }
}


// Dapatkan daftar dinas untuk filter dropdown
// Hanya Superadmin dan Bupati yang bisa melihat semua dinas di dropdown filter
$list_dinas = [];
// Untuk saat ini, mari kita panggil langsung dari DB
$sql_dinas = "SELECT id_dinas, nama_dinas FROM dinas ORDER BY nama_dinas ASC";
if ($result_dinas = $mysqli->query($sql_dinas)) {
    while ($row_dinas = $result_dinas->fetch_assoc()) {
        $list_dinas[] = $row_dinas;
    }
    $result_dinas->free();
} else {
    error_log("Error fetching dinas list: " . $mysqli->error);
}


// Bangun query dasar untuk mengambil data pegawai
$sql_select = "
    SELECT
        p.id_pegawai,
        p.nip,
        p.nama,
        p.pangkat,
        p.golongan,
        p.jabatan,
        p.status_kepegawaian,
        p.dinas as nama_dinas_string_from_pegawai, -- Nama dinas asli dari tabel pegawai
        d.id_dinas,
        d.nama_dinas -- Nama dinas dari tabel dinas (hasil JOIN)
    FROM
        pegawai p
    LEFT JOIN
        dinas d ON p.dinas = d.nama_dinas
    WHERE 1=1
";
$params = [];
$types = "";

// Logika pembatasan berdasarkan hak akses
if ($hak_akses_user === 'Admin Dinas') {
    $sql_select .= " AND p.dinas = ?";
    $params[] = $dinas_user;
    $types .= "s";
    
    // Set filter_dinas_id untuk dropdown agar menampilkan dinasnya sendiri bagi Admin Dinas
    foreach ($list_dinas as $d) {
        if ($d['nama_dinas'] === $dinas_user) {
            $filter_dinas_id = $d['id_dinas'];
            break;
        }
    }
} else if ($hak_akses_user === 'Superadmin') {
    if (!empty($filter_dinas_id)) {
        $sql_select .= " AND d.id_dinas = ?";
        $params[] = $filter_dinas_id;
        $types .= "i";
    }
}

// Tambahkan pengurutan
$sql_select .= " ORDER BY p.nama ASC";

$pegawai_data = [];
if ($stmt = $mysqli->prepare($sql_select)) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $pegawai_data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $message = "ERROR: Could not prepare query for fetching pegawai data. " . $mysqli->error;
    $message_type = "danger";
    $pegawai_data = [];
    error_log($message);
}

// ----------------------------------------------------
// PROSES TAMBAH/EDIT/HAPUS PEGAWAI
// ----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action == 'add_pegawai' || $action == 'edit_pegawai') {
        $nip = trim($_POST['nip'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $id_dinas_selected = filter_var($_POST['id_dinas'] ?? '', FILTER_VALIDATE_INT);
        $pangkat = trim($_POST['pangkat'] ?? '');
        $golongan = trim($_POST['golongan'] ?? '');
        $jabatan = trim($_POST['jabatan'] ?? '');
        $status_kepegawaian = trim($_POST['status_kepegawaian'] ?? '');

        // Dapatkan nama dinas dari id_dinas yang dipilih
        $nama_dinas_untuk_db = '';
        foreach ($list_dinas as $d) {
            if ($d['id_dinas'] == $id_dinas_selected) {
                $nama_dinas_untuk_db = $d['nama_dinas'];
                break;
            }
        }

        // --- VALIDASI INPUT ---
        if (empty($nip) || empty($nama) || empty($nama_dinas_untuk_db)) {
            $message = "NIP, Nama, dan Dinas tidak boleh kosong.";
            $message_type = "danger";
        } else {
            if ($action == 'add_pegawai') {
                // Cek NIP duplikat di tabel pegawai
                $check_nip_sql = "SELECT id_pegawai FROM pegawai WHERE nip = ?";
                if ($stmt_check = $mysqli->prepare($check_nip_sql)) {
                    $stmt_check->bind_param("s", $nip);
                    $stmt_check->execute();
                    $stmt_check->store_result();
                    if ($stmt_check->num_rows > 0) {
                        $message = "NIP sudah terdaftar.";
                        $message_type = "danger";
                    } else {
                        $stmt_check->close();
                        // Insert ke tabel pegawai
                        $insert_sql = "INSERT INTO pegawai (nip, nama, dinas, pangkat, golongan, jabatan, status_kepegawaian) VALUES (?, ?, ?, ?, ?, ?, ?)";
                        if ($stmt = $mysqli->prepare($insert_sql)) {
                            $stmt->bind_param("sssssss", $nip, $nama, $nama_dinas_untuk_db, $pangkat, $golongan, $jabatan, $status_kepegawaian);
                            if ($stmt->execute()) {
                                $message = "Pegawai baru berhasil ditambahkan.";
                                $message_type = "success";
                                header("location: manage_pegawai.php?status_msg=$message_type&msg=" . urlencode($message));
                                exit;
                            } else {
                                $message = "Error saat menambahkan pegawai: " . $stmt->error;
                                $message_type = "danger";
                            }
                            $stmt->close();
                        } else {
                            $message = "Error: Could not prepare insert statement.";
                            $message_type = "danger";
                        }
                    }
                } else {
                    $message = "Error checking NIP: " . $mysqli->error;
                    $message_type = "danger";
                }
            } elseif ($action == 'edit_pegawai') {
                $id_pegawai = filter_var($_POST['id_pegawai'] ?? '', FILTER_VALIDATE_INT);
                if ($id_pegawai === false) {
                    $message = "ID pegawai tidak valid untuk diedit.";
                    $message_type = "danger";
                } else {
                    $update_sql = "UPDATE pegawai SET nama=?, dinas=?, pangkat=?, golongan=?, jabatan=?, status_kepegawaian=? WHERE id_pegawai=? AND nip=?";
                    if ($stmt = $mysqli->prepare($update_sql)) {
                        $stmt->bind_param("ssssssis", $nama, $nama_dinas_untuk_db, $pangkat, $golongan, $jabatan, $status_kepegawaian, $id_pegawai, $nip);
                        if ($stmt->execute()) {
                            $message = "Data pegawai berhasil diperbarui.";
                            $message_type = "success";
                            header("location: manage_pegawai.php?status_msg=$message_type&msg=" . urlencode($message));
                            exit;
                        } else {
                            $message = "Error saat memperbarui pegawai: " . $stmt->error;
                            $message_type = "danger";
                        }
                        $stmt->close();
                    } else {
                        $message = "Error: Could not prepare update statement.";
                        $message_type = "danger";
                    }
                }
            }
        }
    } elseif ($action == 'delete_pegawai') {
        // Logika hapus pegawai (hanya tabel pegawai, jangan hapus user di sini)
        $id_pegawai = filter_var($_POST['id_pegawai'], FILTER_VALIDATE_INT);

        if ($id_pegawai === false) {
            $message = "ID pegawai tidak valid.";
            $message_type = "danger";
        } else {
            // Kita perlu NIP untuk menghapus entri yang terkait di tabel absensi dan rekapitulasi_bulanan
            $nip_target = '';
            $get_nip_sql = "SELECT nip FROM pegawai WHERE id_pegawai = ?";
            if ($stmt_get_nip = $mysqli->prepare($get_nip_sql)) {
                $stmt_get_nip->bind_param("i", $id_pegawai);
                $stmt_get_nip->execute();
                $stmt_get_nip->bind_result($nip_target);
                $stmt_get_nip->fetch();
                $stmt_get_nip->close();
            }

            if (empty($nip_target)) {
                $message = "NIP tidak ditemukan untuk pegawai ini.";
                $message_type = "danger";
            } else {
                $mysqli->begin_transaction();
                try {
                    // Hapus data absensi terkait
                    $delete_absensi_sql = "DELETE FROM absensi WHERE nip = ?";
                    if (!($stmt_absensi = $mysqli->prepare($delete_absensi_sql))) {
                        throw new Exception("Error prepare delete absensi: " . $mysqli->error);
                    }
                    $stmt_absensi->bind_param("s", $nip_target);
                    if (!$stmt_absensi->execute()) {
                        throw new Exception("Error execute delete absensi: " . $stmt_absensi->error);
                    }
                    $stmt_absensi->close();

                    // Hapus data rekapitulasi bulanan terkait
                    $delete_rekap_sql = "DELETE FROM rekapitulasi_bulanan WHERE nip = ?";
                    if (!($stmt_rekap = $mysqli->prepare($delete_rekap_sql))) {
                        throw new Exception("Error prepare delete rekap: " . $mysqli->error);
                    }
                    $stmt_rekap->bind_param("s", $nip_target);
                    if (!$stmt_rekap->execute()) {
                        throw new Exception("Error execute delete rekap: " . $stmt_rekap->error);
                    }
                    $stmt_rekap->close();

                    // Hapus dari tabel pegawai
                    $delete_pegawai_sql = "DELETE FROM pegawai WHERE id_pegawai = ?";
                    if (!($stmt_pegawai = $mysqli->prepare($delete_pegawai_sql))) {
                        throw new Exception("Error prepare delete pegawai: " . $mysqli->error);
                    }
                    $stmt_pegawai->bind_param("i", $id_pegawai);
                    if (!$stmt_pegawai->execute()) {
                        throw new Exception("Error execute delete pegawai: " . $stmt_pegawai->error);
                    }
                    $stmt_pegawai->close();

                    // PENTING: User terkait (di tabel `users`) TIDAK dihapus dari sini.
                    // Ini harus dikelola di `manage_users.php` untuk menghindari penghapusan akun yang tidak disengaja.

                    $mysqli->commit();
                    $message = "Data pegawai dan data absensi/rekap terkait berhasil dihapus.";
                    $message_type = "success";
                    header("location: manage_pegawai.php?status_msg=$message_type&msg=" . urlencode($message));
                    exit;

                } catch (Exception $e) {
                    $mysqli->rollback();
                    $message = "Transaksi gagal: " . $e->getMessage();
                    $message_type = "danger";
                }
            }
        }
    }
}

$page_title = "Kelola Data Pegawai"; // Judul halaman
require_once __DIR__ . "/../includes/header.php";
?>

<div class="container-fluid py-4">
    <h2>Kelola Data Pegawai</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Filter Pegawai</h5>
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET" class="row g-3 align-items-end">
                <?php if ($hak_akses_user === 'Superadmin'): // Hanya Superadmin yang bisa melihat semua dinas di dropdown filter ?>
                <div class="col-md-4">
                    <label for="filter_dinas_id" class="form-label">Dinas</label>
                    <select class="form-select" id="filter_dinas_id" name="filter_dinas_id">
                        <option value="">-- Semua Dinas --</option>
                        <?php foreach ($list_dinas as $dinas_item): ?>
                            <option value="<?php echo htmlspecialchars($dinas_item['id_dinas']); ?>" <?php echo ((int)$filter_dinas_id === (int)$dinas_item['id_dinas']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dinas_item['nama_dinas']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: // Admin Dinas hanya akan melihat dinasnya sendiri, tidak ada filter dropdown ?>
                    <div class="col-md-4">
                        <label class="form-label">Dinas</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($dinas_user); ?>" disabled>
                        <input type="hidden" name="filter_dinas_id" value="<?php echo htmlspecialchars($filter_dinas_id); ?>">
                    </div>
                <?php endif; ?>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                    <a href="manage_pegawai.php" class="btn btn-secondary">Reset Filter</a>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAddEditPegawai" data-action="add_pegawai">
            <i class="fas fa-plus"></i> Tambah Pegawai Baru
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Daftar Pegawai</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>NIP</th>
                            <th>Nama</th>
                            <th>Dinas</th>
                            <th>Pangkat</th>
                            <th>Golongan</th>
                            <th>Jabatan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pegawai_data)): ?>
                            <?php foreach ($pegawai_data as $pegawai): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pegawai['nip'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($pegawai['nama'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($pegawai['nama_dinas'] ?? ''); // Tampilkan nama dinas dari JOIN dinas ?></td>
                                    <td><?php echo htmlspecialchars($pegawai['pangkat'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($pegawai['golongan'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($pegawai['jabatan'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($pegawai['status_kepegawaian'] ?? ''); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info me-1"
                                            data-bs-toggle="modal" data-bs-target="#modalAddEditPegawai" data-action="edit_pegawai"
                                            data-id="<?php echo htmlspecialchars($pegawai['id_pegawai'] ?? ''); ?>"
                                            data-nip="<?php echo htmlspecialchars($pegawai['nip'] ?? ''); ?>"
                                            data-nama="<?php echo htmlspecialchars($pegawai['nama'] ?? ''); ?>"
                                            data-id_dinas="<?php echo htmlspecialchars($pegawai['id_dinas'] ?? ''); // Kirim ID dinas ?>"
                                            data-pangkat="<?php echo htmlspecialchars($pegawai['pangkat'] ?? ''); ?>"
                                            data-golongan="<?php echo htmlspecialchars($pegawai['golongan'] ?? ''); ?>"
                                            data-jabatan="<?php echo htmlspecialchars($pegawai['jabatan'] ?? ''); ?>"
                                            data-status_kepegawaian="<?php echo htmlspecialchars($pegawai['status_kepegawaian'] ?? ''); ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            data-bs-toggle="modal" data-bs-target="#modalDeletePegawai"
                                            data-id="<?php echo htmlspecialchars($pegawai['id_pegawai'] ?? ''); ?>"
                                            data-nama="<?php echo htmlspecialchars($pegawai['nama'] ?? ''); ?>">
                                            <i class="fas fa-trash"></i> Hapus Data
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada data pegawai yang ditemukan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddEditPegawai" tabindex="-1" aria-labelledby="modalAddEditPegawaiLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formAddEditPegawai" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="modalAddEditPegawaiLabel">Tambah Pegawai Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="action" id="pegawai_action" value="add_pegawai">
            <input type="hidden" name="id_pegawai" id="pegawai_id">

            <div class="mb-3">
                <label for="nip" class="form-label">NIP</label>
                <input type="text" class="form-control" id="nip" name="nip" required>
            </div>
            <div class="mb-3">
                <label for="nama" class="form-label">Nama</label>
                <input type="text" class="form-control" id="nama" name="nama" required>
            </div>
            <div class="mb-3">
                <label for="id_dinas" class="form-label">Dinas</label>
                <select class="form-select" id="id_dinas" name="id_dinas" required>
                    <option value="">Pilih Dinas</option>
                    <?php foreach ($list_dinas as $dinas_item): ?>
                        <option value="<?php echo htmlspecialchars($dinas_item['id_dinas']); ?>">
                            <?php echo htmlspecialchars($dinas_item['nama_dinas']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="pangkat" class="form-label">Pangkat</label>
                <input type="text" class="form-control" id="pangkat" name="pangkat">
            </div>
            <div class="mb-3">
                <label for="golongan" class="form-label">Golongan</label>
                <input type="text" class="form-control" id="golongan" name="golongan">
            </div>
            <div class="mb-3">
                <label for="jabatan" class="form-label">Jabatan</label>
                <input type="text" class="form-control" id="jabatan" name="jabatan">
            </div>
            <div class="mb-3">
                <label for="status_kepegawaian" class="form-label">Status Kepegawaian</label>
                <select class="form-select" id="status_kepegawaian" name="status_kepegawaian" required>
                    <option value="PNS">PNS</option>
                    <option value="PPPK">PPPK</option>
                </select>
            </div>
            </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalDeletePegawai" tabindex="-1" aria-labelledby="modalDeletePegawaiLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="modalDeletePegawaiLabel">Hapus Data Pegawai</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="delete_pegawai">
          <input type="hidden" name="id_pegawai" id="delete_pegawai_id">
          <p>Anda yakin ingin menghapus data pegawai <b><span id="delete_nama_pegawai"></span></b>? Ini juga akan menghapus semua data absensi dan rekapitulasi bulanan terkait pegawai ini. **Ini tidak akan menghapus akun login pegawai tersebut. Hapus akun di menu 'Kelola Pengguna'.**</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Hapus Data</button>
        </div>
      </form>
    </div>
  </div>
</div>


<?php
require_once __DIR__ . "/../includes/footer.php";
?>

<script>
$(document).ready(function() {
    // Handle Add/Edit Pegawai Modal
    var modalAddEditPegawai = document.getElementById('modalAddEditPegawai');
    modalAddEditPegawai.addEventListener('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var action = button.data('action');
        var modalTitle = $(this).find('.modal-title');
        var form = $(this).find('#formAddEditPegawai');
        form[0].reset(); // Reset form saat modal dibuka

        if (action === 'add_pegawai') {
            modalTitle.text('Tambah Pegawai Baru');
            $(this).find('#pegawai_action').val('add_pegawai');
            $(this).find('#pegawai_id').val('');
            $(this).find('#nip').prop('readonly', false); // NIP bisa diisi saat tambah baru
            // Set dinas dropdown ke opsi default atau kosong
            $(this).find('#id_dinas').val(''); 
        } else if (action === 'edit_pegawai') {
            modalTitle.text('Edit Data Pegawai');
            $(this).find('#pegawai_action').val('edit_pegawai');
            
            var id_pegawai = button.data('id');
            var nip = button.data('nip');
            var nama = button.data('nama');
            var id_dinas = button.data('id_dinas'); // Ambil ID dinas
            var pangkat = button.data('pangkat');
            var golongan = button.data('golongan');
            var jabatan = button.data('jabatan');
            var status_kepegawaian = button.data('status_kepegawaian');

            $(this).find('#pegawai_id').val(id_pegawai);
            $(this).find('#nip').val(nip).prop('readonly', true); // NIP tidak bisa diubah saat edit
            $(this).find('#nama').val(nama);
            $(this).find('#id_dinas').val(id_dinas); // Set ID dinas di dropdown
            $(this).find('#pangkat').val(pangkat);
            $(this).find('#golongan').val(golongan);
            $(this).find('#jabatan').val(jabatan);
            $(this).find('#status_kepegawaian').val(status_kepegawaian);
        }
    });

    // Handle Delete Pegawai Modal
    var modalDeletePegawai = document.getElementById('modalDeletePegawai');
    modalDeletePegawai.addEventListener('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id_pegawai = button.data('id');
        var nama_pegawai = button.data('nama');
        var modal = $(this);
        modal.find('#delete_pegawai_id').val(id_pegawai);
        modal.find('#delete_nama_pegawai').text(nama_pegawai);
    });
});
</script>