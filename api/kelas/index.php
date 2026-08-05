<?php
require_once __DIR__ . '/../../config/database_native.php';
require_once __DIR__ . '/../../app/middleware.php';
require_once __DIR__ . '/../../app/helpers_native.php';

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
            $stmt = $conn->prepare("SELECT k.*, u.nama_lengkap as guru_nama FROM kelas k LEFT JOIN guru g ON k.guru_id = g.id LEFT JOIN users u ON g.user_id = u.id WHERE k.id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();
            if ($data) jsonResponse('success', $data, 'Data kelas ditemukan');
            else jsonResponse('error', null, 'Kelas tidak ditemukan', 404);
        } else {
            $data = getAllKelas($conn);
            jsonResponse('success', $data, 'Seluruh data kelas');
        }
    }
    else if ($method === 'POST') {
        requirePermission('kelas.create');
        $nama_kelas = sanitize($input['nama_kelas'] ?? '');
        $tingkat = sanitize($input['tingkat'] ?? '');
        $jurusan = sanitize($input['jurusan'] ?? '');
        $guru_id = intval($input['guru_id'] ?? 0);

        if (empty($nama_kelas) || empty($tingkat)) {
            jsonResponse('error', null, 'Nama kelas dan Tingkat wajib diisi', 400);
        }

        $stmt = $conn->prepare("INSERT INTO kelas (nama_kelas, tingkat, jurusan, guru_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $nama_kelas, $tingkat, $jurusan, $guru_id);
        if ($stmt->execute()) {
            jsonResponse('success', ['id' => $stmt->insert_id], 'Kelas berhasil ditambahkan');
        } else {
            jsonResponse('error', null, 'Gagal menambah kelas', 400);
        }
    }
    else if ($method === 'PUT') {
        requirePermission('kelas.edit');
        $id = intval($input['id'] ?? $_GET['id'] ?? 0);
        $nama_kelas = sanitize($input['nama_kelas'] ?? '');
        $tingkat = sanitize($input['tingkat'] ?? '');
        $jurusan = sanitize($input['jurusan'] ?? '');
        $guru_id = intval($input['guru_id'] ?? 0);

        if ($id <= 0 || empty($nama_kelas) || empty($tingkat)) {
            jsonResponse('error', null, 'ID Kelas, Nama Kelas, dan Tingkat wajib diisi', 400);
        }

        $stmt = $conn->prepare("UPDATE kelas SET nama_kelas=?, tingkat=?, jurusan=?, guru_id=? WHERE id=?");
        $stmt->bind_param("sssii", $nama_kelas, $tingkat, $jurusan, $guru_id, $id);
        if ($stmt->execute()) {
            jsonResponse('success', null, 'Data kelas berhasil diperbarui');
        } else {
            jsonResponse('error', null, 'Gagal memperbarui kelas', 400);
        }
    }
    else if ($method === 'DELETE') {
        requirePermission('kelas.delete');
        $id = intval($input['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) jsonResponse('error', null, 'ID Kelas wajib diisi', 400);

        $stmt = $conn->prepare("DELETE FROM kelas WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            jsonResponse('success', null, 'Kelas berhasil dihapus');
        } else {
            jsonResponse('error', null, 'Gagal menghapus kelas', 400);
        }
    }
    else {
        jsonResponse('error', null, 'Metode HTTP tidak didukung', 405);
    }
} catch (Exception $e) {
    jsonResponse('error', null, 'Internal error: ' . $e->getMessage(), 500);
}
?>
