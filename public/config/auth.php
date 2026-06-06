<?php
// config/auth.php
require_once __DIR__ . '/db_connect.php';
// FIX #1: db_connect already starts session with a guard — no second session_start needed here

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        header('Location: dashboard.php');
        exit;
    }
}

function loginUser(int $userId, string $name, string $role): void {
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);
    $_SESSION['user_id']   = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_role'] = $role;
}

function logoutUser(): void {
    $_SESSION = [];
    // Clear the session cookie properly
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }
    session_destroy();
}
