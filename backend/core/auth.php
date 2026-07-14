<?php
/**
 * Authentication & Session Management
 */

session_start();
require_once __DIR__ . '/../config/database.php';

/**
 * Detect the project base URL dynamically from the filesystem path.
 * Works regardless of whether accessed via localhost, IP, or domain.
 */
function getBaseUrl() {
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);

    if ($scriptName === '/' || $scriptName === '\\') {
        return '';
    }

    return rtrim($scriptName, '/');
}

/**
 * Attempt login with username and password
 */
function login($username, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

/**
 * Logout and destroy session
 */
function logout() {
    session_unset();
    session_destroy();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user data from session
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'name'     => $_SESSION['name'],
        'role'     => $_SESSION['role']
    ];
}

/**
 * Require a specific role to access a page
 * Redirects to login if not authenticated or wrong role
 */
function requireRole($requiredRole) {
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . '/index.php');
        exit;
    }
    $loginUrl = getBaseUrl() . '/index.php';
    if (is_array($requiredRole)) {
        if (!in_array($_SESSION['role'], $requiredRole)) {
            header('HTTP/1.1 403 Forbidden');
            echo '<h1>Acceso Denegado</h1><p>No tienes permisos para acceder a esta sección.</p>';
            echo '<a href="' . $loginUrl . '">Volver al inicio</a>';
            exit;
        }
    } else {
        if ($_SESSION['role'] !== $requiredRole) {
            header('HTTP/1.1 403 Forbidden');
            echo '<h1>Acceso Denegado</h1><p>No tienes permisos para acceder a esta sección.</p>';
            echo '<a href="' . $loginUrl . '">Volver al inicio</a>';
            exit;
        }
    }
}

/**
 * Get redirect URL based on user role
 */
function getRoleDashboard($role) {
    $base = getBaseUrl();
    switch ($role) {
        case 'admin':  return $base . '/public/admin/admin.php';
        case 'server': return $base . '/public/server/server.php';
        case 'cook':   return $base . '/public/kitchen/kitchen.php';
        default:       return $base . '/index.php';
    }
}

/**
 * Get all users (admin only)
 */
function getAllUsers() {
    $db = getDB();
    return $db->query("SELECT id, username, name, role, active, created_at FROM users ORDER BY role, name")->fetchAll();
}

/**
 * Create a new user (admin only)
 */
function createUser($username, $password, $name, $role) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO users (username, password_hash, name, role) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $name, $role]);
}

/**
 * Toggle user active status (admin only)
 */
function toggleUserStatus($userId) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET active = NOT active WHERE id = ?");
    return $stmt->execute([$userId]);
}
?>
