<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/helpers.php';

logout();
jsonResponse('success', null, 'Berhasil logout');
?>
