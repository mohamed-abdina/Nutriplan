<?php
// Username availability check
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';
secure_session_start();

require_once '../includes/db_connect.php';
require_once '../includes/rate_limit.php';
require_once '../includes/error_logger.php';
require_once __DIR__ . '/../includes/csrf.php';

// Apply strict rate limiting to prevent username enumeration (5 requests per 60 seconds)
if (!$limiter->check_rate_limit('check_username', 5, 60)) {
    http_response_code(429);
    echo json_encode([
        'available' => false,
        'message' => 'Too many requests. Please try again later.'
    ]);
    $error_logger->log_security_event('USERNAME_ENUMERATION_ATTEMPT', ['ip' => $_SERVER['REMOTE_ADDR']]);
    exit;
}

$username = isset($_GET['username']) ? sanitize_input($_GET['username']) : '';

// Log username check attempt
$error_logger->log_api_call('check_username', 'GET', ['username' => $username], 200);

if (strlen($username) < 3) {
    echo json_encode(['available' => false, 'message' => 'Too short']);
    exit;
}

$row = pdo_fetch_one("SELECT user_id FROM users WHERE username = :username", [':username' => $username]);

if ($row) {
    echo json_encode(['available' => false, 'message' => 'Username taken']);
} else {
    echo json_encode(['available' => true, 'message' => 'Username available']);
}
?>
