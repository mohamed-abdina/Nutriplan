<?php
// Avatar upload
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';
secure_session_start();

require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../includes/rate_limit.php';
require_once '../includes/error_logger.php';
require_once __DIR__ . '/../includes/csrf.php';

// Apply rate limiting to file uploads (3 uploads per 60 seconds)
if (!$limiter->check_rate_limit('upload_avatar', 3, 60)) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Too many uploads. Please try again later.'
    ]);
    $error_logger->log_security_event('UPLOAD_RATE_LIMIT_EXCEEDED', ['user_id' => $_SESSION['user_id']]);
    exit;
}

// Log file upload attempt
$error_logger->log_api_call('upload_avatar', 'POST', [], 200);

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!validate_csrf($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

if (!isset($_FILES['avatar'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$result = upload_avatar($conn, $user_id, $_FILES['avatar']);
echo json_encode($result);
?>
