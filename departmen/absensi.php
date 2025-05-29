<?php
if (!isset($_GET['dinas'])) {
    header("location: index.php");
    exit; // Redirect back to department selection if no department chosen
}

$dinas = htmlspecialchars($_GET['dinas']);
require_once "includes/db_connection.php"; 

// Check if the post method is used
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nip = trim($_POST['nip']);
    $tanggal = date('Y-m-d', strtotime(trim($_POST['tanggal'])));
    $jam_masuk = trim($_POST['jam_masuk']);
    $jam_pulang = trim($_POST['jam_pulang']);
    $keterangan = trim($_POST['keterangan']);

    // Validate input
    if (empty($nip) || empty($tanggal) || empty($jam_masuk) || empty($jam_pulang) || empty($keterangan)) {
        $message = "Semua field wajib diisi.";
    } else {
        // Insert absensi record into the database
        $sql = "INSERT INTO absensi (nip, tanggal, jam_masuk, jam_pulang, keterangan) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("sssss", $nip, $tanggal, $jam_masuk, $jam_pulang, $keterangan);
            if ($stmt->execute()) {
                $message = "Absensi berhasil dicatat.";
            } else {
                $message = "Gagal mencatat absensi: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Dinas <?php echo $dinas; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Absensi Dinas <?php echo $dinas; ?></h2>
        <?php if (isset($message)) echo '<div class="alert alert-info">' . $message . '</div>'; ?>

        <form action="" method="post">
            <div class="form-group">
                <label for="nip">NIP:</label>
                <input type="text" class="form-control" name="nip" required>
            </div>
            <div class="form-group">
                <label for="tanggal">Tanggal:</label>
                <input type="date" class="form-control" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label for="jam_masuk">Jam Masuk:</label>
                <input type="time" class="form-control" name="jam_masuk" required>
            </div>
            <div class="form-group">
                <label for="jam_pulang">Jam Pulang:</label>
                <input type="time" class="form-control" name="jam_pulang" required>
            </div>
            <div class="form-group">
                <label for="keterangan">Keterangan:</label>
                <select class="form-control" name="keterangan" required>
                    <option value="Hadir">Hadir</option>
                    <option value="Izin">Izin</option>
                    <option value="Sakit">Sakit</option>
                    <option value="Cuti">Cuti</option>
                    <option value="Dinas Luar">Dinas Luar</option>
                    <option value="Alpha">Alpha</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Kirim Absensi</button>
        </form>
    </div>
</body>
</html>