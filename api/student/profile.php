<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/middleware.php';
require_once __DIR__ . '/../../app/helpers.php';

requireApiStudent();

$student_session = $_SESSION['student'];
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

try {
    if ($method === 'GET') {
        $stmt_s = $conn->prepare("SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.kelas_id = k.id WHERE s.id = ? AND s.is_active = 1");
        $stmt_s->bind_param("i", $student_session['id']);
        $stmt_s->execute();
        $student_full = $stmt_s->get_result()->fetch_assoc();

        if (!$student_full) {
            unset($_SESSION['student']);
            jsonResponse('error', null, 'Akun siswa tidak aktif', 401);
        }

        $stats = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alfa' => 0];
        $stmt_sum = $conn->prepare("SELECT status, COUNT(*) as count FROM absensi WHERE siswa_id = ? GROUP BY status");
        $stmt_sum->bind_param("i", $student_full['id']);
        $stmt_sum->execute();
        $res_sum = $stmt_sum->get_result();
        while ($r = $res_sum->fetch_assoc()) {
            $stats[$r['status']] = intval($r['count']);
        }

        $subject_stats = [];
        $stmt_subj = $conn->prepare("
            SELECT 
                m.nama_mapel,
                COUNT(CASE WHEN a.status = 'Hadir' THEN 1 END) as hadir,
                COUNT(CASE WHEN a.status = 'Izin' THEN 1 END) as izin,
                COUNT(CASE WHEN a.status = 'Sakit' THEN 1 END) as sakit,
                COUNT(CASE WHEN a.status = 'Alfa' THEN 1 END) as alfa
            FROM mata_pelajaran m
            JOIN jadwal_pelajaran j ON j.mata_pelajaran_id = m.id
            LEFT JOIN absensi a ON a.jadwal_id = j.id AND a.siswa_id = ?
            WHERE j.kelas_id = ?
            GROUP BY m.id, m.nama_mapel
            ORDER BY m.nama_mapel
        ");
        $stmt_subj->bind_param("ii", $student_full['id'], $student_full['kelas_id']);
        $stmt_subj->execute();
        $res_subj = $stmt_subj->get_result();
        while ($r = $res_subj->fetch_assoc()) {
            $subject_stats[] = $r;
        }

        $history = [];
        $stmt_h = $conn->prepare("
            SELECT a.tanggal, a.status, a.keterangan, m.nama_mapel, u.nama_lengkap as nama_guru
            FROM absensi a
            JOIN jadwal_pelajaran j ON a.jadwal_id = j.id
            JOIN mata_pelajaran m ON j.mata_pelajaran_id = m.id
            JOIN guru g ON j.guru_id = g.id
            JOIN users u ON g.user_id = u.id
            WHERE a.siswa_id = ?
            ORDER BY a.tanggal DESC, a.created_at DESC
            LIMIT 10
        ");
        $stmt_h->bind_param("i", $student_full['id']);
        $stmt_h->execute();
        $res_h = $stmt_h->get_result();
        while ($r = $res_h->fetch_assoc()) {
            $history[] = [
                'tanggal' => $r['tanggal'],
                'tanggal_indo' => formatDateIndonesia($r['tanggal']),
                'status' => $r['status'],
                'keterangan' => $r['keterangan'],
                'nama_mapel' => $r['nama_mapel'],
                'nama_guru' => $r['nama_guru']
            ];
        }

        jsonResponse('success', [
            'student' => $student_full,
            'stats' => $stats,
            'subject_stats' => $subject_stats,
            'history' => $history
        ], 'Data profil siswa');
    }
    else if ($method === 'POST') {
        $no_telp = sanitize($input['no_telp'] ?? '');
        $alamat = sanitize($input['alamat'] ?? '');

        $stmt = $conn->prepare("UPDATE siswa SET no_telp = ?, alamat = ? WHERE id = ?");
        $stmt->bind_param("ssi", $no_telp, $alamat, $student_session['id']);
        if ($stmt->execute()) {
            jsonResponse('success', null, 'Profil berhasil diperbarui');
        } else {
            jsonResponse('error', null, 'Gagal mengedit profil', 400);
        }
    }
    else {
        jsonResponse('error', null, 'Metode HTTP tidak didukung', 405);
    }
} catch (Exception $e) {
    jsonResponse('error', null, 'Internal error: ' . $e->getMessage(), 500);
}
?>
