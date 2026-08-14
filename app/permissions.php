<?php
/**
 * Role-Based Access Control (RBAC) with Permissions
 * Manages permissions for different roles
 */

const PERMISSIONS = [
    'dashboard.view' => ['admin', 'guru'],
    
    'siswa.view' => ['admin', 'guru'],
    'siswa.create' => ['admin'],
    'siswa.edit' => ['admin'],
    'siswa.delete' => ['admin'],
    
    'guru.view' => ['admin'],
    'guru.create' => ['admin'],
    'guru.edit' => ['admin'],
    'guru.delete' => ['admin'],
    
    'kelas.view' => ['admin', 'guru'],
    'kelas.create' => ['admin'],
    'kelas.edit' => ['admin'],
    'kelas.delete' => ['admin'],
    
    'mapel.view' => ['admin', 'guru'],
    'mapel.create' => ['admin'],
    'mapel.edit' => ['admin'],
    'mapel.delete' => ['admin'],
    
    'jadwal.view' => ['admin', 'guru'],
    'jadwal.create' => ['admin'],
    'jadwal.edit' => ['admin'],
    'jadwal.delete' => ['admin'],
    
    'absensi.view' => ['admin', 'guru'],
    'absensi.input' => ['admin', 'guru'],
    'absensi.scan' => ['student'],
    
    'laporan.view' => ['admin', 'guru'],
    'laporan.export' => ['admin']
];

const ROLE_PERMISSIONS = [
    'admin' => [
        'dashboard.view',
        'siswa.view', 'siswa.create', 'siswa.edit', 'siswa.delete',
        'guru.view', 'guru.create', 'guru.edit', 'guru.delete',
        'kelas.view', 'kelas.create', 'kelas.edit', 'kelas.delete',
        'mapel.view', 'mapel.create', 'mapel.edit', 'mapel.delete',
        'jadwal.view', 'jadwal.create', 'jadwal.edit', 'jadwal.delete',
        'absensi.view', 'absensi.input',
        'laporan.view', 'laporan.export'
    ],
    'guru' => [
        'dashboard.view',
        'siswa.view',
        'kelas.view',
        'mapel.view',
        'jadwal.view',
        'absensi.view', 'absensi.input',
        'laporan.view'
    ],
    'student' => [
        'absensi.scan'
    ]
];

/**
 * Check if user has a specific permission
 */
function hasPermission($permission) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    $role = $_SESSION['role'];
    $permissions = ROLE_PERMISSIONS[$role] ?? [];
    
    return in_array($permission, $permissions);
}

/**
 * Check if user has any of the specified permissions
 */
function hasAnyPermission($permissions) {
    foreach ($permissions as $permission) {
        if (hasPermission($permission)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if user has all of the specified permissions
 */
function hasAllPermissions($permissions) {
    foreach ($permissions as $permission) {
        if (!hasPermission($permission)) {
            return false;
        }
    }
    return true;
}

/**
 * Require specific permission, return error if not authorized
 */
function requirePermission($permission) {
    if (!hasPermission($permission)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Anda tidak memiliki izin untuk mengakses resource ini.'
        ]);
        exit();
    }
}

/**
 * Get all permissions for current user
 */
function getUserPermissions() {
    if (!isset($_SESSION['role'])) {
        return [];
    }
    
    return ROLE_PERMISSIONS[$_SESSION['role']] ?? [];
}

/**
 * Get all available permissions
 */
function getAllPermissions() {
    return array_keys(PERMISSIONS);
}

/**
 * Get permissions by role
 */
function getPermissionsByRole($role) {
    return ROLE_PERMISSIONS[$role] ?? [];
}
?>
