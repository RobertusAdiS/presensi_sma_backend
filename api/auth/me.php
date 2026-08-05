<?php
require_once __DIR__ . '/../../config/database_native.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/permissions.php';
require_once __DIR__ . '/../../app/helpers_native.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = getCurrentUser();
if ($user) {
    $user['permissions'] = getUserPermissions();
    $user['role'] = getCurrentRole();
    jsonResponse('success', ['user' => $user], 'Session aktif');
} else {
    jsonResponse('error', null, 'Belum login', 401);
}
?>
