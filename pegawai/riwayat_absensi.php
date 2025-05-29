<?php
$page_title = "Riwayat Absensi Pegawai";
require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/functions.php";

if ($_SESSION["hak_akses"] !== 'Pegawai') {
    header("location: ../index.php");
    exit;
}

$nip_pegawai = $_SESSION["nip"];

$bulan_selected = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('n');
$tahun_selected = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

$absensi_data = [];

$sql_riwayat_absensi = "SELECT tanggal, jam_masuk, jam_pulang, keterangan, lampiran_izin_sakit, status_izin
                        FROM absensi
                        WHERE nip = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?
                        ORDER BY tanggal DESC";

if ($stmt = $mysqli->prepare($sql_riwayat_absensi)) {
    $stmt->bind_param("sii", $nip_pegawai, $bulan_selected, $tahun_selected);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $absensi_data[] = $row;
    }
    $stmt->close();
}
?>

<div class="wrapper">
    <h2 class="mb-4">Riwayat Absensi Pegawai</h2>

    <div class="form-group row mb-4">
        <label for="filterBulan" class="col-sm-2 col-form-label">Filter Bulan/Tahun:</label>
        <div class="col-sm-4">
            <select id="filterBulan" class="form-control" onchange="applyFilter()">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo ($m == $bulan_selected) ? 'selected' : ''; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $m, 10)); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-sm-4">
            <select id="filterTahun" class="form-control" onchange="applyFilter()">
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): // 5 tahun ke belakang ?>
                    <option value="<?php echo $y; ?>" <?php echo ($y == $tahun_selected) ? 'selected' : ''; ?>>
                        <?php echo $y; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
    </div>

    <?php if (empty($absensi_data)): ?>
        <div class="alert alert-info text-center">Tidak ada data absensi untuk bulan/tahun yang dipilih.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>Tanggal</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Keterangan</th>
                        <th>Status Izin</th>
                        <th>Lampiran</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($absensi_data as $row): ?>
                    <tr>
                        <td><?php echo format_tanggal($row['tanggal']); ?></td>
                        <td><?php echo format_waktu($row['jam_masuk']); ?></td>
                        <td><?php echo format_waktu($row['jam_pulang']); ?></td>
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
                                default:
                                    $status_color = 'badge-info'; // Untuk "Hadir"
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

<script>
function applyFilter() {
    const bulan = document.getElementById('filterBulan').value;
    const tahun = document.getElementById('filterTahun').value;
    window.location.href = `riwayat_absensi.php?bulan=${bulan}&tahun=${tahun}`;
}
</script>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>