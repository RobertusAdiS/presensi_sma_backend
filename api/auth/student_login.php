<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/middleware.php';
require_once __DIR__ . '/../../app/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse('error', null, 'Metode request tidak valid', 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$username = sanitize($input['username'] ?? '');
$password = sanitize($input['password'] ?? '');

if (empty($username) || empty($password)) {
    jsonResponse('error', null, 'Nama Lengkap dan NISN wajib diisi', 400);
}

try {
    $stmt = $conn->prepare("
        SELECT id, nama_lengkap, nisn, kelas_id, is_active 
        FROM siswa 
        WHERE LOWER(TRIM(nama_lengkap)) = LOWER(TRIM(?)) 
          AND TRIM(nisn) = TRIM(?) 
          AND is_active = 1
    ");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $siswa = $result->fetch_assoc();
        $_SESSION['auth_type'] = 'student';
        $_SESSION['student'] = [
            'id' => $siswa['id'],
            'nama_lengkap' => $siswa['nama_lengkap'],
            'nisn' => $siswa['nisn'],
            'kelas_id' => $siswa['kelas_id']
        ];
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['nama_lengkap'], $_SESSION['role']);

        jsonResponse('success', [
            'student' => $siswa
        ], 'Login siswa berhasil!');
    } else {
        jsonResponse('error', null, 'Kombinasi Nama Lengkap atau NISN salah atau akun tidak aktif', 401);
    }
} catch (Exception $e) {
    jsonResponse('error', null, 'Error server: ' . $e->getMessage(), 500);
}
?>
