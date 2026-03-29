<?php
require_once __DIR__ . '/includes/session.php';
secure_session_start();

require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once __DIR__ . '/includes/csrf.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
	exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!validate_csrf($csrf_token)) {
	http_response_code(403);
	echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
	exit;
}

$user_id = $_SESSION['user_id'];

// Use PDO prepared statements for all deletions
try {
	$pdo->beginTransaction();
	// Delete shopping items
	$stmt = $pdo->prepare("DELETE FROM shopping_items WHERE list_id IN (SELECT list_id FROM shopping_lists WHERE user_id = :user_id)");
	$stmt->execute([':user_id' => $user_id]);
	// Delete shopping lists
	$stmt = $pdo->prepare("DELETE FROM shopping_lists WHERE user_id = :user_id");
	$stmt->execute([':user_id' => $user_id]);
	// Delete user
	$stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :user_id");
	$stmt->execute([':user_id' => $user_id]);
	$pdo->commit();
	session_destroy();
	echo json_encode(['success' => true, 'message' => 'Account deleted']);
} catch (Exception $e) {
	$pdo->rollBack();
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Account deletion failed.']);
}
?>
