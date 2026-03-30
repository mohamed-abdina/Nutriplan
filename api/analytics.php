<?php
// Analytics and Insights API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';
secure_session_start();

require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
require_once '../includes/rate_limit.php';
require_once '../includes/error_logger.php';

// Apply rate limiting (10 requests per 60 seconds)
if (!$limiter->check_rate_limit('analytics', 10, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : '';
$period = isset($_GET['period']) ? sanitize_input($_GET['period']) : 'week'; // week, month

$error_logger->log_api_call('analytics', 'GET', ['action' => $action, 'period' => $period], 200);

if ($action === 'weekly_stats') {
    // Get stats for the last 7 days
    $stats = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $day = date('l', strtotime($date)); // Get day name
        
        $data = pdo_fetch_one(
            "SELECT COALESCE(SUM(n.calories), 0) as calories, 
                    COALESCE(SUM(n.proteins_g), 0) as protein,
                    COUNT(DISTINCT m.meal_id) as meal_count
            FROM shopping_lists sl
            LEFT JOIN shopping_items si ON sl.list_id = si.list_id
            LEFT JOIN meals m ON si.meal_id = m.meal_id
            LEFT JOIN nutrition n ON m.meal_id = n.meal_id
            WHERE sl.user_id = :user_id AND DATE(si.created_at) = :date",
            [':user_id' => $user_id, ':date' => $date]
        ) ?? ['calories' => 0, 'protein' => 0, 'meal_count' => 0];
        
        $stats[] = [
            'date' => $date,
            'day' => substr($day, 0, 3),
            'calories' => (int)$data['calories'],
            'protein' => (int)$data['protein'],
            'meal_count' => (int)$data['meal_count']
        ];
    }
    
    echo json_encode(['success' => true, 'stats' => $stats]);
}

elseif ($action === 'meal_frequency') {
    // Most frequently added meals
    $meals = pdo_fetch_all(
        "SELECT m.meal_id, m.meal_name, m.meal_icon, COUNT(si.item_id) as times_added,
                COALESCE(AVG(CAST(mr.rating AS UNSIGNED)), 0) as avg_rating
        FROM shopping_items si
        JOIN meals m ON si.meal_id = m.meal_id
        JOIN shopping_lists sl ON si.list_id = sl.list_id
        LEFT JOIN meal_ratings mr ON m.meal_id = mr.meal_id AND mr.user_id = :user_id
        WHERE sl.user_id = :user_id AND si.meal_id IS NOT NULL
        GROUP BY m.meal_id
        ORDER BY times_added DESC
        LIMIT 10",
        [':user_id' => $user_id]
    ) ?? [];
    
    echo json_encode(['success' => true, 'meals' => $meals]);
}

elseif ($action === 'nutrition_trends') {
    // Daily nutrition averages over 30 days
    $trend_data = pdo_fetch_one(
        "SELECT 
            DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 30 DAY), '%Y-%m-%d') as period,
            COALESCE(AVG(n.calories), 0) as avg_calories,
            COALESCE(AVG(n.proteins_g), 0) as avg_protein,
            COALESCE(AVG(n.carbs_g), 0) as avg_carbs,
            COALESCE(AVG(n.fats_g), 0) as avg_fats,
            COUNT(DISTINCT DATE(si.created_at)) as days_tracked
        FROM shopping_lists sl
        LEFT JOIN shopping_items si ON sl.list_id = si.list_id
        LEFT JOIN meals m ON si.meal_id = m.meal_id
        LEFT JOIN nutrition n ON m.meal_id = n.meal_id
        WHERE sl.user_id = :user_id AND si.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
        [':user_id' => $user_id]
    ) ?? ['avg_calories' => 0, 'avg_protein' => 0, 'avg_carbs' => 0, 'avg_fats' => 0, 'days_tracked' => 0];
    
    echo json_encode(['success' => true, 'trends' => $trend_data]);
}

elseif ($action === 'export_data') {
    // Get all meal history for export
    $meals = pdo_fetch_all(
        "SELECT m.meal_name, c.category_name, n.calories, n.proteins_g, n.carbs_g, n.fats_g,
                si.created_at, si.quantity
        FROM shopping_items si
        JOIN meals m ON si.meal_id = m.meal_id
        JOIN categories c ON m.category_id = c.category_id
        JOIN nutrition n ON m.meal_id = n.meal_id
        JOIN shopping_lists sl ON si.list_id = sl.list_id
        WHERE sl.user_id = :user_id
        ORDER BY si.created_at DESC
        LIMIT 500",
        [':user_id' => $user_id]
    ) ?? [];
    
    // Return CSV data
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="nutriplan_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Meal', 'Category', 'Calories', 'Protein (g)', 'Carbs (g)', 'Fats (g)', 'Date Added', 'Quantity']);
    
    foreach ($meals as $meal) {
        fputcsv($output, [
            $meal['meal_name'],
            $meal['category_name'],
            $meal['calories'],
            $meal['proteins_g'],
            $meal['carbs_g'],
            $meal['fats_g'],
            $meal['created_at'],
            $meal['quantity']
        ]);
    }
    fclose($output);
    exit;
}

else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
