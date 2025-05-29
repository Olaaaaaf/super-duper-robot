<?php
// --- Inisialisasi, koneksi, session, dsb ---
session_start();
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db_connection.php";

$hak_akses_user = $_SESSION["hak_akses"] ?? '';
$dinas_user     = $_SESSION["dinas"] ?? '';

$bulan_selected  = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('n');
$tahun_selected  = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
$filter_dinas    = isset($_GET['filter_dinas']) ? trim($_GET['filter_dinas']) : '';

$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan_selected, $tahun_selected);

// --- Hari libur nasional ---
$hari_libur_nasional = [];
$sql_libur = "SELECT tanggal FROM hari_libur WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?";
if ($stmt_libur = $mysqli->prepare($sql_libur)) {
    $stmt_libur->bind_param("ii", $bulan_selected, $tahun_selected);
    $stmt_libur->execute();
    $result_libur = $stmt_libur->get_result();
    while ($row_libur = $result_libur->fetch_assoc()) {
        $hari_libur_nasional[] = $row_libur['tanggal'];
    }
    $stmt_libur->close();
}
function is_libur_full($tanggal, $hari_libur_nasional) {
    $hari = date('N', strtotime($tanggal));
    if ($hari == 6 || $hari == 7) return true; // Sabtu/Minggu
    if (in_array($tanggal, $hari_libur_nasional)) return true;
    return false;
}

// --- Ambil daftar dinas untuk filter ---
$list_dinas = [];
$sql_dinas = "SELECT nama_dinas FROM dinas ORDER BY nama_dinas ASC";
if ($result_dinas = $mysqli->query($sql_dinas)) {
    while ($row_dinas = $result_dinas->fetch_assoc()) {
        $list_dinas[] = $row_dinas['nama_dinas'];
    }
    $result_dinas->free();
}

// --- Query pegawai sesuai filter ---
$sql_rekap = "SELECT p.nip, p.nama, p.dinas, p.jabatan FROM pegawai p WHERE 1=1 ";
$rekap_params = [];
$rekap_types  = "";

if ($hak_akses_user === 'Bendahara Dinas') {
    $sql_rekap .= " AND p.dinas = ? ";
    $rekap_params[] = $dinas_user;
    $rekap_types   .= "s";
} elseif ($hak_akses_user === 'Superadmin' || $hak_akses_user === 'Bupati') {
    if (!empty($filter_dinas)) {
        $sql_rekap .= " AND p.dinas = ? ";
        $rekap_params[] = $filter_dinas;
        $rekap_types   .= "s";
    }
}
$sql_rekap .= " ORDER BY p.dinas, p.nama";

// --- Query absensi semua pegawai (optimasi satu query) ---
$absensi_per_pegawai_per_hari = [];
$sql_all_abs = "SELECT nip, tanggal, keterangan FROM absensi WHERE MONTH(tanggal)=? AND YEAR(tanggal)=?";
$params_all_abs = [$bulan_selected, $tahun_selected];
$types_all_abs = "ii";
if ($hak_akses_user === 'Bendahara Dinas') {
    $sql_all_abs .= " AND nip IN (SELECT nip FROM pegawai WHERE dinas = ?)";
    $params_all_abs[] = $dinas_user;
    $types_all_abs .= "s";
} elseif (($hak_akses_user === 'Superadmin' || $hak_akses_user === 'Bupati') && !empty($filter_dinas)) {
    $sql_all_abs .= " AND nip IN (SELECT nip FROM pegawai WHERE dinas = ?)";
    $params_all_abs[] = $filter_dinas;
    $types_all_abs .= "s";
}
$stmt_all_abs = $mysqli->prepare($sql_all_abs);
$stmt_all_abs->bind_param($types_all_abs, ...$params_all_abs);
$stmt_all_abs->execute();
$res_all_abs = $stmt_all_abs->get_result();
while ($row = $res_all_abs->fetch_assoc()) {
    $nip = $row['nip'];
    $tglx = (int)date('j', strtotime($row['tanggal']));
    $absensi_per_pegawai_per_hari[$nip][$tglx] = $row['keterangan'];
}
$stmt_all_abs->close();

