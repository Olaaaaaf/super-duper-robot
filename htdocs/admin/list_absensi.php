<?php
// Aktifkan error reporting untuk debugging (HAPUS DI PRODUCTION SAAT LIVE)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ==================================================================================================
// BAGIAN INI HARUS BERADA DI PALING ATAS FILE, SEBELUM OUTPUT APAPUN (TERMASUK HTML DARI HEADER.PHP)
// ==================================================================================================

// 1. Include file fungsi umum (INI HARUS PERTAMA KALI DIMUAT)
require_once __DIR__ . "/../includes/functions.php";

// 2. MUAT CONFIG.PHP HANYA SEKALI DI SINI
require_once __DIR__ . "/../config.php";

// 3. Pastikan session dimulai (penting untuk $_SESSION)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 4. Include file koneksi database
require_once __DIR__ . "/../includes/db_connection.php";

// Set judul halaman sebelum memuat header.php
$page_title = "Daftar Absensi Pegawai";

// Muat header.php (pastikan ini di-include setelah semua logika PHP awal selesai)
require_once __DIR__ . "/../includes/header.php";

// Inisialisasi variabel pesan
$message = "";
$message_type = "";

// Ambil pesan dari URL setelah redirect (jika ada)
if (isset($_GET['status_msg']) && isset($_GET['msg'])) {
    $message_type = htmlspecialchars($_GET['status_msg']);
    $message = htmlspecialchars($_GET['msg']);
}

// Pastikan user memiliki hak akses yang sesuai
if (!in_array($_SESSION["hak_akses"], ['Superadmin', 'Admin Dinas', 'Bendahara Dinas', 'Bupati', 'Kepala Dinas'])) {
    header("location: ../index.php");
    exit;
}

$hak_akses_user = $_SESSION["hak_akses"];
$dinas_user = $_SESSION["dinas"] ?? '';

$absensi_data = [];
$query_params = [];
$query_types = "";

$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');
$filter_nip = isset($_GET['filter_nip']) ? trim($_GET['filter_nip']) : '';
$filter_dinas = isset($_GET['filter_dinas']) ? trim($_GET['filter_dinas']) : '';
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';

// Ambil daftar dinas untuk dropdown dari tabel dinas (BUKAN dari pegawai)
$list_dinas = [];
$sql_dinas = "SELECT nama_dinas FROM dinas ORDER BY nama_dinas ASC";
if ($result_dinas = $mysqli->query($sql_dinas)) {
    while ($row_dinas = $result_dinas->fetch_assoc()) {
        $list_dinas[] = $row_dinas['nama_dinas'];
    }
    $result_dinas->free();
}

// --- START EXPORT CSV LOGIC ---
if (isset($_GET['export_csv']) && $_GET['export_csv'] == '1') {
    $filename = "absensi_pegawai_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, array('NIP', 'Nama Pegawai', 'Dinas', 'Tanggal', 'Jam Masuk', 'Jam Pulang', 'Status Kehadiran', 'Catatan', 'Lampiran'));

    $sql_export = "SELECT a.nip, p.nama, p.dinas, a.tanggal, a.jam_masuk, a.jam_pulang, a.keterangan AS status_kehadiran, a.catatan, a.lampiran_izin_sakit AS lampiran
                   FROM absensi a
                   JOIN pegawai p ON a.nip = p.nip ";
    $where_clauses_export = [];
    $export_params = [];
    $export_types = "";

    if ($filter_date) {
        $where_clauses_export[] = "a.tanggal = ?";
        $export_params[] = $filter_date;
        $export_types .= "s";
    }
    if ($filter_nip) {
        $where_clauses_export[] = "a.nip LIKE ?";
        $export_params[] = "%" . $filter_nip . "%";
        $export_types .= "s";
    }
    if ($filter_dinas) {
        $where_clauses_export[] = "p.dinas = ?";
        $export_params[] = $filter_dinas;
        $export_types .= "s";
    }
    if ($filter_status && $filter_status != 'Semua Status') {
        $where_clauses_export[] = "a.keterangan = ?";
        $export_params[] = $filter_status;
        $export_types .= "s";
    }

    if ($hak_akses_user == 'Admin Dinas' || $hak_akses_user == 'Bendahara Dinas' || $hak_akses_user == 'Kepala Dinas') {
        $where_clauses_export[] = "p.dinas = ?";
        $export_params[] = $dinas_user;
        $export_types .= "s";
    }

    if (!empty($where_clauses_export)) {
        $sql_export .= " WHERE " . implode(" AND ", $where_clauses_export);
    }
    $sql_export .= " ORDER BY a.tanggal DESC, p.nama ASC";

    $stmt_export = $mysqli->prepare($sql_export);
    if ($stmt_export) {
        if (!empty($export_params)) {
            $stmt_export->bind_param($export_types, ...$export_params);
        }
        $stmt_export->execute();
        $result_export = $stmt_export->get_result();

        while ($row = $result_export->fetch_assoc()) {
            fputcsv($output, $row);
        }
        $stmt_export->close();
    } else {
        error_log("Error preparing export statement: " . $mysqli->error);
    }
    fclose($output);
    exit;
}
// --- END EXPORT CSV LOGIC ---

