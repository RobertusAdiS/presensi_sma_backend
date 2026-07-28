<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/middleware.php';
require_once __DIR__ . '/../../app/helpers.php';

requireApiLogin();

$user = getCurrentUser();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $jadwal_id = intval($_GET['jadwal_id'] ?? 0);
        $tanggal = sanitize($_GET['tanggal'] ?? date('Y-m-d'));

        if ($jadwal_id <= 0) {
            jsonResponse('error', null, 'Jadwal ID wajib diisi', 400);
        }

        $stmt_j = $conn->prepare("SELECT j.*, m.nama_mapel, k.nama_kelas, u.nama_lengkap as guru_nama FROM jadwal_pelajaran j JOIN mata_pelajaran m ON j.mata_pelajaran_id = m.id JOIN kelas k ON j.kelas_id = k.id JOIN guru g ON j.guru_id = g.id JOIN users u ON g.user_id = u.id WHERE j.id = ?");
        $stmt_j->bind_param("i", $jadwal_id);
        $stmt_j->execute();
        $jadwal = $stmt_j->get_result()->fetch_assoc();

        if (!$jadwal) {
            jsonResponse('error', null, 'Jadwal tidak ditemukan', 404);
        }

        $siswa_list = getSiswaByKelas($jadwal['kelas_id'], $conn);
        $attendees = [];

        foreach ($siswa_list as $siswa) {
            $stmt_a = $conn->prepare("SELECT status, keterangan FROM absensi WHERE siswa_id = ? AND jadwal_id = ? AND tanggal = ?");
            $stmt_a->bind_param("iis", $siswa['id'], $jadwal_id, $tanggal);
            $stmt_a->execute();
            $absen = $stmt_a->get_result()->fetch_assoc();

            $attendees[] = [
                'siswa_id' => $siswa['id'],
                'nisn' => $siswa['nisn'],
                'nama_lengkap' => $siswa['nama_lengkap'],
                'jenis_kelamin' => $siswa['jenis_kelamin'],
                'status' => $absen['status'] ?? 'Hadir',
                'keterangan' => $absen['keterangan'] ?? ''
            ];
        }

        jsonResponse('success', [
            'jadwal' => $jadwal,
            'tanggal' => $tanggal,
            'tanggal_indo' => formatDateIndonesia($tanggal),
            'attendees' => $attendees
        ], 'Data absensi peserta');
    }
    else if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $jadwal_id = intval($input['jadwal_id'] ?? 0);
        $tanggal = sanitize($input['tanggal'] ?? '');
        $attendees = $input['attendees'] ?? [];

        if ($jadwal_id <= 0 || empty($tanggal) || !is_array($attendees)) {
            jsonResponse('error', null, 'Data input absensi tidak valid', 400);
        }

        $conn->begin_transaction();
        try {
            $bulan = date('m', strtotime($tanggal));
            $tahun = date('Y', strtotime($tanggal));

            foreach ($attendees as $att) {
                $siswa_id = intval($att['siswa_id'] ?? 0);
                $status = sanitize($att['status'] ?? 'Hadir');
                $keterangan = sanitize($att['keterangan'] ?? '');

                if ($siswa_id <= 0) continue;

                $check = query("SELECT id FROM absensi WHERE siswa_id = $siswa_id AND jadwal_id = $jadwal_id AND tanggal = '$tanggal'", $conn);

                if ($check->num_rows > 0) {
                    $stmt_u = $conn->prepare("UPDATE absensi SET status = ?, keterangan = ?, dicatat_oleh = ? WHERE siswa_id = ? AND jadwal_id = ? AND tanggal = ?");
                    $stmt_u->bind_param("sssiis", $status, $keterangan, $user['username'], $siswa_id, $jadwal_id, $tanggal);
                    $stmt_u->execute();
                } else {
                    $stmt_i = $conn->prepare("INSERT INTO absensi (siswa_id, jadwal_id, tanggal, status, keterangan, dicatat_oleh) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt_i->bind_param("iissss", $siswa_id, $jadwal_id, $tanggal, $status, $keterangan, $user['username']);
                    $stmt_i->execute();
                }

                updateRekapAbsensi($siswa_id, $bulan, $tahun, $conn);
            }

            $conn->commit();
            jsonResponse('success', null, 'Absensi berhasil disimpan!');
        } catch (Exception $e) {
            $conn->rollback();
            jsonResponse('error', null, 'Gagal menyimpan absensi: ' . $e->getMessage(), 500);
        }
    }
    else {
        jsonResponse('error', null, 'Metode HTTP tidak didukung', 405);
    }
} catch (Exception $e) {
    jsonResponse('error', null, 'Internal error: ' . $e->getMessage(), 500);
}
?>
