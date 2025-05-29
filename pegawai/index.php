<?php
// pegawai/index.php

// Pastikan session dimulai jika belum
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include koneksi database dan fungsi umum
require_once __DIR__ . "/../config.php"; // Pastikan config.php mendefinisikan koneksi $mysqli
require_once __DIR__ . "/../includes/db_connection.php";
require_once __DIR__ . "/../includes/functions.php";

// Pastikan $_SESSION["hak_akses"] adalah "Pegawai"
if (!isset($_SESSION["hak_akses"]) || $_SESSION["hak_akses"] !== 'Pegawai') {
    header("location: ../index.php"); // Redirect ke halaman login jika tidak sah
    exit;
}

// Inisialisasi variabel pesan (untuk menampilkan status setelah redirect, misalnya dari absensi.php)
$message = "";
$message_type = "";
if (isset($_GET['status_msg']) && isset($_GET['msg'])) {
    $message_type = htmlspecialchars($_GET['status_msg']);
    $message = htmlspecialchars($_GET['msg']);
}

// Untuk menampilkan profil
$nama_pegawai = htmlspecialchars($_SESSION["nama"]);
$nip_pegawai = htmlspecialchars($_SESSION["nip"]);
$dinas_pegawai = htmlspecialchars($_SESSION["dinas"]);
$hak_akses_pegawai = htmlspecialchars($_SESSION["hak_akses"]);

// Cek apakah sudah absensi masuk/pulang hari ini
$sudah_absensi_masuk = false;
$sudah_absensi_pulang = false;
$jam_masuk_tercatat = null;
$jam_pulang_tercatat = null;
$keterangan_absensi = null; // Untuk izin/sakit/cuti/DL
$tanggal_hari_ini = date('Y-m-d');

$sql_absensi_hari_ini = "SELECT jam_masuk, jam_pulang, keterangan FROM absensi WHERE nip = ? AND tanggal = ?";
if ($stmt = $mysqli->prepare($sql_absensi_hari_ini)) {
    $stmt->bind_param("ss", $nip_pegawai, $tanggal_hari_ini);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($jam_masuk, $jam_pulang, $keterangan);
        $stmt->fetch();
        
        $keterangan_absensi = $keterangan; // Simpan keterangan

        if ($jam_masuk !== null) {
            $sudah_absensi_masuk = true;
            $jam_masuk_tercatat = date('H:i', strtotime($jam_masuk));
        }
        if ($jam_pulang !== null) {
            $sudah_absensi_pulang = true;
            $jam_pulang_tercatat = date('H:i', strtotime($jam_pulang));
        }
    }
    $stmt->close();
}

// Cek apakah hari ini adalah hari libur (dari functions.php)
$hari_libur = get_hari_libur($mysqli);
$is_holiday = in_array($tanggal_hari_ini, $hari_libur);

// Cek hari kerja (Minggu/Sabtu)
$day_of_week = date('w', strtotime($tanggal_hari_ini));
$is_weekend = ($day_of_week == 0 || $day_of_week == 6); // 0=Minggu, 6=Sabtu

// Menentukan status hari ini
$today_status = "Hari Kerja Normal";
$status_color_class = "text-green-600"; // Default
if ($is_weekend) {
    $today_status = "Hari Libur (Akhir Pekan)";
    $status_color_class = "text-indigo-600";
} elseif ($is_holiday) {
    $today_status = "Hari Libur Nasional";
    $status_color_class = "text-red-600";
}

