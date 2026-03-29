<?php
// API endpoint for searching meals
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';
secure_session_start();

require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/rate_limit.php';
require_once '../includes/error_logger.php';

// Apply rate limiting (20 requests per 60 seconds for search - more lenient since it's public)
if (!$limiter->check_rate_limit('search_api', 20, 60)) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Too many search requests. Please try again later.',
        'remaining' => 0
    ]);
    $error_logger->log_security_event('RATE_LIMIT_EXCEEDED', ['endpoint' => 'search_api']);
    exit;
}

$query = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';
$category = isset($_GET['cat']) ? sanitize_input($_GET['cat']) : '';
$nutrition = isset($_GET['nut']) ? sanitize_input($_GET['nut']) : '';

// Log API call
$error_logger->log_api_call('search_api', 'GET', ['query' => $query, 'category' => $category], 200);

$sql = "SELECT m.meal_id, m.meal_name, m.meal_icon, c.category_name, c.category_id,
        n.calories, n.proteins_g, n.carbs_g, n.fats_g, n.fiber_g
FROM meals m
JOIN categories c ON m.category_id = c.category_id
JOIN nutrition n ON m.meal_id = n.meal_id
WHERE 1=1";

$params = [];
if (!empty($query)) {
    $sql .= " AND (m.meal_name LIKE :term OR m.description LIKE :term)";
    $params[':term'] = '%' . $query . '%';
}

if (!empty($category)) {
    $sql .= " AND c.category_id = :cat_id";
    $params[':cat_id'] = (int)$category;
}

$sql .= " ORDER BY m.meal_name ASC LIMIT 50";

$rows = pdo_fetch_all($sql, $params);

echo json_encode([
    'success' => true,
    'count' => is_array($rows) ? count($rows) : 0,
    'meals' => $rows ?: []
]);
?>
