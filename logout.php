<?php
require_once __DIR__ . '/includes/session.php';
secure_session_start();
session_destroy();
header('Location: index.php');
exit;
?>
