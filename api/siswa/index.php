<?php
require_once __DIR__ . '/../../config/database_native.php';
require_once __DIR__ . '/../../app/middleware.php';
require_once __DIR__ . '/../../app/permissions.php';
require_once __DIR__ . '/../../app/helpers_native.php';

requireApiLogin();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
if (isset($input['_method'])) {
    $method = strtoupper($input['_method']);
}

try {
    // GET: Retrieve Siswa List or Detail
    if ($method === 'GET') {
        $id = intval($_GET['id'] ?? 0);
        $kelas_id = intval($_GET['kelas_id'] ?? 0);

        if ($id > 0) {
            $stmt = $conn->prepare("SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.kelas_id = k.id WHERE s.id = ? AND s.is_active = 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();
            if ($data) {
                jsonResponse('success', $data, 'Data siswa ditemukan');
            } else {
                jsonResponse('error', null, 'Data siswa tidak ditemukan', 404);
            }
        } else if ($kelas_id > 0) {
            $data = getSiswaByKelas($kelas_id, $conn);
            jsonResponse('success', $data, 'Data siswa per kelas');
        } else {
            $result = query("SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.kelas_id = k.id WHERE s.is_active = 1 ORDER BY k.nama_kelas, s.nama_lengkap", $conn);
            $data = $result->fetch_all(MYSQLI_ASSOC);
            jsonResponse('success', $data, 'Seluruh data siswa');
        }
    }

    // POST: Create New Siswa
     else if ($method === 'POST') {
         requirePermission('siswa.create');
        $nisn = sanitize($input['nisn'] ?? '');
        $nama = sanitize($input['nama_lengkap'] ?? '');
        $jenis_kelamin = sanitize($input['jenis_kelamin'] ?? '');
        $tanggal_lahir = sanitize($input['tanggal_lahir'] ?? '');
        $no_telp = sanitize($input['no_telp'] ?? '');
        $alamat = sanitize($input['alamat'] ?? '');
        $kelas_id = intval($input['kelas_id'] ?? 0);

        if (empty($nisn) || empty($nama) || empty($jenis_kelamin) || $kelas_id <= 0) {
            jsonResponse('error', null, 'Semua field wajib diisi', 400);
        }

        $stmt = $conn->prepare("INSERT INTO siswa (nisn, nama_lengkap, jenis_kelamin, tanggal_lahir, no_telp, alamat, kelas_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", $nisn, $nama, $jenis_kelamin, $tanggal_lahir, $no_telp, $alamat, $kelas_id);

        if ($stmt->execute()) {
            jsonResponse('success', ['id' => $stmt->insert_id], 'Siswa berhasil ditambahkan');
        } else {
            jsonResponse('error', null, 'NISN sudah terdaftar atau terjadi kesalahan', 400);
        }
    }

    // PUT: Update Siswa
     else if ($method === 'PUT') {
         requirePermission('siswa.edit');
        $id = intval($input['id'] ?? $_GET['id'] ?? 0);
        $nisn = sanitize($input['nisn'] ?? '');
        $nama = sanitize($input['nama_lengkap'] ?? '');
        $jenis_kelamin = sanitize($input['jenis_kelamin'] ?? '');
        $tanggal_lahir = sanitize($input['tanggal_lahir'] ?? '');
        $no_telp = sanitize($input['no_telp'] ?? '');
        $alamat = sanitize($input['alamat'] ?? '');
        $kelas_id = intval($input['kelas_id'] ?? 0);

        if ($id <= 0 || empty($nisn) || empty($nama) || empty($jenis_kelamin) || $kelas_id <= 0) {
            jsonResponse('error', null, 'Data tidak lengkap atau ID tidak valid', 400);
        }

        $stmt = $conn->prepare("UPDATE siswa SET nisn=?, nama_lengkap=?, jenis_kelamin=?, tanggal_lahir=?, no_telp=?, alamat=?, kelas_id=? WHERE id=?");
        $stmt->bind_param("ssssssii", $nisn, $nama, $jenis_kelamin, $tanggal_lahir, $no_telp, $alamat, $kelas_id, $id);

        if ($stmt->execute()) {
            jsonResponse('success', null, 'Data siswa berhasil diperbarui');
        } else {
            jsonResponse('error', null, 'Gagal memperbarui data siswa', 400);
        }
    }

    // DELETE: Deactivate/Delete Siswa
     else if ($method === 'DELETE') {
         requirePermission('siswa.delete');
        $id = intval($input['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse('error', null, 'ID Siswa wajib diisi', 400);
        }

        $stmt = $conn->prepare("UPDATE siswa SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            jsonResponse('success', null, 'Siswa berhasil dihapus');
        } else {
            jsonResponse('error', null, 'Gagal menghapus siswa', 400);
        }
    }

    else {
        jsonResponse('error', null, 'Metode HTTP tidak didukung', 405);
    }
} catch (Exception $e) {
    jsonResponse('error', null, 'Internal error: ' . $e->getMessage(), 500);
}
?>
