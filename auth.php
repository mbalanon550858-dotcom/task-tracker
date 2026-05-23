<?php
// ============================================================
// includes/auth.php  — Session & Auth Helpers
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

function requireGuest() {
    if (isLoggedIn()) {
        header('Location: dashboard.php');
        exit();
    }
}

function currentUser() {
    return [
        'id'       => $_SESSION['user_id']   ?? null,
        'username' => $_SESSION['username']  ?? null,
        'email'    => $_SESSION['email']     ?? null,
    ];
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
