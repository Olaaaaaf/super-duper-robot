<?php
// db_connection.php
// File ini berfungsi untuk membuat koneksi database.
// Diasumsikan bahwa config.php (yang mendefinisikan DB_SERVER, DB_USERNAME, dll.)
// sudah dimuat sebelum file ini di-include.

/* Coba koneksi ke database MySQL */
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Cek koneksi
if ($mysqli->connect_errno) {
    die("ERROR: Tidak dapat terhubung ke database. " . $mysqli->connect_error);
}

// Tambahkan kode untuk mengambil pengaturan dinamis jika diperlukan
// Ini harus ada di sini atau di file lain yang di-include setelah db_connection.php
// dan sebelum fungsi get_pengaturan() digunakan.
// Pastikan fungsi get_pengaturan() sudah tersedia (dari functions.php)
// dan $mysqli sudah terdefinisi di sini.

/*
// Contoh bagaimana mengambil pengaturan dinamis (jika fungsi get_pengaturan ada)
// Ini harus ada di file utama atau file lain yang memerlukan nilai ini setelah koneksi DB.
// Jika Anda ingin ini menjadi konstanta, definisikan di sini setelah $mysqli ada.

if (function_exists('get_pengaturan')) { // Pastikan functions.php sudah dimuat
    $jam_masuk_db = get_pengaturan($mysqli, 'Jam Masuk Default');
    $jam_pulang_db = get_pengaturan($mysqli, 'Jam Pulang Default');
    define('JAM_MASUK_ABSEN', $jam_masuk_db ? $jam_masuk_db : JAM_MASUK_DEFAULT);
    define('JAM_PULANG_ABSEN', $jam_pulang_db ? $jam_pulang_db : JAM_PULANG_DEFAULT);

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
}
*/
?>