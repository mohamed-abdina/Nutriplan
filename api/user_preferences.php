<?php
// User preferences API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';
secure_session_start();

require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
require_once '../includes/rate_limit.php';
require_once '../includes/error_logger.php';
require_once __DIR__ . '/../includes/csrf.php';

// Apply rate limiting (10 requests per 60 seconds)
if (!$limiter->check_rate_limit('user_preferences', 10, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? sanitize_input($_POST['action']) : '';

$error_logger->log_api_call('user_preferences', 'POST', ['action' => $action], 200);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!validate_csrf($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

if ($action === 'get') {
    $prefs = pdo_fetch_one("SELECT * FROM user_preferences WHERE user_id = :user_id", [':user_id' => (int)$user_id]);
    if ($prefs) {
        echo json_encode(['success' => true, 'preferences' => $prefs]);
    } else {
        // Create default preferences if not exist
        pdo_query("INSERT INTO user_preferences (user_id) VALUES (:user_id)", [':user_id' => (int)$user_id]);
        echo json_encode([
            'success' => true,
            'preferences' => [
                'portion_size' => 'normal',
                'dietary_restrictions' => '',
                'allergies' => '',
                'preferred_cuisine' => '',
                'notifications_enabled' => true,
                'theme_preference' => 'dark'
            ]
        ]);
    }
}

elseif ($action === 'update') {
    $portion_size = isset($_POST['portion_size']) ? sanitize_input($_POST['portion_size']) : 'normal';
    $dietary_restrictions = isset($_POST['dietary_restrictions']) ? sanitize_input($_POST['dietary_restrictions']) : '';
    $allergies = isset($_POST['allergies']) ? sanitize_input($_POST['allergies']) : '';
    $preferred_cuisine = isset($_POST['preferred_cuisine']) ? sanitize_input($_POST['preferred_cuisine']) : '';
    $notifications = isset($_POST['notifications_enabled']) ? (int)$_POST['notifications_enabled'] : 1;
    $theme = isset($_POST['theme_preference']) ? sanitize_input($_POST['theme_preference']) : 'dark';
    
    // Validate portion size
    $valid_sizes = ['small', 'normal', 'large', 'extra-large'];
    if (!in_array($portion_size, $valid_sizes)) {
        $portion_size = 'normal';
    }
    
    // Validate theme
    if (!in_array($theme, ['light', 'dark'])) {
        $theme = 'dark';
    }
    
    $sql = "INSERT INTO user_preferences (user_id, portion_size, dietary_restrictions, allergies, preferred_cuisine, notifications_enabled, theme_preference)
            VALUES (:user_id, :portion_size, :dietary_restrictions, :allergies, :preferred_cuisine, :notifications_enabled, :theme_preference)
            ON DUPLICATE KEY UPDATE 
            portion_size = :portion_size_upd,
            dietary_restrictions = :dietary_restrictions_upd,
            allergies = :allergies_upd,
            preferred_cuisine = :preferred_cuisine_upd,
            notifications_enabled = :notifications_enabled_upd,
            theme_preference = :theme_preference_upd,
            updated_at = CURRENT_TIMESTAMP";

    $params = [
        ':user_id' => (int)$user_id,
        ':portion_size' => $portion_size,
        ':dietary_restrictions' => $dietary_restrictions,
        ':allergies' => $allergies,
        ':preferred_cuisine' => $preferred_cuisine,
        ':notifications_enabled' => (int)$notifications,
        ':theme_preference' => $theme,
        ':portion_size_upd' => $portion_size,
        ':dietary_restrictions_upd' => $dietary_restrictions,
        ':allergies_upd' => $allergies,
        ':preferred_cuisine_upd' => $preferred_cuisine,
        ':notifications_enabled_upd' => (int)$notifications,
        ':theme_preference_upd' => $theme
    ];

    if (pdo_query($sql, $params) !== false) {
        $error_logger->log_security_event('PREFERENCES_UPDATED', ['user_id' => $user_id]);
        echo json_encode(['success' => true, 'message' => 'Preferences updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating preferences']);
        error_log('Preferences error');
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
