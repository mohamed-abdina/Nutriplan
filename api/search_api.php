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

$query = isset($_GET['q']) ? normalize_search_query($_GET['q']) : '';
$category = isset($_GET['cat']) ? sanitize_input($_GET['cat']) : '';
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'name';
$min_cal = isset($_GET['min_cal']) ? max(0, (int)$_GET['min_cal']) : 0;
$max_cal = isset($_GET['max_cal']) ? max(0, (int)$_GET['max_cal']) : 10000;
$min_protein = isset($_GET['min_protein']) ? max(0, (int)$_GET['min_protein']) : 0;
$max_protein = isset($_GET['max_protein']) ? max(0, (int)$_GET['max_protein']) : 1000;

// Enforce input length limits to prevent DoS attacks
$max_query_length = 200;
$max_sort_length = 50;
$max_offset = 1000;

if (strlen($query) > $max_query_length) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Search query too long (max 200 characters)']);
    exit;
}

if (strlen($sort) > $max_sort_length) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid sort parameter']);
    exit;
}

if ($offset > $max_offset) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Offset too large']);
    exit;
}

// Validate and constrain numeric ranges
$min_cal = min($min_cal, 5000);
$max_cal = max($min_cal, min($max_cal, 5000));
$min_protein = min($min_protein, 1000);
$max_protein = max($min_protein, min($max_protein, 1000));

// Log API call
$error_logger->log_api_call('search_api', 'GET', ['query' => $query, 'category' => $category, 'offset' => $offset], 200);

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$apply_preferences = !isset($_GET['apply_preferences']) || (int)$_GET['apply_preferences'] !== 0;

$portion_multiplier = 1.0;
$dietary_restrictions = [];
$allergies = [];

if ($user_id > 0 && $apply_preferences) {
    $prefs = pdo_fetch_one(
        "SELECT portion_size, dietary_restrictions, allergies
         FROM user_preferences
         WHERE user_id = :user_id",
        [':user_id' => $user_id]
    );

    if (is_array($prefs)) {
        $portion_size = strtolower(trim((string)($prefs['portion_size'] ?? 'normal')));
        $portion_map = [
            'small' => 0.8,
            'normal' => 1.0,
            'large' => 1.25,
            'extra-large' => 1.5,
        ];
        $portion_multiplier = $portion_map[$portion_size] ?? 1.0;

        $dietary_restrictions = array_values(array_filter(array_map(
            static function ($item) {
                return strtolower(trim($item));
            },
            explode(',', (string)($prefs['dietary_restrictions'] ?? ''))
        )));

        $allergies = array_values(array_filter(array_map(
            static function ($item) {
                return strtolower(trim($item));
            },
            explode(',', (string)($prefs['allergies'] ?? ''))
        )));
    }
}

$sql = "SELECT m.meal_id, m.meal_name, m.meal_icon, c.category_name, c.category_id,
    ROUND(n.calories * :portion_multiplier_sel_cal) AS calories,
    ROUND(n.proteins_g * :portion_multiplier_sel_protein, 1) AS proteins_g,
    ROUND(n.carbs_g * :portion_multiplier_sel_carbs, 1) AS carbs_g,
    ROUND(n.fats_g * :portion_multiplier_sel_fats, 1) AS fats_g,
    ROUND(n.fiber_g * :portion_multiplier_sel_fiber, 1) AS fiber_g
FROM meals m
JOIN categories c ON m.category_id = c.category_id
JOIN nutrition n ON m.meal_id = n.meal_id
WHERE 1=1";

$params = [
    ':portion_multiplier_sel_cal' => $portion_multiplier,
    ':portion_multiplier_sel_protein' => $portion_multiplier,
    ':portion_multiplier_sel_carbs' => $portion_multiplier,
    ':portion_multiplier_sel_fats' => $portion_multiplier,
    ':portion_multiplier_sel_fiber' => $portion_multiplier,
    ':portion_multiplier_filter_cal' => $portion_multiplier,
    ':portion_multiplier_filter_protein' => $portion_multiplier,
    ':portion_multiplier_filter_keto' => $portion_multiplier,
];
if (!empty($query)) {
    $sql .= " AND (m.meal_name LIKE :term OR m.description LIKE :term)";
    $params[':term'] = '%' . $query . '%';
}

