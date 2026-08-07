<?php
require_once __DIR__ . '/../../config/database_native.php';
require_once __DIR__ . '/../../app/middleware.php';
require_once __DIR__ . '/../../app/helpers_native.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['student'])) {
    jsonResponse('error', null, 'Sesi siswa tidak aktif', 401);
}

$student = $_SESSION['student'];

try {
    $stmt = $conn->prepare("
        SELECT s.*, k.nama_kelas 
        FROM siswa s 
        LEFT JOIN kelas k ON s.kelas_id = k.id 
        WHERE s.id = ? AND s.is_active = 1
    ");
    $stmt->bind_param("i", $student['id']);
    $stmt->execute();
    $studentData = $stmt->get_result()->fetch_assoc();
    
    if (!$studentData) {
        jsonResponse('error', null, 'Data siswa tidak ditemukan', 404);
    }
    
    $stmt_history = $conn->prepare("
        SELECT a.*, m.nama_mapel, u.nama_lengkap as nama_guru
        FROM absensi a
        LEFT JOIN jadwal_pelajaran jp ON a.jadwal_id = jp.id
        LEFT JOIN mata_pelajaran m ON jp.mata_pelajaran_id = m.id
        LEFT JOIN guru g ON jp.guru_id = g.id
        LEFT JOIN users u ON g.user_id = u.id
        WHERE a.siswa_id = ?
        ORDER BY a.tanggal DESC
        LIMIT 10
    ");
    $stmt_history->bind_param("i", $student['id']);
    $stmt_history->execute();
    $historyResult = $stmt_history->get_result();
    $history = [];
    
    while ($row = $historyResult->fetch_assoc()) {
        $row['tanggal_indo'] = formatDateIndonesia($row['tanggal']);
        $history[] = $row;
    }
    
    jsonResponse('success', [
        'student' => $studentData,
        'history' => $history
    ], 'Profil siswa berhasil dimuat');
    
} catch (Exception $e) {
    jsonResponse('error', null, 'Error server: ' . $e->getMessage(), 500);
}
?>