// --- Pagination Setup ---
$records_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Build base query for counting total records
$count_query = "SELECT COUNT(a.id_absensi) FROM absensi a JOIN pegawai p ON a.nip = p.nip";
$where_clauses_count = [];
$count_params = [];
$count_types = "";

if ($filter_date) {
    $where_clauses_count[] = "a.tanggal = ?";
    $count_params[] = $filter_date;
    $count_types .= "s";
}
if ($filter_nip) {
    $where_clauses_count[] = "a.nip LIKE ?";
    $count_params[] = "%" . $filter_nip . "%";
    $count_types .= "s";
}
if ($filter_dinas) {
    $where_clauses_count[] = "p.dinas = ?";
    $count_params[] = $filter_dinas;
    $count_types .= "s";
}
if ($filter_status && $filter_status != 'Semua Status') {
    $where_clauses_count[] = "a.keterangan = ?";
    $count_params[] = $filter_status;
    $count_types .= "s";
}

if ($hak_akses_user == 'Admin Dinas' || $hak_akses_user == 'Bendahara Dinas' || $hak_akses_user == 'Kepala Dinas') {
    $where_clauses_count[] = "p.dinas = ?";
    $count_params[] = $dinas_user;
    $count_types .= "s";
}

if (!empty($where_clauses_count)) {
    $count_query .= " WHERE " . implode(" AND ", $where_clauses_count);
}

$stmt_count = $mysqli->prepare($count_query);
if ($stmt_count === false) {
    die("Error preparing count query: " . $mysqli->error);
}

if (!empty($count_params)) {
    $stmt_count->bind_param($count_types, ...$count_params);
}
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_records = $result_count->fetch_row()[0];
$stmt_count->close();

$total_pages = ceil($total_records / $records_per_page);

// Build base query for fetching actual data
$sql = "SELECT a.id_absensi AS id, a.tanggal, a.jam_masuk, a.jam_pulang, a.keterangan AS status_kehadiran, a.catatan, a.lampiran_izin_sakit AS lampiran, p.nip, p.nama, p.dinas, p.jabatan
        FROM absensi a
        JOIN pegawai p ON a.nip = p.nip";
$where_clauses_select = [];
$select_params = [];
$select_types = "";

if ($filter_date) {
    $where_clauses_select[] = "a.tanggal = ?";
    $select_params[] = $filter_date;
    $select_types .= "s";
}
if ($filter_nip) {
    $where_clauses_select[] = "a.nip LIKE ?";
    $select_params[] = "%" . $filter_nip . "%";
    $select_types .= "s";
}
if ($filter_dinas) {
    $where_clauses_select[] = "p.dinas = ?";
    $select_params[] = $filter_dinas;
    $select_types .= "s";
}
if ($filter_status && $filter_status != 'Semua Status') {
    $where_clauses_select[] = "a.keterangan = ?";
    $select_params[] = $filter_status;
    $select_types .= "s";
}

if ($hak_akses_user == 'Admin Dinas' || $hak_akses_user == 'Bendahara Dinas' || $hak_akses_user == 'Kepala Dinas') {
    $where_clauses_select[] = "p.dinas = ?";
    $select_params[] = $dinas_user;
    $select_types .= "s";
}

if (!empty($where_clauses_select)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses_select);
}
$sql .= " ORDER BY a.tanggal DESC, p.nama ASC LIMIT ? OFFSET ?";
$select_params[] = $records_per_page;
$select_params[] = $offset;
$select_types .= "ii";

$stmt = $mysqli->prepare($sql);
if ($stmt === false) {
    die("Error preparing select query: " . $mysqli->error);
}

