<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/middleware.php';
require_once __DIR__ . '/../../app/helpers.php';

requireApiLogin(); // Ensures the user is logged in (Guru/Admin)
$user = getCurrentUser();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$jadwal_id = intval($input['jadwal_id'] ?? 0);
$tanggal = sanitize($input['tanggal'] ?? '');
$durasi = intval($input['durasi'] ?? 15); // Default 15 minutes

if ($jadwal_id <= 0 || empty($tanggal)) {
    jsonResponse('error', null, 'Jadwal dan Tanggal wajib diisi', 400);
}

try {
    // Check if jadwal exists
    $stmt = $conn->prepare("SELECT id FROM jadwal_pelajaran WHERE id = ?");
    $stmt->bind_param("i", $jadwal_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        jsonResponse('error', null, 'Jadwal pelajaran tidak ditemukan', 404);
    }

    // Generate unique secure token
    $token = bin2hex(random_bytes(16)) . '-' . time();
    
    // Calculate expiration time
    $created_by = $user['id'];
    $expires_at = date('Y-m-d H:i:s', strtotime("+$durasi minutes"));

    $insert = $conn->prepare("INSERT INTO qr_sessions (token, jadwal_id, tanggal, expires_at, created_by) VALUES (?, ?, ?, ?, ?)");
    $insert->bind_param("sissi", $token, $jadwal_id, $tanggal, $expires_at, $created_by);
    
    if ($insert->execute()) {
        jsonResponse('success', [
            'token' => $token,
            'expires_at' => $expires_at,
            'durasi_menit' => $durasi
        ], 'QR Code berhasil dibuat');
    } else {
        jsonResponse('error', null, 'Gagal membuat sesi QR Code', 500);
    }
} catch (Exception $e) {
    jsonResponse('error', null, 'Terjadi kesalahan sistem: ' . $e->getMessage(), 500);
}
?>
