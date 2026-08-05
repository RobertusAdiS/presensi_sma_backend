<?php
require_once __DIR__ . '/../../config/database_native.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/middleware.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$username = sanitize($input['username'] ?? '');
$password = sanitize($input['password'] ?? '');

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Username dan password wajib diisi']);
    exit;
}

try {
    $user = login($username, $password, $conn);
    
    if ($user) {
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Login berhasil!',
            'data' => [
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'nama_lengkap' => $user['nama_lengkap'],
                    'role' => $user['role']
                ]
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Username atau password salah']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error server: ' . $e->getMessage()]);
}
?>