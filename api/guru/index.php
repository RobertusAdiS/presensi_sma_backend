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
    // GET: List or Detail Guru
    if ($method === 'GET') {
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT g.*, u.nama_lengkap, u.email, u.username, u.is_active FROM guru g JOIN users u ON g.user_id = u.id WHERE g.id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();
            if ($data) {
                jsonResponse('success', $data, 'Data guru ditemukan');
            } else {
                jsonResponse('error', null, 'Guru tidak ditemukan', 404);
            }
        } else {
            $data = getAllGuru($conn);
            jsonResponse('success', $data, 'Seluruh data guru');
        }
    }

    // POST: Create Guru
    else if ($method === 'POST') {
        requireApiAdmin();
        $nip = sanitize($input['nip'] ?? '');
        $nama_lengkap = sanitize($input['nama_lengkap'] ?? '');
        $username = sanitize($input['username'] ?? '');
        $email = sanitize($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $no_telp = sanitize($input['no_telp'] ?? '');
        $alamat = sanitize($input['alamat'] ?? '');
        $jenis_kelamin = sanitize($input['jenis_kelamin'] ?? '');

        if (empty($nip) || empty($nama_lengkap) || empty($username) || empty($password)) {
            jsonResponse('error', null, 'NIP, Nama, Username, dan Password wajib diisi', 400);
        }

        $hashed_password = hashPassword($password);
        $role = 'guru';

        $conn->begin_transaction();
        try {
            $stmt_user = $conn->prepare("INSERT INTO users (username, password, nama_lengkap, email, role) VALUES (?, ?, ?, ?, ?)");
            $stmt_user->bind_param("sssss", $username, $hashed_password, $nama_lengkap, $email, $role);
            $stmt_user->execute();
            $user_id = $stmt_user->insert_id;

            $stmt_guru = $conn->prepare("INSERT INTO guru (user_id, nip, no_telp, alamat, jenis_kelamin) VALUES (?, ?, ?, ?, ?)");
            $stmt_guru->bind_param("issss", $user_id, $nip, $no_telp, $alamat, $jenis_kelamin);
            $stmt_guru->execute();

            $conn->commit();
            jsonResponse('success', ['id' => $stmt_guru->insert_id], 'Guru berhasil ditambahkan');
        } catch (Exception $e) {
            $conn->rollback();
            jsonResponse('error', null, 'NIP atau Username sudah terdaftar', 400);
        }
    }

    // PUT: Update Guru
    else if ($method === 'PUT') {
        requireApiAdmin();
        $id = intval($input['id'] ?? $_GET['id'] ?? 0);
        $nip = sanitize($input['nip'] ?? '');
        $nama_lengkap = sanitize($input['nama_lengkap'] ?? '');
        $email = sanitize($input['email'] ?? '');
        $no_telp = sanitize($input['no_telp'] ?? '');
        $alamat = sanitize($input['alamat'] ?? '');
        $jenis_kelamin = sanitize($input['jenis_kelamin'] ?? '');

        if ($id <= 0 || empty($nip) || empty($nama_lengkap)) {
            jsonResponse('error', null, 'ID Guru, NIP dan Nama wajib diisi', 400);
        }

        $stmt_get = $conn->prepare("SELECT user_id FROM guru WHERE id = ?");
        $stmt_get->bind_param("i", $id);
        $stmt_get->execute();
        $user_id = $stmt_get->get_result()->fetch_assoc()['user_id'] ?? 0;

        if ($user_id <= 0) {
            jsonResponse('error', null, 'Guru tidak ditemukan', 404);
        }

        $conn->begin_transaction();
        try {
            $stmt_u = $conn->prepare("UPDATE users SET nama_lengkap=?, email=? WHERE id=?");
            $stmt_u->bind_param("ssi", $nama_lengkap, $email, $user_id);
            $stmt_u->execute();

            $stmt_g = $conn->prepare("UPDATE guru SET nip=?, no_telp=?, alamat=?, jenis_kelamin=? WHERE id=?");
            $stmt_g->bind_param("ssssi", $nip, $no_telp, $alamat, $jenis_kelamin, $id);
            $stmt_g->execute();

            $conn->commit();
            jsonResponse('success', null, 'Data guru berhasil diperbarui');
        } catch (Exception $e) {
            $conn->rollback();
            jsonResponse('error', null, 'Gagal mengedit guru: ' . $e->getMessage(), 400);
        }
    }

    // DELETE: Remove Guru
    else if ($method === 'DELETE') {
        requireApiAdmin();
        $id = intval($input['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse('error', null, 'ID Guru wajib diisi', 400);
        }

        $stmt_get = $conn->prepare("SELECT user_id FROM guru WHERE id = ?");
        $stmt_get->bind_param("i", $id);
        $stmt_get->execute();
        $user_id = $stmt_get->get_result()->fetch_assoc()['user_id'] ?? 0;

        if ($user_id > 0) {
            $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            jsonResponse('success', null, 'Guru berhasil dinonaktifkan');
        } else {
            jsonResponse('error', null, 'Guru tidak ditemukan', 404);
        }
    }

    else {
        jsonResponse('error', null, 'Metode HTTP tidak didukung', 405);
    }
} catch (Exception $e) {
    jsonResponse('error', null, 'Internal error: ' . $e->getMessage(), 500);
}
?>