// --- Blok EXPORT CSV, tampilkan format berbeda ---
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rekap_bulanan_'.date('Ymd_His').'.csv"');
    $output = fopen('php://output', 'w');

    // Header khusus csv: contoh (NO,NIP,NAMA,dsb, lalu 01,02,...,31, lalu total)
    $csv_header = ['NO','NIP','NAMA','JABATAN'];
    // Tambah header tanggal
    for ($tgl=1; $tgl<=$jumlah_hari; $tgl++) {
        $csv_header[] = sprintf('%02d', $tgl);
    }
    $csv_header = array_merge($csv_header, ['HADIR','ALPHA','IZIN','SAKIT','CUTI','DINAS LUAR','HARI EFEKTIF','% KEHADIRAN']);
    fputcsv($output, $csv_header);

    // Data pegawai
    $no = 1;
    if ($stmt_pegawai = $mysqli->prepare($sql_rekap)) {
        if (!empty($rekap_types)) {
            $stmt_pegawai->bind_param($rekap_types, ...$rekap_params);
        }
        $stmt_pegawai->execute();
        $result_pegawai = $stmt_pegawai->get_result();
        while ($pegawai = $result_pegawai->fetch_assoc()) {
            $nip = $pegawai['nip'];
            $hadir=0; $alpha=0; $izin=0; $sakit=0; $cuti=0; $dl=0; $hari_kerja=0;
            $baris = [$no++, $nip, $pegawai['nama'], $pegawai['jabatan']];
            for ($tgl=1; $tgl<=$jumlah_hari; $tgl++) {
                $tanggal = sprintf('%04d-%02d-%02d', $tahun_selected, $bulan_selected, $tgl);
                if (is_libur_full($tanggal, $hari_libur_nasional)) {
                    $baris[] = 'L';
                } else {
                    $hari_kerja++;
                    $stat = $absensi_per_pegawai_per_hari[$nip][$tgl] ?? '-';
                    $baris[] = $stat;
                    switch($stat) {
                        case 'Hadir': $hadir++; break;
                        case 'Alpha': $alpha++; break;
                        case 'Izin': $izin++; break;
                        case 'Sakit': $sakit++; break;
                        case 'Cuti': $cuti++; break;
                        case 'Dinas Luar': $dl++; break;
                    }
                }
            }
            $persen_kehadiran = $hari_kerja ? ($hadir+$izin+$sakit+$cuti+$dl)/$hari_kerja*100 : 0;
            $baris = array_merge($baris, [$hadir,$alpha,$izin,$sakit,$cuti,$dl,$hari_kerja,number_format($persen_kehadiran,2).'%']);
            fputcsv($output, $baris);
        }
        $stmt_pegawai->close();
    }
    // Tambahkan keterangan di bawah
    fputcsv($output, []);
    fputcsv($output, ['Keterangan: H = Hadir, A = Alpha, I = Izin, S = Sakit, C = Cuti, DL = Dinas Luar, L = Libur, - = Tidak Ada Data']);
    fclose($output);
    exit();
}

// --- Jika BUKAN export, tampilkan HTML seperti biasa ---
$page_title = "Rekapitulasi Bulanan";
require_once __DIR__ . "/../includes/header.php";
?>

