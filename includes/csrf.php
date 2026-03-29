<?php
// CSRF helpers
require_once __DIR__ . '/session.php';

function csrf_token() {
    if (session_status() !== PHP_SESSION_ACTIVE) secure_session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $t = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '">';
}

function validate_csrf($token) {
    if (session_status() !== PHP_SESSION_ACTIVE) secure_session_start();
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

?>
