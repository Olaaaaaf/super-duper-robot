<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// 1. Judul dan Header
$sheet->mergeCells('A1:AF1')->setCellValue('A1', 'PEMERINTAH KABUPATEN DEIYAI SEKRETARIAT DAERAH');
$sheet->mergeCells('A2:AF2')->setCellValue('A2', 'LAPORAN BULANAN REKAPAN ABSENSI ASN KABUPATEN DEIYAI');
$sheet->mergeCells('A3:AF3')->setCellValue('A3', 'DINAS TENAGA KERJA');
$sheet->getStyle('A1:A3')->getFont()->setBold(true)->setSize(14);
$sheet->getRowDimension(1)->setRowHeight(25);

// 2. Informasi Bulan/Tahun
$sheet->setCellValue('B5', 'BULAN')->setCellValue('C5', 'APRIL');
$sheet->setCellValue('B6', 'TAHUN')->setCellValue('C6', '2025');

// 3. Header tabel
// Kolom data: No, NIP, Nama, Pangkat, Golongan, Jabatan
$headers = ['NO', 'NIP', 'NAMA', 'PANGKAT', 'GOLONGAN', 'JABATAN'];
$col = 1;
foreach ($headers as $h) {
    $sheet->setCellValueByColumnAndRow($col, 8, $h);
    $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
    $col++;
}
// Kolom tanggal dan hari (misal 30 hari April)
for ($tgl=1; $tgl<=30; $tgl++) {
    $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $hari = date('D', strtotime("2025-04-$tgl"));
    $sheet->setCellValueByColumnAndRow($col, 7, $hari);
    $sheet->setCellValueByColumnAndRow($col, 8, sprintf('%02d', $tgl));
    $sheet->getColumnDimension($cell)->setWidth(3);
    $col++;
}
// Kolom total dll
$totalHeaders = ['H', 'A', 'I', 'S', 'C', 'DL', 'Hari Efektif', '%', 'TPP'];
foreach ($totalHeaders as $h) {
    $sheet->setCellValueByColumnAndRow($col, 8, $h);
    $col++;
}

// 4. Data dummy (isi dari database kamu)
$data = [
    // Nomer, NIP, Nama, Pangkat, Golongan, Jabatan, ...absensi 1-30..., H, A, I, S, C, DL, Hari Efektif, %, TPP
    [1, '1932...', 'ERNEST', 'IV/B', '---', 'KEPALA DINAS', 'A','A','A','A','A','A','A','A','A','H','H','H','H','H','H','H','H','H','H','H','DL','A','A','H','H', 8, 12, 1, 2, 1, 0, 22, '80%', '10%'],
    // dst ...
];
$row = 9;
foreach ($data as $d) {
    $col = 1;
    foreach ($d as $cellval) {
        $sheet->setCellValueByColumnAndRow($col, $row, $cellval);
        // Pewarnaan status
        if ($col > 6 && $col <= 36) { // range tanggal
            switch ($cellval) {
                case 'H': $warna = 'A9D08E'; break;
                case 'A': $warna = 'FF0000'; break;
                case 'I': $warna = 'FFD966'; break;
                case 'S': $warna = '9DC3E6'; break;
                case 'C': $warna = 'E2EFDA'; break;
                case 'DL': $warna = 'D9D9D9'; break;
                default: $warna = false;
            }
            if ($warna) {
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col).$row;
                $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($warna);
            }
        }
        $col++;
    }
    $row++;
}

// 5. Keterangan warna (legend)
$sheet->setCellValue('AG8', 'Keterangan:');
$sheet->setCellValue('AG9', 'H = Hadir');
$sheet->setCellValue('AG10', 'A = Alpha');
$sheet->setCellValue('AG11', 'I = Izin');
$sheet->setCellValue('AG12', 'S = Sakit');
$sheet->setCellValue('AG13', 'C = Cuti');
$sheet->setCellValue('AG14', 'DL = Dinas Luar');

// 6. Output
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="rekap_absensi.xlsx"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>