<div class="wrapper">
    <h2 class="mb-4">Rekapitulasi Absensi Bulanan</h2>
    <form action="" method="get" class="form-inline">
        <!-- filter bulan, tahun, dinas -->
        <div class="form-group mr-3 mb-2">
            <label>Bulan:</label>
            <select name="bulan" class="form-control ml-2">
                <?php for ($m=1; $m<=12; $m++): ?>
                <option value="<?=$m?>" <?=($bulan_selected==$m)?'selected':''?>><?=date('F', mktime(0,0,0,$m,1))?></option>
                <?php endfor;?>
            </select>
        </div>
        <div class="form-group mr-3 mb-2">
            <label>Tahun:</label>
            <select name="tahun" class="form-control ml-2">
                <?php for ($y=date('Y'); $y>=date('Y')-5; $y--): ?>
                <option value="<?=$y?>" <?=($tahun_selected==$y)?'selected':''?>><?=$y?></option>
                <?php endfor;?>
            </select>
        </div>
        <div class="form-group mr-3 mb-2">
            <label>Dinas:</label>
            <select class="form-control ml-2" name="filter_dinas">
                <option value="">Semua Dinas</option>
                <?php foreach ($list_dinas as $dinas_opt): ?>
                <option value="<?=$dinas_opt?>" <?=($dinas_opt==$filter_dinas)?'selected':''?>><?=$dinas_opt?></option>
                <?php endforeach;?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary mb-2 ml-2">Filter</button>
        <a href="?bulan=<?=$bulan_selected?>&tahun=<?=$tahun_selected?>&filter_dinas=<?=$filter_dinas?>&export_csv=1" class="btn btn-success mb-2 ml-2">
            Export CSV
        </a>
    </form>

    <!-- Tabel rekap web -->
    <div class="table-responsive">
        <table class="table table-bordered table-sm" style="font-size:11px;">
            <thead>
                <tr>
                    <th>No</th><th>NIP</th><th>Nama</th><th>Jabatan</th>
                    <?php for($tgl=1;$tgl<=$jumlah_hari;$tgl++): ?>
                        <th><?=$tgl?></th>
                    <?php endfor;?>
                    <th>Hadir</th><th>Alpha</th><th>Izin</th>
                    <th>Sakit</th><th>Cuti</th><th>Dinas Luar</th>
                    <th>Hari Efektif</th><th>% Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no=1;
                if ($stmt_pegawai = $mysqli->prepare($sql_rekap)) {
                    if (!empty($rekap_types)) {
                        $stmt_pegawai->bind_param($rekap_types, ...$rekap_params);
                    }
                    $stmt_pegawai->execute();
                    $result_pegawai = $stmt_pegawai->get_result();
                    while ($pegawai = $result_pegawai->fetch_assoc()) {
                        $nip = $pegawai['nip'];
                        $hadir=0; $alpha=0; $izin=0; $sakit=0; $cuti=0; $dl=0; $hari_kerja=0;
                        echo "<tr><td>".$no++."</td>";
                        echo "<td>{$nip}</td><td>{$pegawai['nama']}</td><td>{$pegawai['jabatan']}</td>";
                        for ($tgl=1;$tgl<=$jumlah_hari;$tgl++) {
                            $tanggal = sprintf('%04d-%02d-%02d', $tahun_selected, $bulan_selected, $tgl);
                            if (is_libur_full($tanggal, $hari_libur_nasional)) {
                                echo "<td style='background:#eee;'>L</td>";
                            } else {
                                $hari_kerja++;
                                $stat = $absensi_per_pegawai_per_hari[$nip][$tgl] ?? '-';
                                $clr = "";
                                switch($stat) {
                                    case 'Hadir': $hadir++; $clr='#aaffaa'; break;
                                    case 'Alpha': $alpha++; $clr='#ffaaaa'; break;
                                    case 'Izin': $izin++; $clr='#ffe680'; break;
                                    case 'Sakit': $sakit++; $clr='#b0e0e6'; break;
                                    case 'Cuti': $cuti++; $clr='#e6b0e0'; break;
                                    case 'Dinas Luar': $dl++; $clr='#c0c0ff'; break;
                                    default: $clr='';
                                }
                                $txt = ($stat=='-'||$stat=='') ? '-' : substr($stat,0,1);
                                echo "<td style='background:$clr'>$txt</td>";
                            }
                        }
                        $persen = $hari_kerja ? ($hadir+$izin+$sakit+$cuti+$dl)/$hari_kerja*100 : 0;
                        echo "<td>$hadir</td><td>$alpha</td><td>$izin</td><td>$sakit</td><td>$cuti</td><td>$dl</td><td>$hari_kerja</td><td>".number_format($persen,2)."%</td>";
                        echo "</tr>";
                    }
                    $stmt_pegawai->close();
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>