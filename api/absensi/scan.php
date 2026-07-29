<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/middleware.php';
require_once __DIR__ . '/../../app/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$student = $_SESSION['student'] ?? null;

if (!$student) {
    jsonResponse('error', null, 'Sesi siswa tidak aktif. Silakan login terlebih dahulu.', 401);
}

// Check for token from QR Code
$token = sanitize($input['token'] ?? '');

if (empty($token)) {
    jsonResponse('error', null, 'Token QR Code tidak valid', 400);
}

try {
    // Verify token from qr_sessions table
    $stmt_qr = $conn->prepare("SELECT jadwal_id, tanggal, expires_at FROM qr_sessions WHERE token = ?");
    $stmt_qr->bind_param("s", $token);
    $stmt_qr->execute();
    $qr_res = $stmt_qr->get_result();

    if ($qr_res->num_rows === 0) {
        jsonResponse('error', null, 'QR Code tidak dikenali atau tidak valid.', 404);
    }

    $qr_session = $qr_res->fetch_assoc();
    $jadwal_id = $qr_session['jadwal_id'];
    $tanggal = $qr_session['tanggal'];
    $expires_at = $qr_session['expires_at'];

    // Check expiration
    if (strtotime($expires_at) < time()) {
        jsonResponse('error', null, 'Waktu presensi untuk QR Code ini telah habis (Kedaluwarsa).', 403);
    }

    $status = 'Hadir'; // Default from QR Scan
    $keterangan = 'Presensi Mandiri via QR Scan';

    // Verify Schedule
    $stmt_j = $conn->prepare("SELECT j.*, m.nama_mapel, k.nama_kelas FROM jadwal_pelajaran j JOIN mata_pelajaran m ON j.mata_pelajaran_id = m.id JOIN kelas k ON j.kelas_id = k.id WHERE j.id = ?");
    $stmt_j->bind_param("i", $jadwal_id);
    $stmt_j->execute();
    $jadwal = $stmt_j->get_result()->fetch_assoc();

    if (!$jadwal) {
        jsonResponse('error', null, 'Jadwal presensi tidak ditemukan', 404);
    }

    if (intval($student['kelas_id']) !== intval($jadwal['kelas_id'])) {
        jsonResponse('error', null, 'Jadwal presensi ini bukan untuk kelas Anda (' . $jadwal['nama_kelas'] . ')', 403);
    }

    $siswa_id = $student['id'];
    $check = query("SELECT id, status FROM absensi WHERE siswa_id = $siswa_id AND jadwal_id = $jadwal_id AND tanggal = '$tanggal'", $conn);

    if ($check->num_rows > 0) {
        $existing = $check->fetch_assoc();
        jsonResponse('success', [
            'already_recorded' => true,
            'status' => $existing['status'],
            'mapel' => $jadwal['nama_mapel']
        ], 'Anda sudah melakukan presensi untuk pelajaran ini (' . $existing['status'] . ').');
    } else {
        $dicatat = $student['nama_lengkap'] . ' (Self QR Scan)';
        $stmt_i = $conn->prepare("INSERT INTO absensi (siswa_id, jadwal_id, tanggal, status, keterangan, dicatat_oleh) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_i->bind_param("iissss", $siswa_id, $jadwal_id, $tanggal, $status, $keterangan, $dicatat);
        $stmt_i->execute();

        $bulan = date('m', strtotime($tanggal));
        $tahun = date('Y', strtotime($tanggal));
        updateRekapAbsensi($siswa_id, $bulan, $tahun, $conn);

        jsonResponse('success', [
            'recorded' => true,
            'status' => $status,
            'mapel' => $jadwal['nama_mapel'],
            'waktu' => date('H:i:s')
        ], 'Presensi berhasil dicatat untuk mata pelajaran ' . $jadwal['nama_mapel']);
    }
} catch (Exception $e) {
    jsonResponse('error', null, 'Gagal memproses scan QR: ' . $e->getMessage(), 500);
}
?>
