<?php
require_once '../config.php';
require_once '../includes/header.php';

// Ambil daftar dinas
$sql = "SELECT id_dinas, nama_dinas FROM dinas ORDER BY nama_dinas";
$res = $mysqli->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manajemen Dinas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
    body {
        background: #f3f7fa;
        font-family: 'Segoe UI', Arial, sans-serif;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 700px;
        margin: 40px auto 0 auto;
        background: #fff;
        padding: 32px 28px 22px 28px;
        border-radius: 14px;
        box-shadow: 0 2px 8px #0002;
    }
    h2 {
        text-align: center;
        color: #1e88e5;
        margin-bottom: 28px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 24px;
    }
    th, td {
        padding: 12px 8px;
        border: 1px solid #dde6ef;
        text-align: left;
    }
    th {
        background: #e3eafc;
        font-weight: 600;
        color: #1976d2;
    }
    tr:nth-child(even) { background: #f6faff; }
    tr:hover { background: #f0f7ff; }
    .aksi-links a {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 6px;
        color: #fff;
        text-decoration: none;
        font-size: 0.97em;
        margin-right: 5px;
        margin-bottom: 3px;
        transition: background 0.16s;
    }
    .aksi-links .edit { background: #43a047; }
    .aksi-links .edit:hover { background: #388e3c; }
    .aksi-links .delete { background: #e53935; }
    .aksi-links .delete:hover { background: #b71c1c; }
    .aksi-links .qr { background: #ffa726; color: #333; }
    .aksi-links .qr:hover { background: #fb8c00; color: #fff; }
    .aksi-links .pegawai { background: #1976d2;}
    .aksi-links .pegawai:hover { background: #0d47a1;}
    @media (max-width: 600px) {
        .container { padding: 10px 2vw 10px 2vw; }
        table, th, td { font-size: 0.97em; }
        .aksi-links a { font-size: 0.9em; padding: 5px 8px;}
    }
    </style>
</head>
<body>
    <div class="container">
        <h2>Daftar Dinas</h2>
        <table>
            <tr>
                <th>No</th>
                <th>Nama Dinas</th>
                <th>Aksi</th>
            </tr>
            <?php $no=1; while ($row = $res->fetch_assoc()): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nama_dinas']) ?></td>
                <td class="aksi-links">
                    <a class="edit" href="edit_dinas.php?id=<?= $row['id_dinas'] ?>">Edit</a>
                    <a class="delete" href="hapus_dinas.php?id=<?= $row['id_dinas'] ?>"
                       onclick="return confirm('Hapus dinas ini? Data pegawai juga bisa terhapus. Lanjutkan?')">Hapus</a>
                    <a class="qr" href="../pegawai/absensi.php?dinas=<?= urlencode($row['nama_dinas']) ?>" target="_blank" title="Link Absensi Dinas">QR Absensi</a>
                    <a class="pegawai" href="manage_pegawai.php?dinas=<?= urlencode($row['nama_dinas']) ?>" title="Lihat Pegawai">Pegawai</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>