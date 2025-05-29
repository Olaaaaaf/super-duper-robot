<?php
// Aktifkan error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page_title = "Rekapitulasi Absensi Bulanan";
require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/functions.php";

// Pastikan user memiliki hak akses yang sesuai
if (!in_array($_SESSION["hak_akses"], ['Superadmin', 'Bendahara Dinas', 'Bupati'])) {
    header("location: ../index.php");
    exit;
}

$hak_akses_user = $_SESSION["hak_akses"];
// Perbaikan: Gunakan null coalescing operator untuk menghindari undefined index jika dinas tidak ada
$dinas_user = $_SESSION["dinas"] ?? ''; 

$bulan_selected = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('n');
$tahun_selected = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
$filter_dinas = isset($_GET['filter_dinas']) ? trim($_GET['filter_dinas']) : '';

$rekap_data = [];
$message = "";
$message_type = "";

// Dapatkan daftar dinas untuk filter (jika Superadmin/Bupati)
$list_dinas = [];
if ($hak_akses_user === 'Superadmin' || $hak_akses_user === 'Bupati') {
    $sql_dinas = "SELECT DISTINCT dinas FROM pegawai ORDER BY dinas ASC";
    if ($result_dinas = $mysqli->query($sql_dinas)) {
        while ($row_dinas = $result_dinas->fetch_assoc()) {
            $list_dinas[] = $row_dinas['dinas'];
        }
        $result_dinas->free();
    } else {
        $message = "Error fetching dinas list: " . $mysqli->error;
        $message_type = "danger";
    }
}

