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
    // Rely exclusively on environment configuration
    // This is more predictable and explicit than trying to detect environment from HTTP_HOST or file presence
    return $_ENV['MYSQL_HOST'] ?? ($_ENV['DB_HOST'] ?? 'db');
}

function get_today_meals(&$conn, $user_id) {
    $today = date('Y-m-d');
    $sql = "SELECT m.meal_id, m.meal_name, m.meal_icon, m.category_id, c.category_name, 
            n.calories, n.proteins_g, n.carbs_g, n.fats_g, n.fiber_g, mp.meal_type
            FROM meal_planning mp
            JOIN meals m ON mp.meal_id = m.meal_id
            JOIN categories c ON m.category_id = c.category_id
            JOIN nutrition n ON m.meal_id = n.meal_id
            WHERE mp.user_id = :user_id AND mp.planned_date = :today
            ORDER BY FIELD(mp.meal_type, 'breakfast', 'lunch', 'snack', 'dinner') ASC";

    $rows = pdo_fetch_all($sql, [':user_id' => (int)$user_id, ':today' => $today]);
    return $rows === false ? [] : $rows;
}

function get_user_shopping_list(&$conn, $user_id) {
    $sql = "SELECT c.list_id, COUNT(ci.item_id) as total_items, 
            SUM(CASE WHEN ci.purchased = 0 THEN 1 ELSE 0 END) as unpurchased
            FROM carts c
            LEFT JOIN cart_items ci ON c.list_id = ci.list_id
            WHERE c.user_id = :user_id
            ORDER BY c.created_at DESC
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
    $sql = "SELECT COUNT(DISTINCT mp.planned_date) as days_planned,
            COUNT(*) as total_meals,
            COALESCE(SUM(n.calories), 0) as total_calories
            FROM meal_planning mp
            JOIN nutrition n ON mp.meal_id = n.meal_id
            WHERE mp.user_id = :user_id
              AND mp.planned_date BETWEEN :week_ago AND :today";

    $row = pdo_fetch_one($sql, [':user_id' => (int)$user_id, ':week_ago' => $week_ago, ':today' => $today]);
    return $row === false ? ['days_planned' => 0, 'total_meals' => 0, 'total_calories' => 0] : $row;
}

if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        $data = trim((string)$data);
        return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/**
 * Normalize a search query for database matching.
 * This keeps the user's literal input intact so characters like & and '
 * still match the stored meal name.
 */
if (!function_exists('normalize_search_query')) {
    function normalize_search_query($data) {
        return trim((string)$data);
    }
}

/**
 * Generate unified meal card HTML structure
 * Used by server-side rendering (meal_card.php) and client-side JavaScript
 * Ensures consistent card styling across all pages (search, dashboard, etc.)
 * 
 * @param array $meal Meal data array with keys: meal_id, meal_name, meal_icon, category_name, calories, proteins_g
 * @param array $options Optional rendering options: animation_delay, card_accent_override
 * @return string HTML markup for meal card
 */
