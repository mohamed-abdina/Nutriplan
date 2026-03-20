<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get user info
$user_result = $conn->query("SELECT first_name FROM users WHERE user_id = $user_id");
$user = $user_result->fetch_assoc();
$greeting = get_greeting() . ', ' . $user['first_name'] . ' 👋';

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

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $meals[] = $row;
    $total_calories += $row['calories'];
    $total_protein += $row['proteins_g'];
}

// Get shopping list stats
$cart_result = $conn->query("SELECT COUNT(DISTINCT si.item_id) as total, 
        SUM(CASE WHEN si.purchased = 0 THEN 1 ELSE 0 END) as unpurchased
        FROM shopping_lists sl
        LEFT JOIN shopping_items si ON sl.list_id = si.list_id
        WHERE sl.user_id = $user_id");
$cart_stats = $cart_result->fetch_assoc() ?? ['total' => 0, 'unpurchased' => 0];

// Nutrition score
$nutrition_score = $total_protein > 30 && $total_calories > 500 ? 85 : 60;
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
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
            <!-- Topbar -->
            <div class="topbar">
                <div>
                    <h3 style="margin-bottom: var(--sp-2);"><?php echo $greeting; ?></h3>
                </div>
                <div style="display: flex; gap: var(--sp-4); align-items: center;">
                    <a href="profile.php" style="text-decoration: none; color: var(--text-1);">👤</a>
                </div>
            </div>
            
            <!-- Stat Cards -->
            <div class="grid-4">
                <div class="stat-card" style="--stat-color: var(--primary);">
                    <div class="stat-label">Today's Meals</div>
                    <div class="stat-value" data-count="<?php echo count($meals); ?>">0</div>
                </div>
                <div class="stat-card" style="--stat-color: var(--success);">
                    <div class="stat-label">Nutrition Score</div>
                    <div class="stat-value" data-count="<?php echo $nutrition_score; ?>">0</div>%
                </div>
                <div class="stat-card" style="--stat-color: var(--warning);">
                    <div class="stat-label">Shopping Items</div>
                    <div class="stat-value" data-count="<?php echo $cart_stats['unpurchased']; ?>">0</div>
                </div>
                <div class="stat-card" style="--stat-color: var(--accent);">
                    <div class="stat-label">Calories</div>
                    <div class="stat-value" data-count="<?php echo (int)($total_calories / 100) * 100; ?>">0</div>
                </div>
            </div>
            
            <!-- Meals Grid -->
            <div style="margin-top: var(--sp-12);">
                <h2 style="margin-bottom: var(--sp-6);">Recommended Meals</h2>
                <div class="grid-2 stagger-container">
                    <?php foreach ($meals as $meal): ?>
                    <article class="meal-card stagger-item" style="--card-accent: var(--primary);">
                        <div class="card-accent-strip"></div>
                        <div class="card-body">
                            <div class="card-icon"><?php echo $meal['meal_icon']; ?></div>
                            <div style="flex: 1;">
                                <div class="card-title"><?php echo $meal['meal_name']; ?></div>
                                <span class="card-category"><?php echo $meal['category_name']; ?></span>
                                <p class="card-nutrients">Cal: <?php echo $meal['calories']; ?> · Protein: <?php echo $meal['proteins_g']; ?>g</p>
                            </div>
                        </div>
                        <div class="card-actions">
                            <button class="btn-ghost btn-sm" onclick="addToShoppingList(<?php echo $meal['meal_id']; ?>)">+ Add</button>
                            <a href="meal.php?id=<?php echo $meal['meal_id']; ?>" class="btn-outline btn-sm">Details →</a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Call to Action -->
            <div style="margin-top: var(--sp-12); background: var(--overlay); border: 1px solid var(--border); border-radius: 14px; padding: var(--sp-8); text-align: center;">
                <h3 style="margin-bottom: var(--sp-4);">Want to plan more meals?</h3>
                <a href="search.php" class="btn btn-primary">Search Meals →</a>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js" defer></script>
    <script>
        animateCounters();
    </script>
</body>
</html>
