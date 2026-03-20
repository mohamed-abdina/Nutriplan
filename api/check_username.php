<?php
// Username availability check
header('Content-Type: application/json');

require_once '../includes/db_connect.php';

$username = isset($_GET['username']) ? sanitize_input($_GET['username']) : '';

if (strlen($username) < 3) {
    echo json_encode(['available' => false, 'message' => 'Too short']);
    exit;
}

$result = $conn->query("SELECT user_id FROM users WHERE username = '$username'");

if ($result->num_rows > 0) {
    echo json_encode(['available' => false, 'message' => 'Username taken']);
} else {
    echo json_encode(['available' => true, 'message' => 'Username available']);
}
?>
