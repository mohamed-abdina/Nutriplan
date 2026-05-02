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

// Use PDO prepared statements and rely on CASCADE constraints for safety
try {
	$pdo->beginTransaction();
	
	// Delete user - all related data will cascade delete due to foreign key constraints:
	// - meal_ratings (ON DELETE CASCADE)
	// - user_preferences (ON DELETE CASCADE)
	// - shopping_lists (ON DELETE CASCADE)
	//   - shopping_items (ON DELETE CASCADE)
	// - meal_planning (ON DELETE CASCADE)
	$stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :user_id");
	$stmt->execute([':user_id' => $user_id]);
	
	$pdo->commit();
	session_destroy();
	echo json_encode(['success' => true, 'message' => 'Account deleted']);
} catch (Exception $e) {
	$pdo->rollBack();
	error_log('Account deletion error: ' . $e->getMessage());
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Account deletion failed.']);
}
?>