// --- START EXPORT CSV LOGIC ---
if (isset($_GET['export_csv']) && $_GET['export_csv'] == '1') {
    $total_hari_kerja_efektif_bulan_ini = get_hari_kerja_efektif($mysqli, $bulan_selected, $tahun_selected);

    $sql_export_rekap = "SELECT p.nip, p.nama, p.dinas, p.jabatan
                         FROM pegawai p
                         WHERE 1=1 ";

    $export_rekap_params = [];
    $export_rekap_types = "";

    if ($hak_akses_user === 'Bendahara Dinas') {
        $sql_export_rekap .= " AND p.dinas = ? ";
        $export_rekap_params[] = $dinas_user;
        $export_rekap_types .= "s";
    } elseif ($hak_akses_user === 'Superadmin' || $hak_akses_user === 'Bupati') {
        if (!empty($filter_dinas)) {
            $sql_export_rekap .= " AND p.dinas = ? ";
            $export_rekap_params[] = $filter_dinas;
            $export_rekap_types .= "s";
        }
    }
    $sql_export_rekap .= " ORDER BY p.dinas, p.nama";

    $filename = "rekap_absensi_" . date('Ym', mktime(0,0,0,$bulan_selected, 1, $tahun_selected)) . "_" . date('His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');

    // CSV Header
    fputcsv($output, [
        'NIP', 'Nama', 'Dinas', 'Jabatan', 'Hadir', 'Alpha', 'Izin', 'Sakit',
        'Cuti', 'Dinas Luar', '% Kehadiran', 'Potongan TPP'
    ]);

    if ($stmt_export_rekap = $mysqli->prepare($sql_export_rekap)) {
        if (!empty($export_rekap_types)) {
            $stmt_export_rekap->bind_param($export_rekap_types, ...$export_rekap_params);
        }
        $stmt_export_rekap->execute();
        $result_export_rekap = $stmt_export_rekap->get_result();

        while ($pegawai = $result_export_rekap->fetch_assoc()) {
            $nip = $pegawai['nip'];
            $rekap_entry = [
                'nip' => $nip,
                'nama' => $pegawai['nama'],
                'dinas' => $pegawai['dinas'],
                'jabatan' => $pegawai['jabatan'],
                'total_hadir' => 0,
                'total_alpha' => 0,
                'total_izin' => 0,
                'total_sakit' => 0,
                'total_cuti' => 0,
                'total_dinas_luar' => 0,
                'persentase_kehadiran' => 0.00,
                'potongan_tpp' => 0.00
            ];

            $sql_absensi_pegawai_export = "SELECT tanggal, keterangan, status_izin, jam_masuk
                                           FROM absensi
                                           WHERE nip = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?";
            if ($stmt_absensi_export = $mysqli->prepare($sql_absensi_pegawai_export)) {
                $stmt_absensi_export->bind_param("sii", $nip, $bulan_selected, $tahun_selected);
                $stmt_absensi_export->execute();
                $result_absensi_export = $stmt_absensi_export->get_result();

                while ($absensi = $result_absensi_export->fetch_assoc()) {
                    $keterangan = $absensi['keterangan'];
                    $status_izin = $absensi['status_izin'];
                    $jam_masuk_actual = $absensi['jam_masuk'];

                    switch ($keterangan) {
                        case 'Hadir':
                            $rekap_entry['total_hadir']++;
                            if (!empty($jam_masuk_actual) && $jam_masuk_actual !== '00:00:00') {
                                // PASTIKAN pemanggilan is_terlambat() memiliki 3 argumen
                                if (defined('JAM_MASUK_ABSEN') && defined('TOLERANSI_KETERLAMBATAN_ACTUAL')) {
                                    if (is_terlambat(JAM_MASUK_ABSEN, $jam_masuk_actual, TOLERANSI_KETERLAMBATAN_ACTUAL)) {
                                        // Implementasi potongan keterlambatan jika ada
                                        if (defined('POTONGAN_TERLAMBAT_ACTUAL')) {
                                            $rekap_entry['potongan_tpp'] += POTONGAN_TERLAMBAT_ACTUAL;
                                        }
                                    }
                                }
                            }
                            break;
                        case 'Alpha':
                            $rekap_entry['total_alpha']++;
                            if (defined('POTONGAN_ALPHA_ACTUAL')) {
                                $rekap_entry['potongan_tpp'] += POTONGAN_ALPHA_ACTUAL;
                            }
                            break;
                        case 'Izin':
                            if ($status_izin === 'Disetujui') {
                                $rekap_entry['total_izin']++;
                                if (defined('POTONGAN_IZIN_ACTUAL')) {
                                    $rekap_entry['potongan_tpp'] += POTONGAN_IZIN_ACTUAL;
                                }
                            } elseif ($status_izin === 'Ditolak') {
                                $rekap_entry['total_alpha']++; // Dianggap alpha jika ditolak
                                if (defined('POTONGAN_TIDAK_SETUJU')) {
                                    $rekap_entry['potongan_tpp'] += POTONGAN_TIDAK_SETUJU;
                                }
                            }
                            break;
                        case 'Sakit':
                            if ($status_izin === 'Disetujui') {
                                $rekap_entry['total_sakit']++;
                                if (defined('POTONGAN_SAKIT_ACTUAL')) {
                                    $rekap_entry['potongan_tpp'] += POTONGAN_SAKIT_ACTUAL;
                                }
                            } elseif ($status_izin === 'Ditolak') {
                                $rekap_entry['total_alpha']++;
                                if (defined('POTONGAN_TIDAK_SETUJU')) {
                                    $rekap_entry['potongan_tpp'] += POTONGAN_TIDAK_SETUJU;
                                }
                            }
                            break;
                        case 'Cuti':
                            if ($status_izin === 'Disetujui') {
                                $rekap_entry['total_cuti']++;
                                if (defined('POTONGAN_CUTI_ACTUAL')) { // Jika cuti ada potongan
                                    $rekap_entry['potongan_tpp'] += POTONGAN_CUTI_ACTUAL;
                                }
                            } elseif ($status_izin === 'Ditolak') {
                                $rekap_entry['total_alpha']++;
                                if (defined('POTONGAN_TIDAK_SETUJU')) {
                                    $rekap_entry['potongan_tpp'] += POTONGAN_TIDAK_SETUJU;
                                }
                            }
                            break;
                        case 'Dinas Luar':
                            if ($status_izin === 'Disetujui') {
                                $rekap_entry['total_dinas_luar']++;
                                if (defined('POTONGAN_DINAS_LUAR_ACTUAL')) { // Jika dinas luar ada potongan
                                    $rekap_entry['potongan_tpp'] += POTONGAN_DINAS_LUAR_ACTUAL;
                                }
                            } elseif ($status_izin === 'Ditolak') {
                                $rekap_entry['total_alpha']++;
                                if (defined('POTONGAN_TIDAK_SETUJU')) {
                                    $rekap_entry['potongan_tpp'] += POTONGAN_TIDAK_SETUJU;
                                }
                            }
                            break;
                    }
                }
                $stmt_absensi_export->close();
            }
            // Sisa perhitungan tetap sama...
            if ($total_hari_kerja_efektif_bulan_ini > 0) {
                $hadir_total_export = $rekap_entry['total_hadir'] + $rekap_entry['total_izin'] + $rekap_entry['total_sakit'] + $rekap_entry['total_cuti'] + $rekap_entry['total_dinas_luar'];
                $rekap_entry['persentase_kehadiran'] = ($hadir_total_export / $total_hari_kerja_efektif_bulan_ini) * 100;
            } else {
                $rekap_entry['persentase_kehadiran'] = 0;
            }
            $rekap_entry['potongan_tpp'] = min(1.00, $rekap_entry['potongan_tpp']); // Pastikan tidak lebih dari 100%

            fputcsv($output, [
                htmlspecialchars($rekap_entry['nip']),
                htmlspecialchars($rekap_entry['nama']),
                htmlspecialchars($rekap_entry['dinas']),
                htmlspecialchars($rekap_entry['jabatan']),
                $rekap_entry['total_hadir'],
                $rekap_entry['total_alpha'],
                $rekap_entry['total_izin'],
                $rekap_entry['total_sakit'],
                $rekap_entry['total_cuti'],
                $rekap_entry['total_dinas_luar'],
                number_format($rekap_entry['persentase_kehadiran'], 2) . '%',
                number_format($rekap_entry['potongan_tpp'] * 100, 2) . '%'
            ]);
        }
        $stmt_export_rekap->close();
    }

    fclose($output);
    exit();
}
// --- END EXPORT CSV LOGIC ---

