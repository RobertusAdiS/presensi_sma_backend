<?php
function jsonResponse($status, $data = null, $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    
    $response = [
        'status' => $status,
        'message' => $message,
        'data' => $data
    ];
    
    echo json_encode($response);
    exit;
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function formatDateIndonesia($date) {
    $hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $timestamp = strtotime($date);
    if (!$timestamp) return $date;
    $day = $hari[date('w', $timestamp)];
    $day_num = date('d', $timestamp);
    $month = $bulan[date('n', $timestamp) - 1];
    $year = date('Y', $timestamp);
    return "$day, $day_num $month $year";
}
?>