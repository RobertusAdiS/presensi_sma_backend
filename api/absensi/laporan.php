<?php
require_once __DIR__ . '/../../config/database_native.php';
require_once __DIR__ . '/../../app/middleware.php';
require_once __DIR__ . '/../../app/helpers_native.php';

requireApiAnyPermission(['laporan.view']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse('error', null, 'Metode request tidak valid', 405);
}

$bulan = intval($_GET['bulan'] ?? date('n'));
$tahun = intval($_GET['tahun'] ?? date('Y'));
$kelas_id = intval($_GET['kelas_id'] ?? 0);

try {
    $bulanNama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$bulan] ?? '';

    $kelas = null;
    $siswaList = [];
    if ($kelas_id > 0) {
        $stmtK = $conn->prepare("SELECT k.*, u.nama_lengkap as guru_nama FROM kelas k LEFT JOIN guru g ON k.guru_id = g.id LEFT JOIN users u ON g.user_id = u.id WHERE k.id = ?");
        $stmtK->bind_param("i", $kelas_id);
        $stmtK->execute();
        $kelas = $stmtK->get_result()->fetch_assoc();

        $stmtS = $conn->prepare("SELECT id, nisn, nama_lengkap FROM siswa WHERE kelas_id = ? AND is_active = 1 ORDER BY nama_lengkap");
        $stmtS->bind_param("i", $kelas_id);
        $stmtS->execute();
        $siswaList = $stmtS->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $siswaList = $conn->query("SELECT id, nisn, nama_lengkap FROM siswa WHERE is_active = 1 ORDER BY nama_lengkap")->fetch_all(MYSQLI_ASSOC);
    }

    $laporan = [];
    foreach ($siswaList as $siswa) {
        $stmtA = $conn->prepare("SELECT status, COUNT(*) AS total FROM absensi WHERE siswa_id = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ? GROUP BY status");
        $stmtA->bind_param("iii", $siswa['id'], $bulan, $tahun);
        $stmtA->execute();
        $statusRows = $stmtA->get_result()->fetch_all(MYSQLI_ASSOC);

        $counts = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alfa' => 0];
        $totalHadir = 0;
        foreach ($statusRows as $row) {
            $status = ucfirst(strtolower($row['status']));
            $total = intval($row['total']);
            if (isset($counts[$status])) {
                $counts[$status] = $total;
            }
            if ($status === 'Hadir') {
                $totalHadir = $total;
            }
        }

        $totalEntries = array_sum($counts);
        $persentase = $totalEntries > 0 ? round(($totalHadir / $totalEntries) * 100) : 0;

        $laporan[] = [
            'siswa_id' => intval($siswa['id']),
            'nisn' => $siswa['nisn'],
            'nama_lengkap' => $siswa['nama_lengkap'],
            'total_hadir' => $counts['Hadir'],
            'total_izin' => $counts['Izin'],
            'total_sakit' => $counts['Sakit'],
            'total_alfa' => $counts['Alfa'],
            'persentase_hadir' => $persentase
        ];
    }

    jsonResponse('success', [
        'bulan' => $bulan,
        'tahun' => $tahun,
        'bulan_nama' => $bulanNama,
        'kelas' => $kelas,
        'laporan' => $laporan
    ], 'Laporan absensi berhasil dimuat');
} catch (Exception $e) {
    jsonResponse('error', null, 'Internal error: ' . $e->getMessage(), 500);
}
