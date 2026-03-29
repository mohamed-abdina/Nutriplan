<?php
require_once __DIR__ . '/includes/session.php';
secure_session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];

// Delete user and all associated data
$conn->query("DELETE FROM shopping_items WHERE list_id IN (SELECT list_id FROM shopping_lists WHERE user_id = $user_id)");
$conn->query("DELETE FROM shopping_lists WHERE user_id = $user_id");
$conn->query("DELETE FROM users WHERE user_id = $user_id");

// Destroy session
session_destroy();

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Account deleted']);
?>
