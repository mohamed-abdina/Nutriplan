<?php
// Helper functions for NutriPlan

function get_nutrition_score($meals_array) {
    if (empty($meals_array)) return 0;
    
    $total_protein = 0;
    $total_carbs = 0;
    $total_fiber = 0;
    
    foreach ($meals_array as $meal) {
        $total_protein += $meal['proteins_g'] ?? 0;
        $total_carbs += $meal['carbs_g'] ?? 0;
        $total_fiber += $meal['fiber_g'] ?? 0;
    }
    
    // Score based on balance
    $score = 0;
    if ($total_protein > 30) $score += 25;
    if ($total_carbs > 100) $score += 25;
    if ($total_fiber > 15) $score += 25;
    $score += 25; // Bonus for having meals
    
    return min($score, 100);
}

function get_greeting() {
    $hour = (int)date('H');
    if ($hour < 12) return 'Good morning';
    if ($hour < 18) return 'Good afternoon';
    return 'Good evening';
}

/**
 * Determine the database host to use.
 * Priority:
 *  1. Explicit env var: MYSQL_HOST or DB_HOST
 *  2. CLI or local HTTP_HOST containing localhost -> 127.0.0.1
 *  3. Default for containerized setup -> 'db'
 */
function get_db_host() {
    $envHost = $_ENV['MYSQL_HOST'] ?? ($_ENV['DB_HOST'] ?? null);
    if (!empty($envHost)) return $envHost;

    if (php_sapi_name() === 'cli' || PHP_SAPI === 'cli') {
        return '127.0.0.1';
    }

    if (!empty($_SERVER['HTTP_HOST'])) {
        $hostHeader = $_SERVER['HTTP_HOST'];
        if (strpos($hostHeader, 'localhost') !== false || strpos($hostHeader, '127.0.0.1') !== false) {
            return '127.0.0.1';
        }
    }

    return 'db';
}

function get_today_meals(&$conn, $user_id) {
    $today = date('Y-m-d');
    $sql = "SELECT m.meal_id, m.meal_name, m.meal_icon, m.category_id, c.category_name, 
            n.calories, n.proteins_g, n.carbs_g, n.fats_g, n.fiber_g
            FROM meals m
            JOIN categories c ON m.category_id = c.category_id
            JOIN nutrition n ON m.meal_id = n.meal_id
            WHERE DATE(m.created_at) = :today
            ORDER BY c.category_id ASC";

    $rows = pdo_fetch_all($sql, [':today' => $today]);
    return $rows === false ? [] : $rows;
}

function get_user_shopping_list(&$conn, $user_id) {
    $sql = "SELECT sl.list_id, COUNT(si.item_id) as total_items, 
            SUM(CASE WHEN si.purchased = 0 THEN 1 ELSE 0 END) as unpurchased
            FROM shopping_lists sl
            LEFT JOIN shopping_items si ON sl.list_id = si.list_id
            WHERE sl.user_id = :user_id
            ORDER BY sl.created_at DESC
            LIMIT 1";

    $row = pdo_fetch_one($sql, [':user_id' => (int)$user_id]);
    return $row === false ? [] : $row;
}

function get_shopping_items_grouped(&$conn, $list_id) {
    $sql = "SELECT c.category_name, c.category_id, si.item_id, si.item_name, 
            si.quantity, si.purchased, si.custom_item, m.meal_name
            FROM shopping_items si
            LEFT JOIN meals m ON si.meal_id = m.meal_id
            LEFT JOIN categories c ON m.category_id = c.category_id OR si.custom_item = TRUE
            WHERE si.list_id = :list_id
            ORDER BY c.category_id ASC, si.purchased ASC";

    $rows = pdo_fetch_all($sql, [':list_id' => (int)$list_id]);
    return $rows === false ? [] : $rows;
}

function upload_avatar(&$conn, $user_id, $file) {
    $target_dir = __DIR__ . "/../uploads/avatars/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
    $file_ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed_exts = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];

    if (!array_key_exists($file_ext, $allowed_exts)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    if ($file["size"] > 2000000) {
        return ['success' => false, 'message' => 'File too large'];
    }

    // Validate MIME type using finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if ($mime !== $allowed_exts[$file_ext]) {
        return ['success' => false, 'message' => 'MIME type mismatch'];
    }

    $filename = "avatar_" . (int)$user_id . "_" . time() . "." . $file_ext;
    $target_file = $target_dir . $filename;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        // Ensure safe permissions
        @chmod($target_file, 0644);
        $url = "/uploads/avatars/" . $filename;
        $sql = "UPDATE users SET avatar_url = :url WHERE user_id = :user_id";
        pdo_query($sql, [':url' => $url, ':user_id' => (int)$user_id]);
        return ['success' => true, 'url' => $url];
    }

    return ['success' => false, 'message' => 'Upload failed'];
}

function search_meals(&$conn, $query, $category = '', $nutrients = []) {
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

    $sql .= " ORDER BY m.meal_name ASC";

    $rows = pdo_fetch_all($sql, $params);
    return $rows === false ? [] : $rows;
}

function get_week_stats(&$conn, $user_id) {
    $week_ago = date('Y-m-d', strtotime('-7 days'));
    $today = date('Y-m-d');
    $sql = "SELECT COUNT(DISTINCT DATE(m.created_at)) as days_planned,
            COUNT(*) as total_meals,
            SUM(n.calories) as total_calories
            FROM meals m
            JOIN nutrition n ON m.meal_id = n.meal_id
            WHERE DATE(m.created_at) BETWEEN :week_ago AND :today";

    $row = pdo_fetch_one($sql, [':week_ago' => $week_ago, ':today' => $today]);
    return $row === false ? ['days_planned' => 0, 'total_meals' => 0, 'total_calories' => 0] : $row;
}
?>