if (!empty($category)) {
    $sql .= " AND c.category_id = :cat_id";
    $params[':cat_id'] = (int)$category;
}

// Nutrition filters
$sql .= " AND (n.calories * :portion_multiplier_filter_cal) BETWEEN :min_cal AND :max_cal";
$params[':min_cal'] = $min_cal;
$params[':max_cal'] = $max_cal;

$sql .= " AND (n.proteins_g * :portion_multiplier_filter_protein) BETWEEN :min_protein AND :max_protein";
$params[':min_protein'] = $min_protein;
$params[':max_protein'] = $max_protein;

if (!empty($dietary_restrictions)) {
    $restriction_map = [
        'vegetarian' => ['chicken', 'beef', 'pork', 'fish', 'turkey', 'lamb', 'bacon', 'ham', 'seafood', 'shrimp'],
        'vegan' => ['chicken', 'beef', 'pork', 'fish', 'turkey', 'lamb', 'bacon', 'ham', 'seafood', 'shrimp', 'egg', 'milk', 'cheese', 'butter', 'yogurt', 'cream', 'honey'],
        'gluten-free' => ['wheat', 'bread', 'pasta', 'flour', 'barley', 'rye', 'noodle'],
        'dairy-free' => ['milk', 'cheese', 'butter', 'cream', 'yogurt'],
        'halal' => ['pork', 'bacon', 'ham', 'wine', 'alcohol'],
    ];

    foreach ($dietary_restrictions as $restriction_idx => $restriction) {
        if (!isset($restriction_map[$restriction])) {
            continue;
        }

        foreach ($restriction_map[$restriction] as $term_idx => $term) {
            $term_key = ':restriction_' . $restriction_idx . '_' . $term_idx;
            $desc_key = ':restriction_desc_' . $restriction_idx . '_' . $term_idx;
            $sql .= " AND m.meal_id NOT IN (
                        SELECT i.meal_id FROM ingredients i WHERE LOWER(i.ingredient_name) LIKE {$term_key}
                    )
                    AND LOWER(CONCAT(m.meal_name, ' ', COALESCE(m.description, ''))) NOT LIKE {$desc_key}";
            $params[$term_key] = '%' . $term . '%';
            $params[$desc_key] = '%' . $term . '%';
        }

        if ($restriction === 'keto') {
            $sql .= " AND (n.carbs_g * :portion_multiplier_filter_keto) <= 20";
        }
    }
}

if (!empty($allergies)) {
    foreach ($allergies as $allergy_idx => $allergy) {
        $allergy_ing_key = ':allergy_ing_' . $allergy_idx;
        $allergy_desc_key = ':allergy_desc_' . $allergy_idx;
        $sql .= " AND m.meal_id NOT IN (
                    SELECT i.meal_id FROM ingredients i WHERE LOWER(i.ingredient_name) LIKE {$allergy_ing_key}
                )
                AND LOWER(CONCAT(m.meal_name, ' ', COALESCE(m.description, ''))) NOT LIKE {$allergy_desc_key}";
        $params[$allergy_ing_key] = '%' . $allergy . '%';
        $params[$allergy_desc_key] = '%' . $allergy . '%';
    }
}

// Sorting
switch ($sort) {
    case 'calories_low':
        $sql .= " ORDER BY n.calories ASC";
        break;
    case 'calories_high':
        $sql .= " ORDER BY n.calories DESC";
        break;
    case 'protein_high':
        $sql .= " ORDER BY n.proteins_g DESC";
        break;
    case 'protein_low':
        $sql .= " ORDER BY n.proteins_g ASC";
        break;
    case 'relevance':
        if (!empty($query)) {
            $sql .= " ORDER BY CASE WHEN m.meal_name LIKE :exact THEN 0 ELSE 1 END, m.meal_name ASC";
            $params[':exact'] = $query . '%';
        } else {
            $sql .= " ORDER BY m.meal_name ASC";
        }
        break;
    default: // 'name'
        $sql .= " ORDER BY m.meal_name ASC";
}

// Pagination
$limit = 12;
$sql .= " LIMIT :limit OFFSET :offset";
$params[':limit'] = $limit;
$params[':offset'] = $offset;

$rows = pdo_fetch_all($sql, $params);

