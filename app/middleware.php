<?php
/**
 * Authorization & Security Middleware for REST API
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

/**
 * Enforce Login for API
 */
function requireApiLogin() {
    if (!isLoggedIn()) {
        jsonResponse('error', null, 'Akses ditolak. Silakan login terlebih dahulu.', 401);
    }
}

/**
 * Enforce Admin Role for API
 */
function requireApiAdmin() {
    requireApiLogin();
    if (!hasRole('admin')) {
        jsonResponse('error', null, 'Akses ditolak. Anda bukan Admin.', 403);
    }
}

/**
 * Enforce Student Login for API
 */
function requireApiStudent() {
    if (!isset($_SESSION['student'])) {
        jsonResponse('error', null, 'Akses ditolak. Sesi siswa tidak aktif.', 401);
    }
}
?>
