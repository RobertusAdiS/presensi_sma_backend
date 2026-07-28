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
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT m.*, u.nama_lengkap as guru_nama FROM mata_pelajaran m JOIN guru g ON m.guru_id = g.id JOIN users u ON g.user_id = u.id WHERE m.id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();
            if ($data) jsonResponse('success', $data, 'Data mapel ditemukan');
            else jsonResponse('error', null, 'Mata pelajaran tidak ditemukan', 404);
        } else {
            $data = getAllMataPelajaran($conn);
            jsonResponse('success', $data, 'Seluruh data mata pelajaran');
        }
    }
    else if ($method === 'POST') {
        requireApiAdmin();
        $nama_mapel = sanitize($input['nama_mapel'] ?? '');
        $kode_mapel = sanitize($input['kode_mapel'] ?? '');
        $guru_id = intval($input['guru_id'] ?? 0);

        if (empty($nama_mapel) || empty($kode_mapel) || $guru_id <= 0) {
            jsonResponse('error', null, 'Nama mapel, Kode mapel, dan Guru pengampu wajib diisi', 400);
        }

        $stmt = $conn->prepare("INSERT INTO mata_pelajaran (nama_mapel, kode_mapel, guru_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $nama_mapel, $kode_mapel, $guru_id);
        if ($stmt->execute()) {
            jsonResponse('success', ['id' => $stmt->insert_id], 'Mata pelajaran berhasil ditambahkan');
        } else {
            jsonResponse('error', null, 'Kode mapel sudah terdaftar atau terjadi kesalahan', 400);
        }
    }
    else if ($method === 'PUT') {
        requireApiAdmin();
        $id = intval($input['id'] ?? $_GET['id'] ?? 0);
        $nama_mapel = sanitize($input['nama_mapel'] ?? '');
        $kode_mapel = sanitize($input['kode_mapel'] ?? '');
        $guru_id = intval($input['guru_id'] ?? 0);

        if ($id <= 0 || empty($nama_mapel) || empty($kode_mapel) || $guru_id <= 0) {
            jsonResponse('error', null, 'Data tidak lengkap atau ID tidak valid', 400);
        }

        $stmt = $conn->prepare("UPDATE mata_pelajaran SET nama_mapel=?, kode_mapel=?, guru_id=? WHERE id=?");
        $stmt->bind_param("ssii", $nama_mapel, $kode_mapel, $guru_id, $id);
        if ($stmt->execute()) {
            jsonResponse('success', null, 'Mata pelajaran berhasil diperbarui');
        } else {
            jsonResponse('error', null, 'Gagal memperbarui mata pelajaran', 400);
        }
    }
    else if ($method === 'DELETE') {
        requireApiAdmin();
        $id = intval($input['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) jsonResponse('error', null, 'ID Mapel wajib diisi', 400);

        $stmt = $conn->prepare("DELETE FROM mata_pelajaran WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            jsonResponse('success', null, 'Mata pelajaran berhasil dihapus');
        } else {
            jsonResponse('error', null, 'Gagal menghapus mata pelajaran', 400);
        }
    }
    else {
        jsonResponse('error', null, 'Metode HTTP tidak didukung', 405);
    }
} catch (Exception $e) {
    jsonResponse('error', null, 'Internal error: ' . $e->getMessage(), 500);
}
?>
