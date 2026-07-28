<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse('error', null, 'Metode request tidak valid', 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$username = sanitize($input['username'] ?? '');
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    jsonResponse('error', null, 'Username dan password wajib diisi', 400);
}

$user = login($username, $password, $conn);

if ($user) {
    jsonResponse('success', [
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'nama_lengkap' => $user['nama_lengkap'],
            'role' => $user['role']
        ]
    ], 'Login berhasil');
} else {
    jsonResponse('error', null, 'Username atau password salah', 401);
}
?>