// Hitung hari kerja efektif untuk bulan dan tahun ini
$total_hari_kerja_efektif_bulan_ini = get_hari_kerja_efektif($mysqli, $bulan_selected, $tahun_selected);

$sql_rekap = "SELECT p.nip, p.nama, p.dinas, p.jabatan
              FROM pegawai p
              WHERE 1=1 ";

$rekap_params = [];
$rekap_types = "";

if ($hak_akses_user === 'Bendahara Dinas') {
    $sql_rekap .= " AND p.dinas = ? ";
    $rekap_params[] = $dinas_user;
    $rekap_types .= "s";
} elseif ($hak_akses_user === 'Superadmin' || $hak_akses_user === 'Bupati') {
    if (!empty($filter_dinas)) {
        $sql_rekap .= " AND p.dinas = ? ";
        $rekap_params[] = $filter_dinas;
        $rekap_types .= "s";
    }
}
$sql_rekap .= " ORDER BY p.dinas, p.nama";

if ($stmt_pegawai = $mysqli->prepare($sql_rekap)) {
    if (!empty($rekap_types)) {
        $stmt_pegawai->bind_param($rekap_types, ...$rekap_params);
    }
    $stmt_pegawai->execute();
    $result_pegawai = $stmt_pegawai->get_result();

    while ($pegawai = $result_pegawai->fetch_assoc()) {
        $nip = $pegawai['nip'];
        $rekap_entry = [
            'nip' => $nip,
            'nama' => $pegawai['nama'],
            'dinas' => $pegawai['dinas'],
            'jabatan' => $pegawai['jabatan'],
            'total_hadir' => 0,
            'total_alpha' => 0,
            'total_izin' => 0,
            'total_sakit' => 0,
            'total_cuti' => 0,
            'total_dinas_luar' => 0,
            'persentase_kehadiran' => 0.00,
            'potongan_tpp' => 0.00
        ];

        // Hitung absensi untuk pegawai ini pada bulan terpilih
        $sql_absensi_pegawai = "SELECT tanggal, keterangan, status_izin, jam_masuk
                                FROM absensi
                                WHERE nip = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?";
        if ($stmt_absensi = $mysqli->prepare($sql_absensi_pegawai)) {
            $stmt_absensi->bind_param("sii", $nip, $bulan_selected, $tahun_selected);
            $stmt_absensi->execute();
            $result_absensi = $stmt_absensi->get_result();

            while ($absensi = $result_absensi->fetch_assoc()) {
                $keterangan = $absensi['keterangan'];
                $status_izin = $absensi['status_izin'];
                $jam_masuk_actual = $absensi['jam_masuk'];

                switch ($keterangan) {
                    case 'Hadir':
                        $rekap_entry['total_hadir']++;
                        // Cek keterlambatan jika ada jam masuk
                        if (!empty($jam_masuk_actual) && $jam_masuk_actual !== '00:00:00') {
                            // PASTIKAN pemanggilan is_terlambat() memiliki 3 argumen
                            if (defined('JAM_MASUK_ABSEN') && defined('TOLERANSI_KETERLAMBATAN_ACTUAL')) {
                                if (is_terlambat(JAM_MASUK_ABSEN, $jam_masuk_actual, TOLERANSI_KETERLAMBATAN_ACTUAL)) {
                                    // Jika terlambat, bisa dihitung sebagai potongan tambahan
                                    if (defined('POTONGAN_TERLAMBAT_ACTUAL')) {
                                        $rekap_entry['potongan_tpp'] += POTONGAN_TERLAMBAT_ACTUAL;
                                    }
                                }
                            }
                        }
                        break;
                    case 'Alpha':
                        $rekap_entry['total_alpha']++;
                        if (defined('POTONGAN_ALPHA_ACTUAL')) {
                            $rekap_entry['potongan_tpp'] += POTONGAN_ALPHA_ACTUAL;
                        }
                        break;
                    case 'Izin':
                        if ($status_izin === 'Disetujui') {
                            $rekap_entry['total_izin']++;
                            if (defined('POTONGAN_IZIN_ACTUAL')) {
                                $rekap_entry['potongan_tpp'] += POTONGAN_IZIN_ACTUAL;
                            }
                        } elseif ($status_izin === 'Ditolak') {
                            $rekap_entry['total_alpha']++;
                            if (defined('POTONGAN_TIDAK_SETUJU')) {
                                $rekap_entry['potongan_tpp'] += POTONGAN_TIDAK_SETUJU;
                            }
                        }
                        break;
                    case 'Sakit':
                        if ($status_izin === 'Disetujui') {
                            $rekap_entry['total_sakit']++;
                            if (defined('POTONGAN_SAKIT_ACTUAL')) {
                                $rekap_entry['potongan_tpp'] += POTONGAN_SAKIT_ACTUAL;
                            }
                        } elseif ($status_izin === 'Ditolak') {
                            $rekap_entry['total_alpha']++;
                            if (defined('POTONGAN_TIDAK_SETUJU')) {
                                $rekap_entry['potongan_tpp'] += POTONGAN_TIDAK_SETUJU;
                            }
                        }
                        break;
                    case 'Cuti':
                        if ($status_izin === 'Disetujui') {
                            $rekap_entry['total_cuti']++;
                            if (defined('POTONGAN_CUTI_ACTUAL')) {
                                $rekap_entry['potongan_tpp'] += POTONGAN_CUTI_ACTUAL;
                            }
                        } elseif ($status_izin === 'Ditolak') {
                            $rekap_entry['total_alpha']++;
                            if (defined('POTONGAN_TIDAK_SETUJU')) {
                                $rekap_entry['potongan_tpp'] += POTONGAN_TIDAK_SETUJU;
                            }
                        }
                        break;
                    case 'Dinas Luar':
                        if ($status_izin === 'Disetujui') {
                            $rekap_entry['total_dinas_luar']++;
                            if (defined('POTONGAN_DINAS_LUAR_ACTUAL')) {
                                $rekap_entry['potongan_tpp'] += POTONGAN_DINAS_LUAR_ACTUAL;
                            }
                        } elseif ($status_izin === 'Ditolak') {
                            $rekap_entry['total_alpha']++;
                            if (defined('POTONGAN_TIDAK_SETUJU')) {
                                $rekap_entry['potongan_tpp'] += POTONGAN_TIDAK_SETUJU;
                            }
                        }
                        break;
                }
            }
            $stmt_absensi->close();
        }

        // Hitung persentase kehadiran
        // total_absensi_tercatat tidak digunakan dalam perhitungan persentase kehadiran, tapi bisa dipertimbangkan
        // $total_absensi_tercatat = $rekap_entry['total_hadir'] + $rekap_entry['total_izin'] + $rekap_entry['total_sakit'] + $rekap_entry['total_cuti'] + $rekap_entry['total_dinas_luar'] + $rekap_entry['total_alpha'];

        if ($total_hari_kerja_efektif_bulan_ini > 0) {
            $hadir_total = $rekap_entry['total_hadir'] + $rekap_entry['total_izin'] + $rekap_entry['total_sakit'] + $rekap_entry['total_cuti'] + $rekap_entry['total_dinas_luar'];
            $rekap_entry['persentase_kehadiran'] = ($hadir_total / $total_hari_kerja_efektif_bulan_ini) * 100;
        } else {
            $rekap_entry['persentase_kehadiran'] = 0;
        }

        $rekap_entry['potongan_tpp'] = min(1.00, $rekap_entry['potongan_tpp']);

        $rekap_data[] = $rekap_entry;
    }
    $stmt_pegawai->close();
}

