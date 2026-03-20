<?php
// Avatar upload
header('Content-Type: application/json');
session_start();

require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

$user_id = $_SESSION['user_id'];

if (!isset($_FILES['avatar'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$result = upload_avatar($conn, $user_id, $_FILES['avatar']);
echo json_encode($result);
?>
