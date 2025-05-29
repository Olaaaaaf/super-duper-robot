<?php
/**
 * Retrieves a list of holiday dates from the database.
 *
 * @param mysqli $mysqli The database connection object.
 * @return array An array of holiday dates in 'YYYY-MM-DD' format.
 */
function get_hari_libur(mysqli $mysqli): array
{
    $holidays = [];
    // Pastikan nama tabel di sini adalah 'hari_libur_nasional' jika itu yang Anda gunakan
    // Sebelumnya saya asumsikan 'hari_libur_nasional', tapi di functions.php Anda pakai 'hari_libur'
    $sql = "SELECT tanggal FROM hari_libur"; // Pastikan tabel ini ada di DB Anda
    $result = $mysqli->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $holidays[] = $row['tanggal'];
        }
        $result->free();
    } else {
        // Log error jika query gagal
        error_log("Error fetching hari libur: " . $mysqli->error);
    }
    return $holidays;
}

/**
 * Calculates the number of effective workdays in a given month and year,
 * excluding weekends and specified holidays.
 *
 * @param mysqli $mysqli The database connection object.
 * @param int $bulan The month (1-12).
 * @param int $tahun The year.
 * @return int The number of effective workdays.
 */
function get_hari_kerja_efektif(mysqli $mysqli, int $bulan, int $tahun): int
{
    $holidays = get_hari_libur($mysqli); // Menggunakan variabel $holidays untuk hasil hari libur
    $effective_work_days = 0;
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

    for ($day = 1; $day <= $days_in_month; $day++) {
        $current_date_str = sprintf('%04d-%02d-%02d', $tahun, $bulan, $day);
        $current_date_timestamp = strtotime($current_date_str);
        $day_of_week = date('w', $current_date_timestamp); // 0 = Minggu, 6 = Sabtu

        // Perbaikan di sini: Hapus duplikasi dan gunakan $holidays
        // Cek jika bukan hari Minggu (0) atau Sabtu (6) DAN bukan hari libur
        if ($day_of_week !== '0' && $day_of_week !== '6' && !in_array($current_date_str, $holidays)) {
            $effective_work_days++;
        }
    }
    return $effective_work_days;
}

/**
 * Formats a date string into 'd-m-Y' format.
 * Returns '-' if the input is empty or '0000-00-00'.
 *
 * @param string|null $tanggal_str The date string to format.
 * @return string The formatted date string or '-'.
 */
function format_tanggal(?string $tanggal_str): string
{
    if (empty($tanggal_str) || $tanggal_str === '0000-00-00') {
        return '-';
    }
    return date('d-m-Y', strtotime($tanggal_str));
}

/**
 * Formats a time string into 'H:i' format.
 * Returns '-' if the input is empty or '00:00:00'.
 *
 * @param string|null $waktu_str The time string to format.
 * @return string The formatted time string or '-'.
 */
function format_waktu(?string $waktu_str): string
{
    if (empty($waktu_str) || $waktu_str === '00:00:00') {
        return '-';
    }
    return date('H:i', strtotime($waktu_str));
}

/**
 * Converts a time string (HH:MM:SS) to minutes.
 *
 * @param string|null $time_str The time string.
 * @return int The duration in minutes.
 */
function time_to_minutes(?string $time_str): int
{
    if (empty($time_str)) {
        return 0;
    }
    $parts = explode(':', $time_str);
    $hours = (int)($parts[0] ?? 0);
    $minutes = (int)($parts[1] ?? 0);
    return (int)$hours * 60 + (int)$minutes;
}

/**
 * Checks if an actual arrival time is considered late compared to a scheduled time with a tolerance.
 *
 * @param string $scheduled_time_str The expected time of arrival (e.g., '08:00:00').
 * @param string $actual_time_str The actual time of arrival (e.g., '08:05:30').
 * @param int $tolerance_minutes The allowed tolerance in minutes.
 * @return bool True if actual time is after scheduled time + tolerance, false otherwise.
 */
function is_terlambat(string $scheduled_time_str, string $actual_time_str, int $tolerance_minutes): bool
{
    $seharusnya = strtotime($scheduled_time_str);
    $aktual = strtotime($actual_time_str);

    // Hitung selisih dalam detik dan ubah ke menit
    $diff_minutes = ($aktual - $seharusnya) / 60;

    // Dianggap terlambat jika selisih lebih besar dari toleransi
    return $diff_minutes > $tolerance_minutes;
}

/**
 * Validates an uploaded file based on allowed extensions and maximum size.
 *
 * @param array $file_array The $_FILES entry for the uploaded file.
 * @param array $allowed_extensions An array of allowed file extensions (e.g., ['jpg', 'png']).
 * @param int $max_size_kb The maximum allowed file size in kilobytes.
 * @return array An associative array with 'success' (bool) and 'message' (string).
 */
function validate_uploaded_file(array $file_array, array $allowed_extensions = [], int $max_size_kb = 2048): array
{
    $response = ['success' => false, 'message' => ''];

    if ($file_array['error'] === UPLOAD_ERR_NO_FILE) {
        $response['success'] = true; // Tidak ada file yang diunggah
        $response['message'] = 'Tidak ada file yang diunggah.';
        return $response;
    }

    if ($file_array['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Terjadi kesalahan saat mengunggah file: ' . $file_array['error'];
        return $response;
    }

    $file_ext = strtolower(pathinfo($file_array['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_extensions)) {
        $response['message'] = 'Ekstensi file tidak diizinkan. Hanya ' . implode(', ', $allowed_extensions) . ' yang diperbolehkan.';
        return $response;
    }

    if ($file_array['size'] > ($max_size_kb * 1024)) {
        $response['message'] = 'Ukuran file terlalu besar. Maksimal ' . $max_size_kb . ' KB.';
        return $response;
    }

    $response['success'] = true;
    $response['message'] = 'File valid.';
    return $response;
}

/**
 * Uploads a file to the specified target directory with a unique name.
 *
 * @param array $file_array The $_FILES entry for the uploaded file.
 * @param string $target_dir The directory where the file should be uploaded.
 * @return array An associative array with 'success', 'path', and 'message'.
 */
function upload_file(array $file_array, string $target_dir): array
{
    $response = ['success' => false, 'path' => '', 'message' => ''];

    // Pastikan direktori target ada
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $unique_name = uniqid() . '_' . basename($file_array['name']);
    $target_file = $target_dir . $unique_name;

    if (move_uploaded_file($file_array["tmp_name"], $target_file)) {
        $response['success'] = true;
        $response['path'] = $target_file; // Atau path relatif jika diperlukan
        $response['message'] = 'File berhasil diunggah.';
    } else {
        $response['message'] = 'Gagal mengunggah file. Kode error: ' . $file_array['error'];
    }
    return $response;
}

/**
 * Retrieves a list of all distinct 'dinas' (departments/agencies) from the 'pegawai' table.
 *
 * @param mysqli $mysqli The database connection object.
 * @return array An array of unique dinas names.
 */
function get_all_dinas(mysqli $mysqli): array
{
    $dinas_list = [];
    $sql = "SELECT DISTINCT dinas FROM pegawai ORDER BY dinas ASC";
    $result = $mysqli->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $dinas_list[] = htmlspecialchars($row['dinas']);
        }
        $result->free();
    } else {
        // Log the error if the query fails
        error_log("Error fetching all dinas: " . $mysqli->error);
    }
    return $dinas_list;
}

?>