// Menyimpan rekapitulasi bulanan ke database (opsional, jika ingin mengaktifkan)
foreach ($rekap_data as $data) {
    $sql_insert_update_rekap = "INSERT INTO rekapitulasi_bulanan (nip, bulan, tahun, total_hadir, total_alpha, total_izin, total_sakit, total_cuti, total_dinas_luar, total_hari_kerja, persentase_kehadiran, potongan_tpp)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE
                                total_hadir = VALUES(total_hadir), total_alpha = VALUES(total_alpha), total_izin = VALUES(total_izin),
                                total_sakit = VALUES(total_sakit), total_cuti = VALUES(total_cuti), total_dinas_luar = VALUES(total_dinas_luar),
                                total_hari_kerja = VALUES(total_hari_kerja), persentase_kehadiran = VALUES(persentase_kehadiran), potongan_tpp = VALUES(potongan_tpp)";
    if ($stmt_rekap = $mysqli->prepare($sql_insert_update_rekap)) {
        $stmt_rekap->bind_param("siiiiiiiiidd",
            $data['nip'], $bulan_selected, $tahun_selected,
            $data['total_hadir'], $data['total_alpha'], $data['total_izin'], $data['total_sakit'], $data['total_cuti'], $data['total_dinas_luar'],
            $total_hari_kerja_efektif_bulan_ini, $data['persentase_kehadiran'], $data['potongan_tpp']
        );
        if (!$stmt_rekap->execute()) {
            error_log("Error saving rekap to DB for NIP " . $data['nip'] . ": " . $stmt_rekap->error);
        }
        $stmt_rekap->close();
    } else {
        error_log("Error preparing rekapitulasi_bulanan insert query: " . $mysqli->error);
    }
}

