<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Dinas</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Pilih Dinas untuk Absensi</h2>
        <form action="absensi.php" method="get">
            <div class="form-group">
                <label for="dinas">Dinas:</label>
                <select name="dinas" class="form-control" required>
                    <option value="">-- Pilih Dinas --</option>
                    <?php
                    require_once "includes/db_connection.php";

                    // Fetch distinct departmental names
                    $sql = "SELECT DISTINCT dinas FROM pegawai ORDER BY dinas ASC";
                    $result = $mysqli->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='".htmlspecialchars($row['dinas'])."'>".htmlspecialchars($row['dinas'])."</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Lanjutkan ke Absensi</button>
        </form>
    </div>
</body>
</html>