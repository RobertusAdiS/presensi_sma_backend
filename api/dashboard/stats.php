<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/middleware.php';
require_once __DIR__ . '/../../app/helpers.php';

requireApiLogin();

try {
    $total_siswa = query("SELECT COUNT(*) as count FROM siswa WHERE is_active = 1", $conn)->fetch_assoc()['count'];
    $total_guru = query("SELECT COUNT(*) as count FROM guru g JOIN users u ON g.user_id = u.id WHERE u.is_active = 1", $conn)->fetch_assoc()['count'];
    $total_kelas = query("SELECT COUNT(*) as count FROM kelas", $conn)->fetch_assoc()['count'];
    $total_mapel = query("SELECT COUNT(*) as count FROM mata_pelajaran", $conn)->fetch_assoc()['count'];

    $today = date('Y-m-d');
    $stats_today = [
        'Hadir' => 0,
        'Izin' => 0,
        'Sakit' => 0,
        'Alfa' => 0
    ];

    $res_today = query("SELECT status, COUNT(*) as count FROM absensi WHERE tanggal = '$today' GROUP BY status", $conn);
    while ($row = $res_today->fetch_assoc()) {
        $stats_today[$row['status']] = intval($row['count']);
    }

    jsonResponse('success', [
        'totals' => [
            'total_siswa' => intval($total_siswa),
            'total_guru' => intval($total_guru),
            'total_kelas' => intval($total_kelas),
            'total_mapel' => intval($total_mapel)
        ],
        'today_attendance' => $stats_today,
        'today_date' => formatDateIndonesia($today)
    ], 'Data statistik berhasil dimuat');
} catch (Exception $e) {
    jsonResponse('error', null, 'Gagal memuat statistik: ' . $e->getMessage(), 500);
}
?>
