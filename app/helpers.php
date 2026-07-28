<?php
/**
 * Helper Functions & JSON Response Utilities
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Send Standard JSON Response
 */
function jsonResponse($status, $data = null, $message = '', $statusCode = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($statusCode);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

/**
 * Sanitize String Input
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Format date to Indonesian
 */
function formatDateIndonesia($date) {
    $hari = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
    $bulan = array(
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    
    $timestamp = strtotime($date);
    if (!$timestamp) return $date;

    $day = $hari[date('w', $timestamp)];
    $day_num = date('d', $timestamp);
    $month = $bulan[date('n', $timestamp) - 1];
    $year = date('Y', $timestamp);
    
    return "$day, $day_num $month $year";
}

/**
 * Get all guru
 */
function getAllGuru($conn) {
    try {
        $result = query("SELECT g.id, u.nama_lengkap, g.nip, g.no_telp, g.alamat, g.jenis_kelamin, u.email, u.username, u.is_active 
                        FROM guru g 
                        JOIN users u ON g.user_id = u.id 
                        WHERE u.is_active = 1 
                        ORDER BY u.nama_lengkap", $conn);
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get all siswa by kelas
 */
function getSiswaByKelas($kelas_id, $conn) {
    try {
        $result = query("SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.kelas_id = k.id WHERE s.kelas_id = $kelas_id AND s.is_active = 1 ORDER BY s.nama_lengkap", $conn);
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get all kelas
 */
function getAllKelas($conn) {
    try {
        $result = query("SELECT k.*, u.nama_lengkap as guru_nama 
                        FROM kelas k 
                        LEFT JOIN guru g ON k.guru_id = g.id 
                        LEFT JOIN users u ON g.user_id = u.id 
                        ORDER BY k.tingkat, k.nama_kelas", $conn);
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get all mata pelajaran
 */
function getAllMataPelajaran($conn) {
    try {
        $result = query("SELECT m.*, u.nama_lengkap as guru_nama 
                        FROM mata_pelajaran m 
                        LEFT JOIN guru g ON m.guru_id = g.id 
                        LEFT JOIN users u ON g.user_id = u.id 
                        ORDER BY m.nama_mapel", $conn);
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get jadwal by kelas
 */
function getJadwalByKelas($kelas_id, $conn) {
    try {
        $result = query("SELECT j.*, m.nama_mapel, k.nama_kelas, u.nama_lengkap as guru_nama 
                        FROM jadwal_pelajaran j 
                        JOIN mata_pelajaran m ON j.mata_pelajaran_id = m.id 
                        JOIN kelas k ON j.kelas_id = k.id
                        JOIN guru g ON j.guru_id = g.id 
                        JOIN users u ON g.user_id = u.id 
                        WHERE j.kelas_id = $kelas_id 
                        ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'), j.jam_mulai", $conn);
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Update rekap absensi
 */
function updateRekapAbsensi($siswa_id, $bulan, $tahun, $conn) {
    try {
        $start_date = "$tahun-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $hadir = query("SELECT COUNT(*) as count FROM absensi WHERE siswa_id = $siswa_id AND status = 'Hadir' AND tanggal BETWEEN '$start_date' AND '$end_date'", $conn)->fetch_assoc()['count'];
        $izin = query("SELECT COUNT(*) as count FROM absensi WHERE siswa_id = $siswa_id AND status = 'Izin' AND tanggal BETWEEN '$start_date' AND '$end_date'", $conn)->fetch_assoc()['count'];
        $sakit = query("SELECT COUNT(*) as count FROM absensi WHERE siswa_id = $siswa_id AND status = 'Sakit' AND tanggal BETWEEN '$start_date' AND '$end_date'", $conn)->fetch_assoc()['count'];
        $alfa = query("SELECT COUNT(*) as count FROM absensi WHERE siswa_id = $siswa_id AND status = 'Alfa' AND tanggal BETWEEN '$start_date' AND '$end_date'", $conn)->fetch_assoc()['count'];
        
        $check = query("SELECT id FROM rekap_absensi WHERE siswa_id = $siswa_id AND bulan = $bulan AND tahun = $tahun", $conn);
        
        if ($check->num_rows > 0) {
            query("UPDATE rekap_absensi SET total_hadir = $hadir, total_izin = $izin, total_sakit = $sakit, total_alfa = $alfa WHERE siswa_id = $siswa_id AND bulan = $bulan AND tahun = $tahun", $conn);
        } else {
            query("INSERT INTO rekap_absensi (siswa_id, bulan, tahun, total_hadir, total_izin, total_sakit, total_alfa) VALUES ($siswa_id, $bulan, $tahun, $hadir, $izin, $sakit, $alfa)", $conn);
        }
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
