<?php
require_once __DIR__ . '/../../config/database_native.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/middleware.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

logout();

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Berhasil logout',
    'data' => null
]);
?>