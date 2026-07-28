<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/middleware.php';
require_once __DIR__ . '/../../app/helpers.php';

requireApiLogin();

try {
    $bulan = intval($_GET['bulan'] ?? date('m'));
    $tahun = intval($_GET['tahun'] ?? date('Y'));
    $kelas_id = intval($_GET['kelas_id'] ?? 0);

    if ($kelas_id <= 0) {
        $first_kelas = query("SELECT id FROM kelas ORDER BY tingkat, nama_kelas LIMIT 1", $conn)->fetch_assoc();
        $kelas_id = $first_kelas['id'] ?? 0;
    }

    $stmt_k = $conn->prepare("SELECT k.*, u.nama_lengkap as guru_nama FROM kelas k LEFT JOIN guru g ON k.guru_id = g.id LEFT JOIN users u ON g.user_id = u.id WHERE k.id = ?");
    $stmt_k->bind_param("i", $kelas_id);
    $stmt_k->execute();
    $kelas_info = $stmt_k->get_result()->fetch_assoc();

    $siswa_list = getSiswaByKelas($kelas_id, $conn);
    $laporan = [];

    foreach ($siswa_list as $siswa) {
        $rekap = getRekapAbsensiSiswa($siswa['id'], $bulan, $tahun, $conn);
        $hadir = $rekap['total_hadir'] ?? 0;
        $izin = $rekap['total_izin'] ?? 0;
        $sakit = $rekap['total_sakit'] ?? 0;
        $alfa = $rekap['total_alfa'] ?? 0;
        $total = $hadir + $izin + $sakit + $alfa;

        $persentase = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;

        $laporan[] = [
            'siswa_id' => $siswa['id'],
            'nisn' => $siswa['nisn'],
            'nama_lengkap' => $siswa['nama_lengkap'],
            'total_hadir' => $hadir,
            'total_izin' => $izin,
            'total_sakit' => $sakit,
            'total_alfa' => $alfa,
            'total_pertemuan' => $total,
            'persentase_hadir' => $persentase
        ];
    }

    $bulan_names = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    jsonResponse('success', [
        'bulan' => $bulan,
        'bulan_nama' => $bulan_names[$bulan] ?? '',
        'tahun' => $tahun,
        'kelas' => $kelas_info,
        'laporan' => $laporan
    ], 'Data laporan absensi');
} catch (Exception $e) {
    jsonResponse('error', null, 'Gagal memuat laporan: ' . $e->getMessage(), 500);
}
?>
