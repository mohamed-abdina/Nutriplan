<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$username = $_SESSION['username'] ?? '';

// Get user info (use PDO helpers)
$user = pdo_fetch_one("SELECT first_name FROM users WHERE user_id = ?", [$user_id]);
$greeting = get_greeting() . ', ' . ($user['first_name'] ?? 'User') . ' 👋';

// Get today's meals
$today = date('Y-m-d');
$meals = [];
$total_calories = 0;
$total_protein = 0;

$sql = "SELECT m.meal_id, m.meal_name, m.meal_icon, c.category_name, n.calories, n.proteins_g, n.carbs_g, n.fiber_g
        FROM meals m
        JOIN categories c ON m.category_id = c.category_id
        JOIN nutrition n ON m.meal_id = n.meal_id
        LIMIT 6";

$fetched = pdo_fetch_all($sql);
if ($fetched !== false && is_array($fetched)) {
    $meals = $fetched;
    foreach ($meals as $row) {
        $total_calories += (int)$row['calories'];
        $total_protein += (int)$row['proteins_g'];
    }
}

// Get shopping list stats
$cart_stats = pdo_fetch_one(
    "SELECT COUNT(DISTINCT si.item_id) as total, 
            SUM(CASE WHEN si.purchased = 0 THEN 1 ELSE 0 END) as unpurchased
        FROM shopping_lists sl
        LEFT JOIN shopping_items si ON sl.list_id = si.list_id
        WHERE sl.user_id = ?",
    [$user_id]
);
$cart_stats = $cart_stats ?? ['total' => 0, 'unpurchased' => 0];

// Nutrition score
$nutrition_score = $total_protein > 30 && $total_calories > 500 ? 85 : 60;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NutriPlan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
</head>
<body>
    <div class="app-shell">
        <?php include 'components/sidebar.php'; ?>
        
        <main class="main page-enter">
            <div class="container">
                <!-- Topbar -->
                <div class="topbar">
                <div>
                    <h3 class="mb-2"><?php echo $greeting; ?></h3>
                </div>
                <div class="flex gap-4 flex-center">
                    <a href="profile.php" class="no-underline text-1">👤</a>
                </div>
            </div>
            
                <!-- Stat Cards -->
                <div class="grid-auto-md">
                <?php 
                $stats = [
                    ['label' => "Today's Meals", 'value' => count($meals), 'color' => 'var(--primary)'],
                    ['label' => 'Nutrition Score', 'value' => $nutrition_score, 'color' => 'var(--success)'],
                    ['label' => 'Shopping Items', 'value' => $cart_stats['unpurchased'], 'color' => 'var(--warning)'],
                    ['label' => 'Calories', 'value' => (int)($total_calories / 100) * 100, 'color' => 'var(--accent)'],
                ];
                foreach ($stats as $stat) {
                    $label = $stat['label'];
                    $value = $stat['value'];
                    $color = $stat['color'];
                    include 'components/stat_card.php';
                }
                ?>
                </div>
                
                <!-- Meals Grid -->
                <div class="mt-12">
                    <h2 class="mb-6">Recommended Meals</h2>
                    <div class="grid-2 stagger-container">
                    <?php foreach ($meals as $meal): ?>
                        <?php include 'components/meal_card.php'; ?>
                    <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Call to Action -->
                <div class="mt-12 bg-overlay border radius-14 p-8 text-center">
                    <h3 class="mb-4">Want to plan more meals?</h3>
                    <a href="search.php" class="btn btn-primary">Search Meals →</a>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js" defer></script>
    <script>
        animateCounters();
    </script>
</body>
</html>