if (!empty($select_params)) {
    $stmt->bind_param($select_types, ...$select_params);
}
$stmt->execute();
$result = $stmt->get_result();
$absensi_data = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<div class="container-fluid py-4">
    <h2 class="mb-4 text-primary">Daftar Absensi Pegawai</h2>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Absensi</h6>
        </div>
        <div class="card-body">
            <form action="" method="GET" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="filter_date" class="form-label">Tanggal:</label>
                        <input type="date" class="form-control" id="filter_date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="filter_nip" class="form-label">NIP / Nama:</label>
                        <input type="text" class="form-control" id="filter_nip" name="filter_nip" value="<?php echo htmlspecialchars($filter_nip); ?>" placeholder="Cari NIP atau Nama">
                    </div>
                    <?php if ($hak_akses_user == 'Superadmin'): ?>
                    <div class="col-md-3">
                        <label for="filter_dinas" class="form-label">Dinas:</label>
                        <select class="form-select" id="filter_dinas" name="filter_dinas">
                            <option value="">Semua Dinas</option>
                            <?php foreach ($list_dinas as $dinas): ?>
                                <option value="<?php echo htmlspecialchars($dinas); ?>" <?php echo ($filter_dinas == $dinas) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dinas); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <label for="filter_status" class="form-label">Status Kehadiran:</label>
                        <select class="form-select" id="filter_status" name="filter_status">
                            <option value="Semua Status" <?php echo ($filter_status == 'Semua Status') ? 'selected' : ''; ?>>Semua Status</option>
                            <option value="Hadir" <?php echo ($filter_status == 'Hadir') ? 'selected' : ''; ?>>Hadir</option>
                            <option value="Terlambat" <?php echo ($filter_status == 'Terlambat') ? 'selected' : ''; ?>>Terlambat</option>
                            <option value="Izin" <?php echo ($filter_status == 'Izin') ? 'selected' : ''; ?>>Izin</option>
                            <option value="Sakit" <?php echo ($filter_status == 'Sakit') ? 'selected' : ''; ?>>Sakit</option>
                            <option value="Cuti" <?php echo ($filter_status == 'Cuti') ? 'selected' : ''; ?>>Cuti</option>
                            <option value="Dinas Luar" <?php echo ($filter_status == 'Dinas Luar') ? 'selected' : ''; ?>>Dinas Luar</option>
                            <option value="Alpha" <?php echo ($filter_status == 'Alpha') ? 'selected' : ''; ?>>Alpha</option>
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
                        <a href="list_absensi.php" class="btn btn-secondary"><i class="fas fa-sync-alt me-1"></i> Reset</a>
                        <a href="list_absensi.php?export_csv=1&filter_date=<?php echo htmlspecialchars($filter_date); ?>&filter_nip=<?php echo htmlspecialchars($filter_nip); ?>&filter_dinas=<?php echo htmlspecialchars($filter_dinas); ?>&filter_status=<?php echo htmlspecialchars($filter_status); ?>" class="btn btn-success"><i class="fas fa-file-excel me-1"></i> Export CSV</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Data Absensi</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if (empty($absensi_data)): ?>
                    <div class="alert alert-info text-center" role="alert">
                        Tidak ada data absensi yang ditemukan untuk filter yang dipilih.
                    </div>
                <?php else: ?>
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>NIP</th>
                            <th>Nama Pegawai</th>
                            <th>Dinas</th>
                            <th>Jabatan</th>
                            <th>Tanggal</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th>Status</th>
                            <th>Catatan</th>
                            <th>Lampiran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($absensi_data as $absensi): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($absensi['nip']); ?></td>
                                <td><?php echo htmlspecialchars($absensi['nama']); ?></td>
                                <td><?php echo htmlspecialchars($absensi['dinas']); ?></td>
                                <td><?php echo htmlspecialchars($absensi['jabatan']); ?></td>
                                <td><?php echo htmlspecialchars(date('d F Y', strtotime($absensi['tanggal']))); ?></td>
                                <td><?php echo htmlspecialchars($absensi['jam_masuk']); ?></td>
                                <td><?php echo htmlspecialchars($absensi['jam_pulang']); ?></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch ($absensi['status_kehadiran']) {
                                        case 'Hadir':
                                            $status_class = 'badge bg-success';
                                            break;
                                        case 'Terlambat':
                                            $status_class = 'badge bg-warning text-dark';
                                            break;
                                        case 'Izin':
                                        case 'Sakit':
                                        case 'Cuti':
                                        case 'Dinas Luar':
                                            $status_class = 'badge bg-info';
                                            break;
                                        case 'Alpha':
                                            $status_class = 'badge bg-danger';
                                            break;
                                        default:
                                            $status_class = 'badge bg-secondary';
                                            break;
                                    }
                                    ?>
                                    <span class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($absensi['status_kehadiran']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($absensi['catatan']); ?></td>
                                <td>
                                    <?php if ($absensi['lampiran']): ?>
                                        <a href="<?php echo htmlspecialchars(UPLOAD_DIR_BUKTI . $absensi['lampiran']); ?>" target="_blank" class="btn btn-sm btn-info">Lihat</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit_absensi.php?id=<?php echo $absensi['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="delete_absensi.php?id=<?php echo $absensi['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Anda yakin ingin menghapus data absensi ini?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <nav aria-label="Page navigation example">
                <ul class="pagination justify-content-center">
                    <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?>&filter_date=<?php echo htmlspecialchars($filter_date); ?>&filter_nip=<?php echo htmlspecialchars($filter_nip); ?>&filter_dinas=<?php echo htmlspecialchars($filter_dinas); ?>&filter_status=<?php echo htmlspecialchars($filter_status); ?>">Sebelumnya</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&filter_date=<?php echo htmlspecialchars($filter_date); ?>&filter_nip=<?php echo htmlspecialchars($filter_nip); ?>&filter_dinas=<?php echo htmlspecialchars($filter_dinas); ?>&filter_status=<?php echo htmlspecialchars($filter_status); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?>&filter_date=<?php echo htmlspecialchars($filter_date); ?>&filter_nip=<?php echo htmlspecialchars($filter_nip); ?>&filter_dinas=<?php echo htmlspecialchars($filter_dinas); ?>&filter_status=<?php echo htmlspecialchars($filter_status); ?>">Selanjutnya</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>