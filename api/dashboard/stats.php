<?php
require_once __DIR__ . '/../../config/database_native.php';
require_once __DIR__ . '/../../app/middleware.php';
require_once __DIR__ . '/../../app/helpers_native.php';

requireApiAnyPermission(['dashboard.view']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse('error', null, 'Metode request tidak valid', 405);
}

try {
    $totalSiswa = $conn->query("SELECT COUNT(*) AS total FROM siswa WHERE is_active = 1")->fetch_assoc()['total'] ?? 0;
    $totalGuru = $conn->query("SELECT COUNT(*) AS total FROM guru g JOIN users u ON g.user_id = u.id WHERE u.is_active = 1")->fetch_assoc()['total'] ?? 0;
    $totalKelas = $conn->query("SELECT COUNT(*) AS total FROM kelas")->fetch_assoc()['total'] ?? 0;
    $totalMapel = $conn->query("SELECT COUNT(*) AS total FROM mata_pelajaran")->fetch_assoc()['total'] ?? 0;

    $today = date('Y-m-d');
    $todayRows = $conn->query("SELECT status, COUNT(*) AS total FROM absensi WHERE tanggal = '$today' GROUP BY status");
    $todayAttendance = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alfa' => 0];
    while ($row = $todayRows->fetch_assoc()) {
        $status = ucfirst(strtolower($row['status']));
        if (isset($todayAttendance[$status])) {
            $todayAttendance[$status] = intval($row['total']);
        }
    }

    jsonResponse('success', [
        'totals' => [
            'total_siswa' => intval($totalSiswa),
            'total_guru' => intval($totalGuru),
            'total_kelas' => intval($totalKelas),
            'total_mapel' => intval($totalMapel)
        ],
        'today_attendance' => $todayAttendance
    ], 'Statistik dashboard berhasil dimuat');
} catch (Exception $e) {
    jsonResponse('error', null, 'Internal error: ' . $e->getMessage(), 500);
}
