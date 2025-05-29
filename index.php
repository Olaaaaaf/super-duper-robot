<?php
// Mulai sesi
session_start();

// --- Kode debugging (tetap biarkan ini untuk saat ini) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- End debugging code ---

// Cek jika user sudah login, redirect ke dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // Berdasarkan hak akses, redirect ke halaman yang sesuai
    switch ($_SESSION["hak_akses"]) {
        case 'Superadmin':
        case 'Admin Dinas':
        case 'Bendahara Dinas':
        case 'Bupati':
            // Semua user admin/bupati diarahkan ke dashboard admin
            // Anda bisa menyesuaikan redirect ini di admin/index.php nanti
            header("location: admin/index.php");
            break;
        case 'Pegawai':
            // Pegawai seharusnya tidak login dari halaman ini jika absensi via QR Code adalah metode utama.
            // Namun, jika ada dashboard khusus pegawai yang diakses via login ini, biarkan.
            // Untuk saat ini, saya asumsikan mereka mungkin punya dashboard.
            header("location: pegawai/index.php");
            break;
        default:
            // Jika hak akses tidak dikenal, logout dan kembali ke login
            session_destroy();
            header("location: index.php");
            exit;
    }
    exit;
}

// Sertakan file konfigurasi database
// Ini akan membuat koneksi $mysqli yang kita butuhkan.
require_once "config.php";

$username = $password = ""; // Mengubah NIP menjadi Username
$username_err = $password_err = $login_err = "";

// Proses data saat form di-submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validasi Username
    if (empty(trim($_POST["username"]))) { // Input field sekarang "username"
        $username_err = "Mohon masukkan Username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Validasi Password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Mohon masukkan password Anda.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validasi kredensial dari tabel 'users'
    if (empty($username_err) && empty($password_err)) {
        // Query ke tabel 'users' untuk mendapatkan informasi login
        // JOIN dengan tabel 'pegawai' untuk mendapatkan nama pegawai
        // LEFT JOIN dengan tabel 'dinas' untuk mendapatkan nama dinas terkait (untuk Admin Dinas/Bendahara Dinas)
        $sql = "SELECT u.id_user, u.id_pegawai, u.username, u.password, u.hak_akses, u.id_dinas_terkait,
                       p.nama AS nama_pegawai, d.nama_dinas AS nama_dinas_terkait
                FROM users u
                JOIN pegawai p ON u.id_pegawai = p.id_pegawai
                LEFT JOIN dinas d ON u.id_dinas_terkait = d.id_dinas
                WHERE u.username = ? AND u.status_akun = 'Aktif'"; // Tambahan kondisi status_akun

        if ($stmt = $mysqli->prepare($sql)) { // Menggunakan $mysqli
            $stmt->bind_param("s", $param_username);
            $param_username = $username;

            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    // Bind hasil ke variabel
                    $stmt->bind_result($id_user, $id_pegawai, $username_db, $password_db, $hak_akses, $id_dinas_terkait, $nama_pegawai, $nama_dinas_terkait);
                    if ($stmt->fetch()) {
                        // Verifikasi password (plain text, sesuai permintaan Anda sebelumnya, **TIDAK AMAN UNTUK PRODUKSI**)
                        if ($password === $password_db) {
                            // Password benar, mulai sesi baru

                            // Simpan data di variabel sesi
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id_user"] = $id_user; // ID dari tabel users
                            $_SESSION["id_pegawai"] = $id_pegawai; // ID pegawai dari tabel pegawai
                            $_SESSION["username"] = $username_db;
                            $_SESSION["hak_akses"] = $hak_akses;
                            $_SESSION["nama_pegawai"] = $nama_pegawai; // Nama dari tabel pegawai
                            $_SESSION["id_dinas_terkait"] = $id_dinas_terkait; // Untuk Admin/Bendahara Dinas
                            $_SESSION["nama_dinas_terkait"] = $nama_dinas_terkait; // Nama dinas terkait

                            // Redirect user berdasarkan hak akses
                            switch ($hak_akses) {
                                case 'Superadmin':
                                case 'Admin Dinas':
                                case 'Bendahara Dinas':
                                case 'Bupati':
                                    header("location: admin/index.php"); // Semua peran admin/bupati ke admin dashboard
                                    break;
                                case 'Pegawai':
                                    // Jika pegawai login dari sini, arahkan ke dashboard pegawai
                                    header("location: pegawai/index.php");
                                    break;
                                default:
                                    // Hak akses tidak dikenal, logout dan kembali ke login
                                    session_destroy();
                                    $login_err = "Hak akses tidak dikenal atau akun tidak aktif.";
                                    break;
                            }
                        } else {
                            $login_err = "Password yang Anda masukkan salah.";
                        }
                    }
                } else {
                    $login_err = "Tidak ada akun ditemukan dengan Username tersebut atau akun tidak aktif.";
                }
            } else {
                // Ini akan menangkap error eksekusi query (misal: kolom tidak ada)
                echo "Ada yang salah dengan eksekusi query. Mohon coba lagi nanti. Error: " . $mysqli->error;
            }

            $stmt->close();
        } else {
            // Ini akan menangkap error prepare statement (misal: sintaks SQL salah)
            echo "Ada yang salah dengan prepare statement. Mohon coba lagi nanti. Error: " . $mysqli->error;
        }
    }

    $mysqli->close(); // Gunakan $mysqli untuk menutup koneksi
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sistem Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body {
            font: 14px sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .wrapper {
            width: 360px;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            max-width: 90%;
            width: 400px;
        }
        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .logo img {
            max-width: 120px;
            height: auto;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body class="d-flex justify-content-center align-items-center min-vh-100 bg-light">
    <div class="wrapper">
        <div class="logo">
            <img src="assets/logo_deiyai.png" alt="Logo Dinas"> </div>
        <h2 class="text-center mb-4">Login Sistem Absensi</h2>
        <p class="text-center">Silakan masukkan Username dan password Anda.</p>

        <?php
        if (!empty($login_err)) {
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>">
                <?php if (!empty($username_err)) echo '<div class="invalid-feedback">' . $username_err . '</div>'; ?>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <?php if (!empty($password_err)) echo '<div class="invalid-feedback">' . $password_err . '</div>'; ?>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">Login</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>