?>

<div class="wrapper">
    <h2 class="mb-4">Rekapitulasi Absensi Bulanan</h2>
    <p class="lead">Total Hari Kerja Efektif Bulan Ini (Di luar Sabtu/Minggu dan Hari Libur Nasional): <b><?php echo $total_hari_kerja_efektif_bulan_ini; ?> hari</b></p>

    <div class="card mb-4">
        <div class="card-header">Filter Rekapitulasi</div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="form-inline">
                <div class="form-group mr-3 mb-2">
                    <label for="filterBulan" class="mr-2">Bulan:</label>
                    <select id="filterBulan" name="bulan" class="form-control">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo ($m == $bulan_selected) ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $m, 10)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group mr-3 mb-2">
                    <label for="filterTahun" class="mr-2">Tahun:</label>
                    <select id="filterTahun" name="tahun" class="form-control">
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo ($y == $tahun_selected) ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <?php if ($hak_akses_user === 'Superadmin' || $hak_akses_user === 'Bupati'): ?>
                <div class="form-group mr-3 mb-2">
                    <label for="filter_dinas" class="mr-2">Dinas:</label>
                    <select class="form-control" id="filter_dinas" name="filter_dinas">
                        <option value="">Semua Dinas</option>
                        <?php foreach ($list_dinas as $dinas_opt): ?>
                            <option value="<?php echo htmlspecialchars($dinas_opt); ?>" <?php echo ($dinas_opt == $filter_dinas) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dinas_opt); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary mb-2"><i class="fas fa-filter"></i> Filter</button>
                <a href="rekap_bulanan.php" class="btn btn-secondary mb-2 ml-2"><i class="fas fa-sync-alt"></i> Reset</a>
                <button type="submit" class="btn btn-success mb-2 ml-2" name="export_csv" value="1"><i class="fas fa-file-excel"></i> Export CSV</button>
            </form>
        </div>
    </div>

    <?php if (empty($rekap_data)): ?>
        <div class="alert alert-info text-center">Tidak ada data rekapitulasi untuk kriteria yang dipilih.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>NIP</th>
                        <th>Nama</th>
                        <th>Dinas</th>
                        <th>Jabatan</th>
                        <th>Hadir</th>
                        <th>Alpha</th>
                        <th>Izin</th>
                        <th>Sakit</th>
                        <th>Cuti</th>
                        <th>Dinas Luar</th>
                        <th>% Kehadiran</th>
                        <th>Potongan TPP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rekap_data as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['nip']); ?></td>
                        <td><?php echo htmlspecialchars($row['nama']); ?></td>
                        <td><?php echo htmlspecialchars($row['dinas']); ?></td>
                        <td><?php echo htmlspecialchars($row['jabatan']); ?></td>
                        <td><?php echo $row['total_hadir']; ?></td>
                        <td><?php echo $row['total_alpha']; ?></td>
                        <td><?php echo $row['total_izin']; ?></td>
                        <td><?php echo $row['total_sakit']; ?></td>
                        <td><?php echo $row['total_cuti']; ?></td>
                        <td><?php echo $row['total_dinas_luar']; ?></td>
                        <td><?php echo number_format($row['persentase_kehadiran'], 2); ?>%</td>
                        <td><?php echo number_format($row['potongan_tpp'] * 100, 2); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>