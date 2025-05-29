<?php
// Aktifkan error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';

// Ambil parameter dinas dari URL jika ada
$dinas = $_GET['dinas'] ?? '';

// Ambil daftar pegawai, filter jika ada parameter dinas
if ($dinas) {
    $sql = "SELECT id_pegawai, nip, nama FROM pegawai WHERE dinas=?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $dinas);
} else {
    $sql = "SELECT id_pegawai, nip, nama FROM pegawai";
    $stmt = $mysqli->prepare($sql);
}
$stmt->execute();
$res = $stmt->get_result();
$daftar_pegawai = $res->fetch_all(MYSQLI_ASSOC);

// Proses absensi jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pegawai = $_POST['id_pegawai'] ?? '';
    $nip = $_POST['nip'] ?? '';
    $jenis = $_POST['jenis'] ?? 'masuk'; // masuk, pulang, izin, cuti, dinas_luar
    $tanggal = date('Y-m-d');
    $success = false;

    // Validasi input
    if (!$id_pegawai || !$nip) {
        $pesan = "Data tidak lengkap!";
    } else {
        if ($jenis == 'masuk') {
            // Cek sudah absen masuk
            $sql = "SELECT COUNT(*) FROM absensi WHERE nip=? AND tanggal=? AND jam_masuk IS NOT NULL";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ss", $nip, $tanggal);
            $stmt->execute();
            $stmt->bind_result($sudah_absen);
            $stmt->fetch();
            $stmt->close();
            if ($sudah_absen) {
                $pesan = "Anda sudah absen masuk hari ini.";
            } else {
                $jam_masuk = date('H:i:s');
                $sql = "INSERT INTO absensi (id_pegawai, nip, tanggal, jam_masuk, keterangan) VALUES (?, ?, ?, ?, 'Hadir')";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("isss", $id_pegawai, $nip, $tanggal, $jam_masuk);
                $success = $stmt->execute();
                $pesan = $success ? "Berhasil absen masuk!" : "Gagal absen masuk.";
                $stmt->close();
            }
        } else if ($jenis == 'pulang') {
            // Cek sudah absen masuk & belum pulang
            $sql = "SELECT id_absensi FROM absensi WHERE nip=? AND tanggal=? AND jam_masuk IS NOT NULL AND jam_pulang IS NULL";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ss", $nip, $tanggal);
            $stmt->execute();
            $stmt->bind_result($id_absensi);
            if ($stmt->fetch()) {
                $stmt->close();
                $jam_pulang = date('H:i:s');
                $sql = "UPDATE absensi SET jam_pulang=? WHERE id_absensi=?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("si", $jam_pulang, $id_absensi);
                $success = $stmt->execute();
                $pesan = $success ? "Berhasil absen pulang!" : "Gagal absen pulang.";
                $stmt->close();
            } else {
                $pesan = "Anda belum absen masuk atau sudah absen pulang.";
                $stmt->close();
            }
        } else {
            // Izin, Cuti, Dinas Luar
            $keterangan = '';
            if ($jenis == 'izin') $keterangan = 'Izin';
            if ($jenis == 'cuti') $keterangan = 'Cuti';
            if ($jenis == 'dinas_luar') $keterangan = 'Dinas Luar';
            $catatan = $_POST['catatan'] ?? '';
            $sql = "INSERT INTO absensi (id_pegawai, nip, tanggal, keterangan, catatan) VALUES (?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("issss", $id_pegawai, $nip, $tanggal, $keterangan, $catatan);
            $success = $stmt->execute();
            $pesan = $success ? "Berhasil mengajukan $keterangan!" : "Gagal mengajukan $keterangan.";
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Absensi Pegawai Dinas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
    :root {
        --primary: #1e88e5;
        --primary-dark: #1565c0;
        --danger: #f44336;
        --success: #43a047;
        --light: #f3f7fa;
        --radius: 12px;
    }
    body {
        min-height: 100vh;
        background: var(--light);
        font-family: 'Segoe UI', Arial, sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .absen-container {
        background: #fff;
        padding: 36px 28px 24px 28px;
        border-radius: var(--radius);
        box-shadow: 0 4px 24px #0001, 0 1.5px 8px #0002;
        width: 100%;
        max-width: 370px;
        margin: 28px 0;
    }
    .absen-title {
        text-align: center;
        font-weight: 700;
        font-size: 1.6em;
        letter-spacing: .5px;
        margin-bottom: 30px;
        color: var(--primary-dark);
    }
    label {
        font-weight: 500;
        display: block;
        margin-bottom: 6px;
        margin-top: 10px;
        color: #444;
    }
    select, textarea, input[type="text"], input[type="file"] {
        width: 100%;
        padding: 9px 12px;
        margin-bottom: 14px;
        border-radius: 7px;
        border: 1.5px solid #dde6ef;
        outline: none;
        font-size: 1em;
        transition: border 0.2s;
        background: #f8fafc;
    }
    select:focus, textarea:focus, input[type="text"]:focus {
        border-color: var(--primary);
    }
    textarea {
        min-height: 50px;
        resize: vertical;
    }
    button {
        width: 100%;
        padding: 12px;
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: 7px;
        font-size: 1.08em;
        font-weight: 600;
        margin-top: 10px;
        cursor: pointer;
        transition: background 0.18s;
        box-shadow: 0 2px 8px #1e88e523;
    }
    button:hover {
        background: var(--primary-dark);
    }
    .alert {
        padding: 13px 12px;
        border-radius: var(--radius);
        margin-bottom: 16px;
        font-size: 1em;
        text-align: center;
        border: 1.5px solid;
    }
    .success { background: #e6f6ea; color: var(--success); border-color: #b7e6c3; }
    .error { background: #fdeaea; color: var(--danger); border-color: #ffbcbc; }
    @media (max-width: 500px) {
        .absen-container { padding: 17px 5vw 12px 5vw; }
        .absen-title { font-size: 1.15em; }
        button { font-size: 1em; }
    }
    </style>
    <script>
    function handleJenisChange() {
        var val = document.getElementById('jenis').value;
        var catatan = document.getElementById('catatan_box');
        if (val == 'izin' || val == 'cuti' || val == 'dinas_luar') {
            catatan.style.display = '';
        } else {
            catatan.style.display = 'none';
        }
    }
    window.onload = function() {
        handleJenisChange();
    };
    </script>
</head>
<body>
    <div class="absen-container">
        <div class="absen-title">Absensi Pegawai</div>
        <?php if (isset($pesan)): ?>
            <div class="alert <?=strpos($pesan, 'Berhasil')!==false ? 'success':'error'?>">
                <?= htmlspecialchars($pesan) ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <label>Pilih Nama</label>
            <select name="id_pegawai" required onchange="document.getElementById('nip').value=this.selectedOptions[0].getAttribute('data-nip')">
                <option value="">-- Pilih Pegawai --</option>
                <?php foreach ($daftar_pegawai as $p): ?>
                <option value="<?= htmlspecialchars($p['id_pegawai']) ?>" data-nip="<?= htmlspecialchars($p['nip']) ?>">
                    <?= htmlspecialchars($p['nama']) ?> (<?= htmlspecialchars($p['nip']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" id="nip" name="nip">
            <label>Jenis Absensi</label>
            <select name="jenis" id="jenis" onchange="handleJenisChange()">
                <option value="masuk">Absen Masuk</option>
                <option value="pulang">Absen Pulang</option>
                <option value="izin">Izin</option>
                <option value="cuti">Cuti</option>
                <option value="dinas_luar">Dinas Luar</option>
            </select>
            <div id="catatan_box" style="display:none">
                <label>Catatan</label>
                <textarea name="catatan" rows="2" placeholder="Masukkan keterangan ..."></textarea>
            </div>
            <button type="submit">Kirim</button>
        </form>
    </div>
    <script>
    document.getElementById('jenis').addEventListener('change', handleJenisChange);
    </script>
</body>
</html>