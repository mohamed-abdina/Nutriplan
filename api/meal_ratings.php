<?php
// Meal ratings and favorites API - DEPRECATED: Use wishlist_api.php instead
// This file now provides backward compatibility with legacy action names
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';
secure_session_start();

require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
require_once '../includes/rate_limit.php';
require_once '../includes/error_logger.php';
require_once __DIR__ . '/../includes/csrf.php';

// Log deprecation warning
$error_logger->log_warning('DEPRECATION_WARNING', [
    'deprecated_endpoint' => 'api/meal_ratings.php',
    'replacement' => 'api/wishlist_api.php',
    'action' => $_POST['action'] ?? 'unknown'
]);

// Apply rate limiting (15 requests per 60 seconds)
if (!$limiter->check_rate_limit('meal_ratings', 15, 60)) {
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
$meal_id = isset($_POST['meal_id']) ? (int)$_POST['meal_id'] : 0;

$error_logger->log_api_call('meal_ratings', 'POST', ['action' => $action, 'meal_id' => $meal_id], 200);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!validate_csrf($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

if ($action === 'rate') {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $review = isset($_POST['review']) ? sanitize_input($_POST['review']) : '';
    
    if ($meal_id <= 0 || $rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid rating']);
        exit;
    }
    
    // Check if meal exists
    $meal_check = pdo_fetch_one("SELECT meal_id FROM meals WHERE meal_id = :meal_id", [':meal_id' => $meal_id]);
    if (!$meal_check) {
        echo json_encode(['success' => false, 'message' => 'Invalid meal']);
        exit;
    }

    // Insert or update rating using prepared statement
    $sql = "INSERT INTO meal_ratings (user_id, meal_id, rating, review) 
            VALUES (:user_id, :meal_id, :rating, :review)
            ON DUPLICATE KEY UPDATE rating = :rating_upd, review = :review_upd, updated_at = CURRENT_TIMESTAMP";

    $params = [
        ':user_id' => (int)$user_id,
        ':meal_id' => (int)$meal_id,
        ':rating' => (int)$rating,
        ':review' => $review,
        ':rating_upd' => (int)$rating,
        ':review_upd' => $review
    ];

    if (pdo_query($sql, $params) !== false) {
        echo json_encode(['success' => true, 'message' => 'Rating saved']);
        $error_logger->log_action('rate', true, ['meal_id' => $meal_id, 'rating' => $rating]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error saving rating']);
        $error_logger->log_action('rate', false, ['meal_id' => $meal_id, 'rating' => $rating, 'reason' => 'Database insert failed']);
    }
}

elseif ($action === 'toggle_favorite') {
    if ($meal_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid meal']);
        exit;
    }
    
    // CRITICAL: Validate meal exists before toggling
    $meal_check = pdo_fetch_one("SELECT meal_id FROM meals WHERE meal_id = :meal_id", [':meal_id' => (int)$meal_id]);
    if (!$meal_check) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Meal not found']);
        $error_logger->log_action('toggle_favorite', false, ['meal_id' => $meal_id, 'reason' => 'Meal does not exist']);
        exit;
    }
    
    // Check if rating exists, if not create one
    $check = pdo_fetch_one("SELECT rating_id, is_wishlisted FROM meal_ratings WHERE user_id = :user_id AND meal_id = :meal_id", [':user_id' => (int)$user_id, ':meal_id' => (int)$meal_id]);
    
    if ($check) {
        $is_favorite = $check['is_wishlisted'] ? 0 : 1;
        $res = pdo_query("UPDATE meal_ratings SET is_wishlisted = :fav WHERE user_id = :user_id AND meal_id = :meal_id", [':fav' => $is_favorite, ':user_id' => (int)$user_id, ':meal_id' => (int)$meal_id]);
        $new_fav = (bool)!$check['is_wishlisted'];
    } else {
        // Create new rating with favorite flag
        $res = pdo_query("INSERT INTO meal_ratings (user_id, meal_id, is_wishlisted) VALUES (:user_id, :meal_id, 1)", [':user_id' => (int)$user_id, ':meal_id' => (int)$meal_id]);
        $new_fav = true;
    }
    
    if ($res !== false) {
        echo json_encode(['success' => true, 'is_favorite' => $new_fav]);
        $error_logger->log_action('toggle_favorite', true, ['meal_id' => $meal_id, 'favorite_status' => $new_fav]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error updating favorite']);
        $error_logger->log_action('toggle_favorite', false, ['meal_id' => $meal_id, 'reason' => 'Database update failed']);
    }
}

elseif ($action === 'get_rating') {
    if ($meal_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid meal']);
        exit;
    }
    
    $rating = pdo_fetch_one("SELECT rating, review, is_wishlisted FROM meal_ratings WHERE user_id = :user_id AND meal_id = :meal_id", [':user_id' => (int)$user_id, ':meal_id' => (int)$meal_id]);
    if ($rating) {
        echo json_encode([
            'success' => true,
            'rating' => $rating['rating'] ?? 0,
            'review' => $rating['review'] ?? '',
            'is_favorite' => (bool)$rating['is_wishlisted']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'rating' => 0,
            'review' => '',
            'is_favorite' => false
        ]);
    }
}

elseif ($action === 'get_favorites') {
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 6;
    $rows = pdo_fetch_all("SELECT m.meal_id, m.meal_name, m.meal_icon, c.category_name, n.calories, n.proteins_g, r.rating, r.is_wishlisted, r.review
        FROM meal_ratings r
        JOIN meals m ON r.meal_id = m.meal_id
        JOIN categories c ON m.category_id = c.category_id
        JOIN nutrition n ON m.meal_id = n.meal_id
        WHERE r.user_id = :user_id AND r.is_wishlisted = 1
        ORDER BY r.rating DESC, r.updated_at DESC
        LIMIT :limit", [':user_id' => (int)$user_id, ':limit' => $limit]);

    echo json_encode(['success' => true, 'favorites' => $rows ?: []]);
}

elseif ($action === 'get_top_rated') {
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 5;
    $rows = pdo_fetch_all("SELECT m.meal_id, m.meal_name, m.meal_icon, c.category_name, n.calories, n.proteins_g, r.rating, COUNT(*) as times_rated
        FROM meal_ratings r
        JOIN meals m ON r.meal_id = m.meal_id
        JOIN categories c ON m.category_id = c.category_id
        JOIN nutrition n ON m.meal_id = n.meal_id
        WHERE r.user_id = :user_id AND r.rating >= 4
        GROUP BY r.meal_id
        ORDER BY r.rating DESC, r.updated_at DESC
        LIMIT :limit", [':user_id' => (int)$user_id, ':limit' => $limit]);

    echo json_encode(['success' => true, 'top_rated' => $rows ?: []]);
}

elseif ($action === 'get_favorites_count') {
    $count_result = pdo_fetch_one("SELECT COUNT(*) as count FROM meal_ratings WHERE user_id = :user_id AND is_wishlisted = 1", [':user_id' => (int)$user_id]);
    $count = (int)($count_result['count'] ?? 0);
    echo json_encode(['success' => true, 'count' => $count]);
}

else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    $error_logger->log_action('invalid_action', false, ['action' => $action]);
}
?>
