<?php
// Aktifkan error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../config.php"; // Pastikan $mysqli sudah tersedia
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/header.php";

// Cek hak akses (hanya Superadmin & Admin Dinas)
if (!isset($_SESSION["hak_akses"]) || !in_array($_SESSION["hak_akses"], ['Superadmin', 'Admin Dinas'])) {
    header("location: ../index.php");
    exit;
}

$hak_akses_user = $_SESSION["hak_akses"];
$dinas_user = $_SESSION["dinas"] ?? '';

$message = "";
$message_type = "";

// Ambil daftar dinas untuk dropdown
$list_dinas = [];
$sql_dinas = "SELECT id_dinas, nama_dinas FROM dinas ORDER BY nama_dinas ASC";
if ($result_dinas = $mysqli->query($sql_dinas)) {
    while ($row = $result_dinas->fetch_assoc()) {
        $list_dinas[] = $row;
    }
    $result_dinas->free();
}

// Handle form submit (tambah honorer)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_honorer'])) {
    $nama = trim($_POST['nama'] ?? '');
    $id_dinas = filter_var($_POST['id_dinas'] ?? '', FILTER_VALIDATE_INT);
    $jabatan = trim($_POST['jabatan'] ?? '');

    // Ambil nama dinas dari id_dinas
    $nama_dinas_db = '';
    foreach ($list_dinas as $d) {
        if ((int)$d['id_dinas'] === (int)$id_dinas) {
            $nama_dinas_db = $d['nama_dinas'];
            break;
        }
    }

    if (empty($nama) || empty($nama_dinas_db) || empty($jabatan)) {
        $message = "Nama, Dinas, dan Jabatan tidak boleh kosong.";
        $message_type = "danger";
    } else {
        // Insert ke tabel pegawai dengan status_kepegawaian = Honor
        $sql = "INSERT INTO pegawai (nama, dinas, jabatan, status_kepegawaian) VALUES (?, ?, ?, 'Honor')";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("sss", $nama, $nama_dinas_db, $jabatan);
            if ($stmt->execute()) {
                $message = "Pegawai honorer berhasil ditambahkan.";
                $message_type = "success";
            } else {
                $message = "Gagal menambah pegawai honorer: " . $stmt->error;
                $message_type = "danger";
            }
            $stmt->close();
        } else {
            $message = "Gagal prepare statement: " . $mysqli->error;
            $message_type = "danger";
        }
    }
}

// Query data pegawai honorer
$where = "WHERE p.status_kepegawaian = 'Honor'";
$params = [];
$types = "";

if ($hak_akses_user === 'Admin Dinas') {
    $where .= " AND p.dinas = ?";
    $params[] = $dinas_user;
    $types .= "s";
}
$sql_select = "
    SELECT p.id_pegawai, p.nama, p.dinas, p.jabatan, d.id_dinas, d.nama_dinas
    FROM pegawai p
    LEFT JOIN dinas d ON p.dinas = d.nama_dinas
    $where
    ORDER BY p.nama ASC
";
$honorer_list = [];
if ($stmt = $mysqli->prepare($sql_select)) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $honorer_list = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kelola Pegawai Honorer</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4">Kelola Pegawai Honorer</h2>
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <strong>Tambah Pegawai Honorer</strong>
        </div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="add_honorer" value="1">
                <div class="col-md-4">
                    <label class="form-label">Nama</label>
                    <input type="text" name="nama" class="form-control" maxlength="80" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Dinas</label>
                    <?php if ($hak_akses_user === 'Admin Dinas'): ?>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($dinas_user) ?>" disabled>
                        <input type="hidden" name="id_dinas"
                            value="<?php
                                foreach ($list_dinas as $d) {
                                    if ($d['nama_dinas'] === $dinas_user) {
                                        echo htmlspecialchars($d['id_dinas']);
                                        break;
                                    }
                                }
                            ?>">
                    <?php else: ?>
                        <select class="form-select" name="id_dinas" required>
                            <option value="">Pilih Dinas</option>
                            <?php foreach ($list_dinas as $d): ?>
                                <option value="<?= htmlspecialchars($d['id_dinas']) ?>"><?= htmlspecialchars($d['nama_dinas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jabatan</label>
                    <input type="text" name="jabatan" class="form-control" maxlength="80" required>
                </div>
                <div class="col-12">
                    <button class="btn btn-success" type="submit">Tambah Honorer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <strong>Daftar Pegawai Honorer</strong>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Dinas</th>
                            <th>Jabatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($honorer_list)): ?>
                            <?php foreach ($honorer_list as $h): ?>
                                <tr>
                                    <td><?= htmlspecialchars($h['nama'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($h['dinas'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($h['jabatan'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center text-danger">Belum ada pegawai honorer.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>