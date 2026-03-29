<?php
// Authentication check
require_once __DIR__ . '/session.php';
secure_session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
?>