function generate_meal_card_html($meal, $options = []) {
    // Extract meal data
    $meal_id = (int)($meal['meal_id'] ?? 0);
    $meal_name = htmlspecialchars((string)($meal['meal_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $category_name = htmlspecialchars((string)($meal['category_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $calories = (int)($meal['calories'] ?? 0);
    $protein = (int)($meal['proteins_g'] ?? 0);
    $meal_icon_raw = trim((string)($meal['meal_icon'] ?? ''));
    
    // Determine icon display
    $meal_icon_display = $meal_icon_raw;
    if ($meal_icon_raw === '' || preg_match('/^[a-z0-9\-\_\s]+$/i', $meal_icon_raw)) {
        $category_lower = strtolower($category_name);
        if (strpos($category_lower, 'breakfast') !== false) {
            $meal_icon_display = '🍳';
        } elseif (strpos($category_lower, 'lunch') !== false) {
            $meal_icon_display = '🥗';
        } elseif (strpos($category_lower, 'dinner') !== false || strpos($category_lower, 'supper') !== false) {
            $meal_icon_display = '🍽️';
        } elseif (strpos($category_lower, 'snack') !== false) {
            $meal_icon_display = '🥜';
        } else {
            $meal_icon_display = '🍽️';
        }
    }
    
    // Determine nutrition level and color
    $nutrition_level = '';
    $nutrition_color = 'var(--primary)';
    
    if ($protein > 25) {
        $nutrition_level = 'High Protein';
        $nutrition_color = 'var(--accent)';
    } elseif ($calories < 300) {
        $nutrition_level = 'Low Cal';
        $nutrition_color = 'var(--success)';
    } elseif ($calories > 700) {
        $nutrition_level = 'Hearty';
        $nutrition_color = 'var(--warning)';
    }
    
    // Override nutrition color if specified
    if (!empty($options['card_accent_override'])) {
        $nutrition_color = htmlspecialchars($options['card_accent_override'], ENT_QUOTES, 'UTF-8');
    }
    
    // Build animation delay style
    $animation_delay = isset($options['animation_delay']) ? (int)$options['animation_delay'] * 60 : 0;
    $animation_style = $animation_delay > 0 ? "animation-delay: {$animation_delay}ms" : '';
    
    // Build the HTML
    $html = <<<HTML
<article class="meal-card stagger-item" style="--card-accent: {$nutrition_color}; {$animation_style}">
    <div class="card-accent-strip"></div>
    <div class="card-body">
        <div style="display: flex; gap: var(--sp-3); width: 100%;">
            <div class="card-icon" aria-hidden="true">{$meal_icon_display}</div>
            <div style="flex: 1; min-width: 0;">
                <div class="card-title">{$meal_name}</div>
                <span class="card-category">{$category_name}</span>
                <div class="card-badges">
                    <div class="nutrition-badge badge-primary" title="{$calories} calories">
                        🔥 {$calories} cal
                    </div>
                    <div class="nutrition-badge badge-accent" title="{$protein}g protein">
                        💪 {$protein}g
                    </div>
HTML;
    
    if ($nutrition_level) {
        $nutrition_level_escaped = htmlspecialchars($nutrition_level, ENT_QUOTES, 'UTF-8');
        $html .= <<<HTML
                    <div class="nutrition-badge badge-level" style="--badge-color: {$nutrition_color};" title="{$nutrition_level_escaped}">
                        ✨ {$nutrition_level_escaped}
                    </div>
HTML;
    }
    
    $html .= <<<HTML
                </div>
            </div>
        </div>
    </div>
    <div class="card-actions">
        <button class="btn btn-ghost btn-sm" onclick="addToShoppingList({$meal_id})" aria-label="Add {$meal_name} to shopping list">+ Add</button>
        <a href="meal.php?meal_id={$meal_id}" class="btn btn-outline btn-sm">Details →</a>
    </div>
</article>
HTML;
    
    return $html;
}
/**
 * Cart Functions (renamed from shopping)
 */

function get_user_cart(&$conn, $user_id) {
    $sql = "SELECT list_id, COUNT(ci.item_id) as total_items, 
            SUM(CASE WHEN ci.purchased = 0 THEN 1 ELSE 0 END) as unpurchased
            FROM carts c
            LEFT JOIN cart_items ci ON c.list_id = ci.list_id
            WHERE c.user_id = :user_id
            ORDER BY c.created_at DESC
            LIMIT 1";

    $row = pdo_fetch_one($sql, [':user_id' => (int)$user_id]);
    return $row === false ? [] : $row;
}

function get_cart_items_grouped(&$conn, $list_id) {
    $sql = "SELECT c.category_name, c.category_id, ci.item_id, ci.item_name, 
            ci.quantity, ci.purchased, ci.custom_item, m.meal_name
            FROM cart_items ci
            LEFT JOIN meals m ON ci.meal_id = m.meal_id
            LEFT JOIN categories c ON m.category_id = c.category_id OR ci.custom_item = TRUE
            WHERE ci.list_id = :list_id
            ORDER BY c.category_id ASC, ci.purchased ASC";

    $rows = pdo_fetch_all($sql, [':list_id' => (int)$list_id]);
    return $rows === false ? [] : $rows;
}

// Backward compatibility: redirect old functions to new cart functions
function get_user_shopping_list_legacy(&$conn, $user_id) {
    return get_user_cart($conn, $user_id);
}

function get_shopping_items_grouped_legacy(&$conn, $list_id) {
    return get_cart_items_grouped($conn, $list_id);
}

/**
 * Wishlist Functions (renamed from favorites)
 */

function get_user_wishlist(&$conn, $user_id, $limit = 6) {
    $sql = "SELECT m.meal_id, m.meal_name, m.meal_icon, c.category_name, c.category_id,
            n.calories, n.proteins_g, r.rating, r.is_wishlisted, r.review
            FROM meal_ratings r
            JOIN meals m ON r.meal_id = m.meal_id
            JOIN categories c ON m.category_id = c.category_id
            JOIN nutrition n ON m.meal_id = n.meal_id
            WHERE r.user_id = :user_id AND r.is_wishlisted = 1
            ORDER BY r.rating DESC, r.updated_at DESC
            LIMIT :limit";

    $rows = pdo_fetch_all($sql, [':user_id' => (int)$user_id, ':limit' => (int)$limit]);
    return $rows === false ? [] : $rows;
}

function get_wishlist_count(&$conn, $user_id) {
    $sql = "SELECT COUNT(*) as count FROM meal_ratings 
            WHERE user_id = :user_id AND is_wishlisted = 1";
    
    $row = pdo_fetch_one($sql, [':user_id' => (int)$user_id]);
    return isset($row['count']) ? (int)$row['count'] : 0;
}

function is_meal_wishlisted(&$conn, $user_id, $meal_id) {
    $sql = "SELECT is_wishlisted FROM meal_ratings 
            WHERE user_id = :user_id AND meal_id = :meal_id";
    
    $row = pdo_fetch_one($sql, [':user_id' => (int)$user_id, ':meal_id' => (int)$meal_id]);
    return $row ? (bool)$row['is_wishlisted'] : false;
}

// Backward compatibility: old function names redirect to new ones
function get_user_favorites_legacy(&$conn, $user_id, $limit = 6) {
    return get_user_wishlist($conn, $user_id, $limit);
}

function get_favorites_count_legacy(&$conn, $user_id) {
    return get_wishlist_count($conn, $user_id);
}