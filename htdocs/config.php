<?php
// config.php
// Database Credentials
define('DB_SERVER', 'sql306.infinityfree.com');
define('DB_USERNAME', 'if0_38965406');
define('DB_PASSWORD', 'Ip9GmWVF6LMo');
define('DB_NAME', 'if0_38965406_absensi_db');

// Attempt to connect to MySQL database
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME); // <-- Koneksi dibuat di sini

// Check connection
if($mysqli === false){
    die("ERROR: Could not connect. " . $mysqli->connect_error);
}
// Set character set (penting untuk karakter khusus)
$mysqli->set_charset("utf8mb4");

// Waktu Toleransi Keterlambatan dalam menit
define('TOLERANSI_KETERLAMBATAN', 15); // Misalnya 15 menit
define('MAX_IZIN_TANPA_SURAT', 3); // Jumlah hari izin tanpa surat (kebijakan dinas)
define('JAM_MASUK_DEFAULT', '08:00:00');
define('JAM_PULANG_DEFAULT', '16:00:00');

// Persentase Potongan TPP (dalam desimal, misal 0.05 untuk 5%)
define('POTONGAN_ALPHA', 0.05);
define('POTONGAN_IZIN', 0.025); // Jika disetujui
define('POTONGAN_SAKIT', 0.015); // Jika disetujui
// Tambahkan definisi untuk potongan jika tidak disetujui (jika ada kebijakan berbeda)
define('POTONGAN_TIDAK_SETUJU', 0.10); // Contoh: 10% jika izin/sakit tidak disetujui

// Fungsi untuk hashing password (jika Anda akan menggunakannya nanti)
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Fungsi untuk verifikasi password (jika Anda akan menggunakannya nanti)
function verify_password($password, $hashed_password) {
    return password_verify($password, $hashed_password);
}

// Fungsi untuk mendapatkan pengaturan dari database (penting untuk konstanta dynamic)
function get_pengaturan($connection, $parameter_name) {
    // Asumsi tabel 'pengaturan' memiliki kolom 'parameter' dan 'nilai'
    $stmt = $connection->prepare("SELECT nilai FROM pengaturan WHERE parameter = ?");
    $stmt->bind_param("s", $parameter_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['nilai'] : null; // Mengembalikan nilai atau null jika tidak ditemukan
}

// Tambahkan ini untuk mendapatkan jam masuk dan pulang dari pengaturan
// atau gunakan default jika tidak ada di pengaturan
$jam_masuk_db = get_pengaturan($mysqli, 'Jam Masuk Default');
$jam_pulang_db = get_pengaturan($mysqli, 'Jam Pulang Default');
define('JAM_MASUK_ABSEN', $jam_masuk_db ? $jam_masuk_db : JAM_MASUK_DEFAULT);
define('JAM_PULANG_ABSEN', $jam_pulang_db ? $jam_pulang_db : JAM_PULANG_DEFAULT);

// Tambahkan ini untuk potongan dari pengaturan
$potongan_alpha_db = get_pengaturan($mysqli, 'Persentase Potongan Alpha');
$potongan_izin_db = get_pengaturan($mysqli, 'Persentase Potongan Izin');
$potongan_sakit_db = get_pengaturan($mysqli, 'Persentase Potongan Sakit');
$toleransi_keterlambatan_db = get_pengaturan($mysqli, 'Waktu Toleransi Keterlambatan');
$max_izin_tanpa_surat_db = get_pengaturan($mysqli, 'Max Izin Tanpa Surat');


define('POTONGAN_ALPHA_ACTUAL', $potongan_alpha_db ? (float)$potongan_alpha_db / 100 : POTONGAN_ALPHA);
define('POTONGAN_IZIN_ACTUAL', $potongan_izin_db ? (float)$potongan_izin_db / 100 : POTONGAN_IZIN);
define('POTONGAN_SAKIT_ACTUAL', $potongan_sakit_db ? (float)$potongan_sakit_db / 100 : POTONGAN_SAKIT);
define('TOLERANSI_KETERLAMBATAN_ACTUAL', $toleransi_keterlambatan_db ? (int)$toleransi_keterlambatan_db : TOLERANSI_KETERLAMBATAN);
define('MAX_IZIN_TANPA_SURAT_ACTUAL', $max_izin_tanpa_surat_db ? (int)$max_izin_tanpa_surat_db : MAX_IZIN_TANPA_SURAT);


// Debugging:
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
?>