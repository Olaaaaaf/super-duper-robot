<?php
// manage_users.php

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
require_once __DIR__ . "/../includes/functions.php"; // Ini penting untuk hash_password, dll.

// 3. Inisialisasi variabel pesan (penting sebelum redirect)
$message = "";
$message_type = "";

// Ambil pesan dari URL setelah redirect (jika ada)
if (isset($_GET['status_msg']) && isset($_GET['msg'])) {
    $message_type = htmlspecialchars($_GET['status_msg']);
    $message = htmlspecialchars($_GET['msg']);
}


// 4. Cek hak akses SEBELUM ada output HTML
// Hanya Superadmin yang boleh mengelola user (hak akses)
if (!isset($_SESSION["hak_akses"]) || $_SESSION["hak_akses"] !== 'Superadmin') {
    header("location: ../index.php");
    exit;
}

$page_title = "Manajemen Pengguna Aplikasi"; // Set judul halaman
require_once __DIR__ . "/../includes/header.php"; // Muat header HTML

$hak_akses_user = $_SESSION["hak_akses"];
$id_user_login = $_SESSION["id_user"]; // ID user yang sedang login

// Proses form Tambah/Edit Pengguna
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user' || $action === 'edit_user') {
        $id_user = filter_var($_POST['id_user'] ?? null, FILTER_VALIDATE_INT);
        $id_pegawai = filter_var($_POST['id_pegawai'], FILTER_VALIDATE_INT);
        $username = trim($_POST['username']);
        $hak_akses = trim($_POST['hak_akses']);
        $status_akun = trim($_POST['status_akun']);
        $password = trim($_POST['password'] ?? ''); // Password hanya jika ditambahkan atau direset

        // Validasi dasar
        if (empty($username) || empty($hak_akses) || empty($status_akun) || $id_pegawai === false) {
            $message = "Semua field wajib diisi.";
            $message_type = "danger";
        } else {
            // Cek apakah username sudah ada (kecuali saat edit user itu sendiri)
            $sql_check_username = "SELECT id_user FROM users WHERE username = ?";
            if ($action === 'edit_user' && $id_user) {
                $sql_check_username .= " AND id_user != ?";
            }
            if ($stmt_check = $mysqli->prepare($sql_check_username)) {
                if ($action === 'edit_user' && $id_user) {
                    $stmt_check->bind_param("si", $username, $id_user);
                } else {
                    $stmt_check->bind_param("s", $username);
                }
                $stmt_check->execute();
                $stmt_check->store_result();
                if ($stmt_check->num_rows > 0) {
                    $message = "Username ini sudah digunakan. Mohon pilih username lain.";
                    $message_type = "danger";
                    $stmt_check->close();
                } else {
                    $stmt_check->close(); // Tutup statement check username

                    if ($action === 'add_user') {
                        // Tambah User Baru
                        if (empty($password)) {
                            $message = "Password wajib diisi untuk user baru.";
                            $message_type = "danger";
                        } else {
                            // Hashing password (gunakan fungsi hash_password dari functions.php)
                            $hashed_password = hash_password($password);
                            $sql = "INSERT INTO users (id_pegawai, username, password, hak_akses, status_akun) VALUES (?, ?, ?, ?, ?)";
                            if ($stmt = $mysqli->prepare($sql)) {
                                $stmt->bind_param("issss", $id_pegawai, $username, $hashed_password, $hak_akses, $status_akun);
                                if ($stmt->execute()) {
                                    $message = "Pengguna berhasil ditambahkan.";
                                    $message_type = "success";
                                    // Redirect untuk menghindari form resubmission
                                    header("location: manage_users.php?status_msg=success&msg=" . urlencode($message));
                                    exit;
                                } else {
                                    $message = "Gagal menambahkan pengguna: " . $stmt->error;
                                    $message_type = "danger";
                                }
                                $stmt->close();
                            } else {
                                $message = "Gagal menyiapkan statement: " . $mysqli->error;
                                $message_type = "danger";
                            }
                        }
                    } elseif ($action === 'edit_user' && $id_user) {
                        // Edit User
                        $sql = "UPDATE users SET id_pegawai = ?, username = ?, hak_akses = ?, status_akun = ? WHERE id_user = ?";
                        if (!empty($password)) { // Jika password diisi, update juga passwordnya
                            $hashed_password = hash_password($password);
                            $sql = "UPDATE users SET id_pegawai = ?, username = ?, password = ?, hak_akses = ?, status_akun = ? WHERE id_user = ?";
                        }

                        if ($stmt = $mysqli->prepare($sql)) {
                            if (!empty($password)) {
                                $stmt->bind_param("issssi", $id_pegawai, $username, $hashed_password, $hak_akses, $status_akun, $id_user);
                            } else {
                                $stmt->bind_param("isssi", $id_pegawai, $username, $hak_akses, $status_akun, $id_user);
                            }

                            if ($stmt->execute()) {
                                $message = "Pengguna berhasil diperbarui.";
                                $message_type = "success";
                                // Jika user mengedit akunnya sendiri, update sesi
                                if ($id_user == $id_user_login) {
                                    $_SESSION["username"] = $username;
                                    $_SESSION["hak_akses"] = $hak_akses;
                                }
                                header("location: manage_users.php?status_msg=success&msg=" . urlencode($message));
                                exit;
                            } else {
                                $message = "Gagal memperbarui pengguna: " . $stmt->error;
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
                $message = "Gagal menyiapkan statement cek username: " . $mysqli->error;
                $message_type = "danger";
            }
        }
    } elseif ($action === 'delete_user') {
        $id_user = filter_var($_POST['id_user'], FILTER_VALIDATE_INT);

        if ($id_user === false) {
            $message = "ID pengguna tidak valid.";
            $message_type = "danger";
        } elseif ($id_user == $id_user_login) { // Mencegah user menghapus akunnya sendiri
            $message = "Anda tidak dapat menghapus akun Anda sendiri.";
            $message_type = "danger";
        } else {
            $sql = "DELETE FROM users WHERE id_user = ?";
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("i", $id_user);
                if ($stmt->execute()) {
                    $message = "Pengguna berhasil dihapus.";
                    $message_type = "success";
                    header("location: manage_users.php?status_msg=success&msg=" . urlencode($message));
                    exit;
                } else {
                    $message = "Gagal menghapus pengguna: " . $stmt->error;
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

// Ambil semua pengguna dari tabel 'users'
$users = [];
// Join dengan tabel pegawai untuk menampilkan nama pegawai terkait
$sql = "SELECT u.id_user, u.id_pegawai, u.username, u.hak_akses, u.status_akun, p.nama AS nama_pegawai
        FROM users u
        LEFT JOIN pegawai p ON u.id_pegawai = p.id_pegawai";
if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
} else {
    $message = "Gagal mengambil data pengguna: " . $mysqli->error;
    $message_type = "danger";
}

// Ambil daftar pegawai yang belum memiliki akun user (untuk form tambah)
$available_pegawai = [];
$sql_pegawai = "SELECT id_pegawai, nip, nama FROM pegawai WHERE id_pegawai NOT IN (SELECT id_pegawai FROM users WHERE id_pegawai IS NOT NULL)";
if ($result_pegawai = $mysqli->query($sql_pegawai)) {
    while ($row_pegawai = $result_pegawai->fetch_assoc()) {
        $available_pegawai[] = $row_pegawai;
    }
    $result_pegawai->free();
} else {
    $message = "Gagal mengambil daftar pegawai: " . $mysqli->error;
    $message_type = "danger";
}

?>

<div class="container-fluid py-4">
    <h2 class="mb-4">Manajemen Pengguna Aplikasi</h2>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            Daftar Pengguna Aplikasi
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddEditUser" data-action="add">
                <i class="fas fa-plus"></i> Tambah Pengguna
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="usersTable">
                    <thead class="table-light">
                        <tr>
                            <th>ID User</th>
                            <th>Username</th>
                            <th>Nama Pegawai Terkait</th>
                            <th>Hak Akses</th>
                            <th>Status Akun</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada pengguna ditemukan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id_user']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['nama_pegawai'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($user['hak_akses']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($user['status_akun'] === 'Aktif') ? 'success' : 'danger'; ?>">
                                            <?php echo htmlspecialchars($user['status_akun']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info me-1"
                                            data-bs-toggle="modal" data-bs-target="#modalAddEditUser"
                                            data-action="edit"
                                            data-id="<?php echo htmlspecialchars($user['id_user']); ?>"
                                            data-id_pegawai="<?php echo htmlspecialchars($user['id_pegawai']); ?>"
                                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                            data-hak_akses="<?php echo htmlspecialchars($user['hak_akses']); ?>"
                                            data-status_akun="<?php echo htmlspecialchars($user['status_akun']); ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <?php if ($user['id_user'] != $id_user_login): // Tidak bisa menghapus akun sendiri ?>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal" data-bs-target="#modalDeleteUser"
                                                data-id="<?php echo htmlspecialchars($user['id_user']); ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                <i class="fas fa-trash-alt"></i> Hapus
                                            </button>
                                        <?php endif; ?>
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

<div class="modal fade" id="modalAddEditUser" tabindex="-1" aria-labelledby="modalAddEditUserLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formAddEditUser" action="" method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="modalAddEditUserLabel">Tambah Pengguna Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="action" id="user_action">
            <input type="hidden" name="id_user" id="user_id">

            <div class="mb-3">
                <label for="id_pegawai" class="form-label">Nama Pegawai Terkait</label>
                <select class="form-select" id="id_pegawai" name="id_pegawai" required>
                    <option value="">Pilih Pegawai</option>
                    <?php foreach ($available_pegawai as $pegawai): ?>
                        <option value="<?php echo htmlspecialchars($pegawai['id_pegawai']); ?>"><?php echo htmlspecialchars($pegawai['nama']) . " (" . htmlspecialchars($pegawai['nip']) . ")"; ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Pilih pegawai yang akan dihubungkan dengan akun pengguna ini. Hanya pegawai yang belum memiliki akun yang akan muncul.</div>
            </div>

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" autocomplete="new-password">
                <div class="form-text" id="passwordHelp">Kosongkan jika tidak ingin mengubah password saat edit. Wajib diisi saat menambah user baru.</div>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password">
                <div class="invalid-feedback d-block" id="passwordMatchFeedback" style="display: none;"></div>
            </div>
            <div class="mb-3">
                <label for="hak_akses" class="form-label">Hak Akses</label>
                <select class="form-select" id="hak_akses" name="hak_akses" required>
                    <option value="Superadmin">Superadmin</option>
                    <option value="Admin Dinas">Admin Dinas</option>
                    <option value="Bendahara Dinas">Bendahara Dinas</option>
                    <option value="Bupati">Bupati</option>
                    <option value="Pegawai">Pegawai</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="status_akun" class="form-label">Status Akun</label>
                <select class="form-select" id="status_akun" name="status_akun" required>
                    <option value="Aktif">Aktif</option>
                    <option value="Nonaktif">Nonaktif</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="submitUserBtn">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalDeleteUser" tabindex="-1" aria-labelledby="modalDeleteUserLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="" method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="modalDeleteUserLabel">Konfirmasi Hapus Pengguna</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="delete_user">
          <input type="hidden" name="id_user" id="delete_user_id">
          <p>Anda yakin ingin menghapus pengguna <b><span id="delete_username"></span></b>?</p>
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
    // Inisialisasi DataTable
    $('#usersTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json"
        }
    });

    // Handle modal show event untuk Tambah/Edit User
    var modalAddEditUser = document.getElementById('modalAddEditUser');
    modalAddEditUser.addEventListener('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var action = button.data('action');

        var modal = $(this);
        var form = modal.find('#formAddEditUser');
        var modalTitle = modal.find('#modalAddEditUserLabel');
        var passwordInput = modal.find('#password');
        var confirmPasswordInput = modal.find('#confirm_password');
        var passwordHelp = modal.find('#passwordHelp');
        var passwordMatchFeedback = modal.find('#passwordMatchFeedback');
        var submitBtn = modal.find('#submitUserBtn');
        var idPegawaiSelect = modal.find('#id_pegawai');

        // Reset form
        form[0].reset();
        passwordInput.attr('required', false); // Default tidak required
        passwordHelp.show();
        passwordInput.removeClass('is-invalid');
        confirmPasswordInput.removeClass('is-invalid');
        passwordMatchFeedback.hide().text('');
        submitBtn.prop('disabled', false); // Enable tombol submit secara default

        if (action === 'add') {
            modalTitle.text('Tambah Pengguna Baru');
            modal.find('#user_action').val('add_user');
            modal.find('#user_id').val('');
            passwordInput.attr('required', true); // Password wajib untuk user baru
            idPegawaiSelect.val(''); // Pastikan select kosong
            idPegawaiSelect.prop('disabled', false); // Aktifkan select
        } else if (action === 'edit') {
            modalTitle.text('Edit Pengguna');
            modal.find('#user_action').val('edit_user');
            
            var id_user = button.data('id');
            var id_pegawai = button.data('id_pegawai');
            var username = button.data('username');
            var hak_akses = button.data('hak_akses');
            var status_akun = button.data('status_akun');

            modal.find('#user_id').val(id_user);
            modal.find('#id_pegawai').val(id_pegawai);
            modal.find('#username').val(username);
            modal.find('#hak_akses').val(hak_akses);
            modal.find('#status_akun').val(status_akun);
            
            passwordInput.attr('required', false); // Password tidak wajib saat edit
            passwordHelp.text('Kosongkan jika tidak ingin mengubah password.');
            
            // Nonaktifkan pilihan pegawai jika sedang edit, karena sudah terhubung
            idPegawaiSelect.val(id_pegawai); // Set nilai terpilih
            idPegawaiSelect.prop('disabled', true); // Nonaktifkan
        }

        // Event listener untuk validasi password real-time
        passwordInput.on('keyup', function() { validatePasswords(passwordInput, confirmPasswordInput, passwordMatchFeedback, submitBtn); });
        confirmPasswordInput.on('keyup', function() { validatePasswords(passwordInput, confirmPasswordInput, passwordMatchFeedback, submitBtn); });
        
        // Initial check in case fields are pre-filled or something
        validatePasswords(passwordInput, confirmPasswordInput, passwordMatchFeedback, submitBtn);
    });

    // Fungsi validasi password
    function validatePasswords(newPass, confirmPass, helpBlock, submitBtn) {
        newPass.removeClass('is-invalid');
        confirmPass.removeClass('is-invalid');
        helpBlock.hide().text('');

        var newPassVal = newPass.val().trim();
        var confirmPassVal = confirmPass.val().trim();
        var isAddAction = $('#user_action').val() === 'add_user';

        if (isAddAction && newPassVal === '') {
            newPass.addClass('is-invalid');
            helpBlock.text('Password baru tidak boleh kosong.').show();
            submitBtn.prop('disabled', true);
        } else if (isAddAction && confirmPassVal === '') {
            confirmPass.addClass('is-invalid');
            helpBlock.text('Konfirmasi password tidak boleh kosong.').show();
            submitBtn.prop('disabled', true);
        } else if (newPassVal !== confirmPassVal && newPassVal !== '' && confirmPassVal !== '') {
            helpBlock.text('Password tidak cocok.').show();
            newPass.addClass('is-invalid');
            confirmPass.addClass('is-invalid');
            submitBtn.prop('disabled', true);
        } else {
            submitBtn.prop('disabled', false);
        }
    }


    // Handle Delete User Modal
    var modalDeleteUser = document.getElementById('modalDeleteUser');
    modalDeleteUser.addEventListener('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id_user = button.data('id');
        var username = button.data('username');
        var modal = $(this);
        modal.find('#delete_user_id').val(id_user);
        modal.find('#delete_username').text(username);
    });
});
</script>