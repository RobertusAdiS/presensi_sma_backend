<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/middleware.php';
require_once __DIR__ . '/../../app/helpers.php';

requireApiLogin();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
if (isset($input['_method'])) {
    $method = strtoupper($input['_method']);
}

try {
    if ($method === 'GET') {
        $id = intval($_GET['id'] ?? 0);
        $kelas_id = intval($_GET['kelas_id'] ?? 0);

        if ($id > 0) {
            $stmt = $conn->prepare("SELECT j.*, m.nama_mapel, k.nama_kelas, u.nama_lengkap as guru_nama FROM jadwal_pelajaran j JOIN mata_pelajaran m ON j.mata_pelajaran_id = m.id JOIN kelas k ON j.kelas_id = k.id JOIN guru g ON j.guru_id = g.id JOIN users u ON g.user_id = u.id WHERE j.id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();
            if ($data) jsonResponse('success', $data, 'Detail jadwal ditemukan');
            else jsonResponse('error', null, 'Jadwal tidak ditemukan', 404);
        } else if ($kelas_id > 0) {
            $data = getJadwalByKelas($kelas_id, $conn);
            jsonResponse('success', $data, 'Jadwal per kelas');
        } else {
            $result = query("SELECT j.*, m.nama_mapel, k.nama_kelas, u.nama_lengkap as guru_nama FROM jadwal_pelajaran j JOIN mata_pelajaran m ON j.mata_pelajaran_id = m.id JOIN kelas k ON j.kelas_id = k.id JOIN guru g ON j.guru_id = g.id JOIN users u ON g.user_id = u.id ORDER BY k.nama_kelas, FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'), j.jam_mulai", $conn);
            $data = $result->fetch_all(MYSQLI_ASSOC);
            jsonResponse('success', $data, 'Seluruh jadwal pelajaran');
        }
    }
    else if ($method === 'POST') {
        requireApiAdmin();
        $kelas_id = intval($input['kelas_id'] ?? 0);
        $mata_pelajaran_id = intval($input['mata_pelajaran_id'] ?? 0);
        $guru_id = intval($input['guru_id'] ?? 0);
        $hari = sanitize($input['hari'] ?? '');
        $jam_mulai = sanitize($input['jam_mulai'] ?? '');
        $jam_selesai = sanitize($input['jam_selesai'] ?? '');

        if ($kelas_id <= 0 || $mata_pelajaran_id <= 0 || $guru_id <= 0 || empty($hari) || empty($jam_mulai) || empty($jam_selesai)) {
            jsonResponse('error', null, 'Semua field wajib diisi', 400);
        }

        $stmt = $conn->prepare("INSERT INTO jadwal_pelajaran (kelas_id, mata_pelajaran_id, guru_id, hari, jam_mulai, jam_selesai) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisss", $kelas_id, $mata_pelajaran_id, $guru_id, $hari, $jam_mulai, $jam_selesai);
        if ($stmt->execute()) {
            jsonResponse('success', ['id' => $stmt->insert_id], 'Jadwal pelajaran berhasil ditambahkan');
        } else {
            jsonResponse('error', null, 'Gagal menambah jadwal pelajaran', 400);
        }
    }
    else if ($method === 'PUT') {
        requireApiAdmin();
        $id = intval($input['id'] ?? $_GET['id'] ?? 0);
        $kelas_id = intval($input['kelas_id'] ?? 0);
        $mata_pelajaran_id = intval($input['mata_pelajaran_id'] ?? 0);
        $guru_id = intval($input['guru_id'] ?? 0);
        $hari = sanitize($input['hari'] ?? '');
        $jam_mulai = sanitize($input['jam_mulai'] ?? '');
        $jam_selesai = sanitize($input['jam_selesai'] ?? '');

        if ($id <= 0 || $kelas_id <= 0 || $mata_pelajaran_id <= 0 || $guru_id <= 0 || empty($hari) || empty($jam_mulai) || empty($jam_selesai)) {
            jsonResponse('error', null, 'Semua field wajib diisi', 400);
        }

        $stmt = $conn->prepare("UPDATE jadwal_pelajaran SET kelas_id=?, mata_pelajaran_id=?, guru_id=?, hari=?, jam_mulai=?, jam_selesai=? WHERE id=?");
        $stmt->bind_param("iiisssi", $kelas_id, $mata_pelajaran_id, $guru_id, $hari, $jam_mulai, $jam_selesai, $id);
        if ($stmt->execute()) {
            jsonResponse('success', null, 'Jadwal pelajaran berhasil diperbarui');
        } else {
            jsonResponse('error', null, 'Gagal memperbarui jadwal', 400);
        }
    }
    else if ($method === 'DELETE') {
        requireApiAdmin();
        $id = intval($input['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) jsonResponse('error', null, 'ID Jadwal wajib diisi', 400);

        $stmt = $conn->prepare("DELETE FROM jadwal_pelajaran WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            jsonResponse('success', null, 'Jadwal pelajaran berhasil dihapus');
        } else {
            jsonResponse('error', null, 'Gagal menghapus jadwal', 400);
        }
    }
    else {
        jsonResponse('error', null, 'Metode HTTP tidak didukung', 405);
    }
} catch (Exception $e) {
    jsonResponse('error', null, 'Internal error: ' . $e->getMessage(), 500);
}
?>