$page_title = "Dashboard Pegawai"; // Variabel ini akan digunakan di header.php
require_once __DIR__ . "/../includes/header.php"; // Sertakan header
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Gaya kustom tambahan jika diperlukan */
        body {
            font-family: 'Inter', sans-serif; /* Font yang lebih modern */
            background-color: #f3f4f6; /* Warna latar belakang lembut */
        }
        .card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .icon-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .absensi-status-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <?php
    // Asumsi header.php sudah menyediakan struktur navigasi dan pembuka tag body
    // Jika tidak, Anda perlu memindahkan elemen <body> dan <div> wrapper ke sini.
    ?>

    <div class="container mx-auto px-4 py-8">
        <h2 class="text-4xl font-extrabold text-gray-900 mb-2 text-center">Dashboard Pegawai</h2>
        <p class="text-lg text-gray-600 mb-8 text-center">Selamat datang kembali, <b class="text-blue-700"><?php echo $nama_pegawai; ?></b>!</p>

        <?php if ($message): ?>
            <div class="p-4 mb-4 text-sm rounded-lg 
                <?php echo ($message_type == 'success') ? 'bg-green-100 text-green-700' : ''; ?>
                <?php echo ($message_type == 'danger') ? 'bg-red-100 text-red-700' : ''; ?>
                <?php echo ($message_type == 'info') ? 'bg-blue-100 text-blue-700' : ''; ?>" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-blue-500 card">
                <div class="flex items-center mb-4">
                    <div class="icon-circle bg-blue-500 text-white me-3">
                        <i class="fas fa-user fa-lg"></i>
                    </div>
                    <h5 class="text-2xl font-semibold text-gray-800">Profil Saya</h5>
                </div>
                <div class="text-gray-700 text-lg space-y-2">
                    <p><strong>NIP:</strong> <?php echo $nip_pegawai; ?></p>
                    <p><strong>Nama:</strong> <?php echo $nama_pegawai; ?></p>
                    <p><strong>Dinas:</strong> <?php echo $dinas_pegawai; ?></p>
                    <p><strong>Hak Akses:</strong> <span class="badge bg-blue-200 text-blue-800 px-2 py-1 rounded-full text-sm font-medium"><?php echo $hak_akses_pegawai; ?></span></p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-purple-500 card">
                <div class="flex items-center mb-4">
                    <div class="icon-circle bg-purple-500 text-white me-3">
                        <i class="fas fa-calendar-check fa-lg"></i>
                    </div>
                    <h5 class="text-2xl font-semibold text-gray-800">Status Absensi Hari Ini</h5>
                </div>
                <div class="text-gray-700 text-lg text-center">
                    <p class="text-xl font-bold mb-2">Tanggal: <?php echo format_tanggal_indo($tanggal_hari_ini); ?></p>
                    <p class="text-xl font-bold mb-4 <?php echo $status_color_class; ?>"><?php echo $today_status; ?></p>

                    <?php if ($is_holiday || $is_weekend): ?>
                        <div class="mt-4 p-3 bg-gray-100 rounded-lg text-gray-700">
                            <p class="text-md">Anda tidak perlu melakukan absensi pada hari ini.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <div class="absensi-status-circle <?php echo $sudah_absensi_masuk ? 'bg-green-500' : 'bg-gray-400'; ?> mx-auto mb-2">
                                    <i class="fas fa-sign-in-alt"></i>
                                </div>
                                <p class="text-md font-medium text-gray-700">Absen Masuk</p>
                                <p class="text-lg font-bold <?php echo $sudah_absensi_masuk ? 'text-green-600' : 'text-gray-500'; ?>">
                                    <?php echo $sudah_absensi_masuk ? $jam_masuk_tercatat : 'Belum'; ?>
                                </p>
                            </div>
                            <div>
                                <div class="absensi-status-circle <?php echo $sudah_absensi_pulang ? 'bg-blue-500' : 'bg-gray-400'; ?> mx-auto mb-2">
                                    <i class="fas fa-sign-out-alt"></i>
                                </div>
                                <p class="text-md font-medium text-gray-700">Absen Pulang</p>
                                <p class="text-lg font-bold <?php echo $sudah_absensi_pulang ? 'text-blue-600' : 'text-gray-500'; ?>">
                                    <?php echo $sudah_absensi_pulang ? $jam_pulang_tercatat : 'Belum'; ?>
                                </p>
                            </div>
                        </div>

                        <?php if ($keterangan_absensi && in_array($keterangan_absensi, ['Izin', 'Sakit', 'Cuti', 'Dinas Luar'])): ?>
                            <div class="mt-4 p-3 bg-blue-100 rounded-lg text-blue-700 font-semibold">
                                <p>Anda berstatus: <span class="font-bold"><?php echo htmlspecialchars($keterangan_absensi); ?></span> hari ini.</p>
                            </div>
                        <?php else: ?>
                            <div class="mt-4">
                                <a href="absensi.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-full transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                                    <i class="fas fa-fingerprint me-2"></i> Lakukan Absensi
                                </a>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-green-500 card flex flex-col justify-between">
                <div>
                    <div class="flex items-center mb-4">
                        <div class="icon-circle bg-green-500 text-white me-3">
                            <i class="fas fa-bolt fa-lg"></i>
                        </div>
                        <h5 class="text-2xl font-semibold text-gray-800">Aksi Cepat</h5>
                    </div>
                    <ul class="space-y-4">
                        <li>
                            <a href="absensi.php" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition duration-200">
                                <div class="icon-circle bg-blue-100 text-blue-600 me-3">
                                    <i class="fas fa-fingerprint"></i>
                                </div>
                                <span class="text-lg font-medium text-gray-800">Absensi Harian</span>
                                <i class="fas fa-chevron-right ms-auto text-gray-400"></i>
                            </a>
                        </li>
                        <li>
                            <a href="riwayat_absensi.php" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition duration-200">
                                <div class="icon-circle bg-green-100 text-green-600 me-3">
                                    <i class="fas fa-history"></i>
                                </div>
                                <span class="text-lg font-medium text-gray-800">Riwayat Absensi</span>
                                <i class="fas fa-chevron-right ms-auto text-gray-400"></i>
                            </a>
                        </li>
                        <li>
                            <a href="pengajuan_izin.php" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition duration-200">
                                <div class="icon-circle bg-orange-100 text-orange-600 me-3">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <span class="text-lg font-medium text-gray-800">Pengajuan Izin/Sakit</span>
                                <i class="fas fa-chevron-right ms-auto text-gray-400"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-yellow-500 card">
            <h5 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-bell text-yellow-600 me-3"></i> Notifikasi / Info Penting
            </h5>
            <div class="text-gray-700 text-lg">
                <p>Tidak ada notifikasi baru saat ini. Pastikan untuk selalu memeriksa bagian ini untuk informasi terkini.</p>
                </div>
        </div>

    </div>

    <?php
    // Asumsi footer.php sudah menyediakan penutup tag body dan html
    require_once __DIR__ . "/../includes/footer.php";
    ?>

</body>
</html>