// Get total count for pagination info
$count_sql = "SELECT COUNT(*) as total FROM meals m
    JOIN categories c ON m.category_id = c.category_id
    JOIN nutrition n ON m.meal_id = n.meal_id
    WHERE 1=1";

$count_params = [
    ':portion_multiplier_filter_cal' => $portion_multiplier,
    ':portion_multiplier_filter_protein' => $portion_multiplier,
    ':portion_multiplier_filter_keto' => $portion_multiplier,
];
if (!empty($query)) {
    $count_sql .= " AND (m.meal_name LIKE :term OR m.description LIKE :term)";
    $count_params[':term'] = '%' . $query . '%';
}
if (!empty($category)) {
    $count_sql .= " AND c.category_id = :cat_id";
    $count_params[':cat_id'] = (int)$category;
}
$count_sql .= " AND (n.calories * :portion_multiplier_filter_cal) BETWEEN :min_cal AND :max_cal";
$count_params[':min_cal'] = $min_cal;
$count_params[':max_cal'] = $max_cal;
$count_sql .= " AND (n.proteins_g * :portion_multiplier_filter_protein) BETWEEN :min_protein AND :max_protein";
$count_params[':min_protein'] = $min_protein;
$count_params[':max_protein'] = $max_protein;

if (!empty($dietary_restrictions)) {
    $restriction_map = [
        'vegetarian' => ['chicken', 'beef', 'pork', 'fish', 'turkey', 'lamb', 'bacon', 'ham', 'seafood', 'shrimp'],
        'vegan' => ['chicken', 'beef', 'pork', 'fish', 'turkey', 'lamb', 'bacon', 'ham', 'seafood', 'shrimp', 'egg', 'milk', 'cheese', 'butter', 'yogurt', 'cream', 'honey'],
        'gluten-free' => ['wheat', 'bread', 'pasta', 'flour', 'barley', 'rye', 'noodle'],
        'dairy-free' => ['milk', 'cheese', 'butter', 'cream', 'yogurt'],
        'halal' => ['pork', 'bacon', 'ham', 'wine', 'alcohol'],
    ];

    foreach ($dietary_restrictions as $restriction_idx => $restriction) {
        if (!isset($restriction_map[$restriction])) {
            continue;
        }

        foreach ($restriction_map[$restriction] as $term_idx => $term) {
            $term_key = ':restriction_' . $restriction_idx . '_' . $term_idx;
            $desc_key = ':restriction_desc_' . $restriction_idx . '_' . $term_idx;
            $count_sql .= " AND m.meal_id NOT IN (
                            SELECT i.meal_id FROM ingredients i WHERE LOWER(i.ingredient_name) LIKE {$term_key}
                        )
                        AND LOWER(CONCAT(m.meal_name, ' ', COALESCE(m.description, ''))) NOT LIKE {$desc_key}";
            $count_params[$term_key] = '%' . $term . '%';
            $count_params[$desc_key] = '%' . $term . '%';
        }

        if ($restriction === 'keto') {
            $count_sql .= " AND (n.carbs_g * :portion_multiplier_filter_keto) <= 20";
        }
    }
}

if (!empty($allergies)) {
    foreach ($allergies as $allergy_idx => $allergy) {
        $allergy_ing_key = ':allergy_ing_' . $allergy_idx;
        $allergy_desc_key = ':allergy_desc_' . $allergy_idx;
        $count_sql .= " AND m.meal_id NOT IN (
                        SELECT i.meal_id FROM ingredients i WHERE LOWER(i.ingredient_name) LIKE {$allergy_ing_key}
                    )
                    AND LOWER(CONCAT(m.meal_name, ' ', COALESCE(m.description, ''))) NOT LIKE {$allergy_desc_key}";
        $count_params[$allergy_ing_key] = '%' . $allergy . '%';
        $count_params[$allergy_desc_key] = '%' . $allergy . '%';
    }
}

$count_result = pdo_fetch_one($count_sql, $count_params) ?? ['total' => 0];
$total_meals = (int)$count_result['total'];
$has_more = ($offset + $limit) < $total_meals;

echo json_encode([
    'success' => true,
    'count' => is_array($rows) ? count($rows) : 0,
    'total' => $total_meals,
    'offset' => $offset,
    'limit' => $limit,
    'has_more' => $has_more,
    'meals' => $rows ?: []
]);
?>
