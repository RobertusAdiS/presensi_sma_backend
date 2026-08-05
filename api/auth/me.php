<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/permissions.php';
require_once __DIR__ . '/../../app/helpers.php';

$user = getCurrentUser();
if ($user) {
    $user['permissions'] = getUserPermissions();
    $user['role'] = getCurrentRole();
    jsonResponse('success', ['user' => $user], 'Session aktif');
} else {
    jsonResponse('error', null, 'Belum login', 401);
}
?>
