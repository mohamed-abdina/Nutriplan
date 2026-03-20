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

function get_today_meals(&$conn, $user_id) {
    $today = date('Y-m-d');
    $sql = "SELECT m.meal_id, m.meal_name, m.meal_icon, m.category_id, c.category_name, 
            n.calories, n.proteins_g, n.carbs_g, n.fats_g, n.fiber_g
            FROM meals m
            JOIN categories c ON m.category_id = c.category_id
            JOIN nutrition n ON m.meal_id = n.meal_id
            WHERE DATE(m.created_at) = '$today' 
            ORDER BY c.category_id ASC";
    
    $result = $conn->query($sql);
    $meals = [];
    
    while ($row = $result->fetch_assoc()) {
        $meals[] = $row;
    }
    
    return $meals;
}

function get_user_shopping_list(&$conn, $user_id) {
    $sql = "SELECT sl.list_id, COUNT(si.item_id) as total_items, 
            SUM(CASE WHEN si.purchased = 0 THEN 1 ELSE 0 END) as unpurchased
            FROM shopping_lists sl
            LEFT JOIN shopping_items si ON sl.list_id = si.list_id
            WHERE sl.user_id = $user_id
            ORDER BY sl.created_at DESC
            LIMIT 1";
    
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}

function get_shopping_items_grouped(&$conn, $list_id) {
    $sql = "SELECT c.category_name, c.category_id, si.item_id, si.item_name, 
            si.quantity, si.purchased, si.custom_item, m.meal_name
            FROM shopping_items si
            LEFT JOIN meals m ON si.meal_id = m.meal_id
            LEFT JOIN categories c ON m.category_id = c.category_id OR si.custom_item = TRUE
            WHERE si.list_id = $list_id
            ORDER BY c.category_id ASC, si.purchased ASC";
    
    $result = $conn->query($sql);
    $items = [];
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}

function upload_avatar(&$conn, $user_id, $file) {
    $target_dir = "../uploads/avatars/";
    $file_ext = pathinfo($file["name"], PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array(strtolower($file_ext), $allowed)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    if ($file["size"] > 2000000) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $filename = "avatar_" . $user_id . "_" . time() . "." . $file_ext;
    $target_file = $target_dir . $filename;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        $url = "/uploads/avatars/" . $filename;
        $conn->query("UPDATE users SET avatar_url = '$url' WHERE user_id = $user_id");
        return ['success' => true, 'url' => $url];
    }
    
    return ['success' => false, 'message' => 'Upload failed'];
}

function search_meals(&$conn, $query, $category = '', $nutrients = []) {
    $search_term = $conn->real_escape_string($query);
    $sql = "SELECT m.meal_id, m.meal_name, m.meal_icon, c.category_name, c.category_id,
            n.calories, n.proteins_g, n.carbs_g, n.fats_g, n.fiber_g
            FROM meals m
            JOIN categories c ON m.category_id = c.category_id
            JOIN nutrition n ON m.meal_id = n.meal_id
            WHERE m.meal_name LIKE '%$search_term%'
            OR m.description LIKE '%$search_term%'";
    
    if (!empty($category)) {
        $cat_id = (int)$category;
        $sql .= " AND c.category_id = $cat_id";
    }
    
    $sql .= " ORDER BY m.meal_name ASC";
    
    $result = $conn->query($sql);
    $meals = [];
    
    while ($row = $result->fetch_assoc()) {
        $meals[] = $row;
    }
    
    return $meals;
}

function get_week_stats(&$conn, $user_id) {
    $week_ago = date('Y-m-d', strtotime('-7 days'));
    $today = date('Y-m-d');
    
    $sql = "SELECT COUNT(DISTINCT DATE(created_at)) as days_planned,
            COUNT(*) as total_meals,
            SUM(calories) as total_calories
            FROM (
                SELECT m.created_at, n.calories
                FROM meals m
                JOIN nutrition n ON m.meal_id = n.meal_id
                WHERE DATE(m.created_at) BETWEEN '$week_ago' AND '$today'
            ) as data";
    
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}
?>
