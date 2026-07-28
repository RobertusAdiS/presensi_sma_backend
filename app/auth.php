<?php
/**
 * Authentication Module for Backend API
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Login user (Admin / Guru)
 */
function login($username, $password, $conn) {
    try {
        $stmt = $conn->prepare("SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ? AND is_active = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];
                return $user;
            }
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check user role
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Logout user
 */
function logout() {
    session_unset();
    session_destroy();
}

/**
 * Get current logged in user
 */
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'nama_lengkap' => $_SESSION['nama_lengkap'],
            'role' => $_SESSION['role']
        ];
    }
    return null;
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}
?>
