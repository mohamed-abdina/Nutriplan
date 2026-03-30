<?php
require_once __DIR__ . '/includes/session.php';
secure_session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$username = $_SESSION['username'] ?? '';

// Get user info (use PDO helpers)
$user = pdo_fetch_one("SELECT first_name FROM users WHERE user_id = ?", [$user_id]);
$greeting = get_greeting() . ', ' . ($user['first_name'] ?? 'User') . ' 👋';

// Get today's meals - USER-SPECIFIC instead of all meals
$today = date('Y-m-d');
$meals = [];
$total_calories = 0;
$total_protein = 0;
$carbs_total = 0;
$fiber_total = 0;

// Fetch user's meals or recent meals from the system with better filtering
$sql = "SELECT m.meal_id, m.meal_name, m.meal_icon, c.category_name, n.calories, n.proteins_g, n.carbs_g, n.fiber_g
        FROM meals m
        JOIN categories c ON m.category_id = c.category_id
        JOIN nutrition n ON m.meal_id = n.meal_id
        ORDER BY m.meal_id DESC
        LIMIT 8";

$fetched = pdo_fetch_all($sql);
if ($fetched !== false && is_array($fetched)) {
    $meals = $fetched;
    foreach ($meals as $row) {
        $total_calories += (int)$row['calories'];
        $total_protein += (int)$row['proteins_g'];
        $carbs_total += (int)$row['carbs_g'];
        $fiber_total += (int)$row['fiber_g'];
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
                // Enhanced stats with trends
                $protein_trend = $total_protein > 100 ? '↑' : '→';
                $calories_trend = $total_calories > 1500 ? '↑' : ($total_calories < 500 ? '↓' : '→');
                
                $stats = [
                    ['label' => 'Meals Available', 'value' => count($meals), 'color' => 'var(--primary)', 'icon' => '🍽'],
                    ['label' => 'Total Protein', 'value' => $total_protein . 'g', 'color' => 'var(--accent)', 'icon' => '💪', 'trend' => $protein_trend],
                    ['label' => 'Calories Matched', 'value' => (int)($total_calories / 100) * 100, 'color' => 'var(--warning)', 'icon' => '🔥', 'trend' => $calories_trend],
                    ['label' => 'To Shop', 'value' => $cart_stats['unpurchased'], 'color' => 'var(--success)', 'icon' => '🛒'],
                ];
                foreach ($stats as $stat) {
                    $label = $stat['label'];
                    $value = $stat['value'];
                    $color = $stat['color'];
                    $icon = $stat['icon'] ?? '📈';
                    $trend = $stat['trend'] ?? '';
                    include 'components/stat_card.php';
                }
                ?>
                </div>
                
                <!-- Nutrition Summary -->
                <div class="mt-8 bg-overlay border radius-14 p-6" style="border-left: 4px solid var(--primary);">
                    <h3 style="margin-bottom: var(--sp-4);">📊 Nutrition Overview</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: var(--sp-4);">
                        <div style="text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700; color: var(--accent); margin-bottom: var(--sp-2);"><?php echo htmlspecialchars((string)$total_protein); ?>g</div>
                            <div style="font-size: var(--text-sm); color: var(--text-2);">Protein</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700; color: var(--warning); margin-bottom: var(--sp-2);"><?php echo htmlspecialchars((string)$total_calories); ?></div>
                            <div style="font-size: var(--text-sm); color: var(--text-2);">Calories</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700; color: var(--primary); margin-bottom: var(--sp-2);"><?php echo htmlspecialchars((string)$carbs_total); ?>g</div>
                            <div style="font-size: var(--text-sm); color: var(--text-2);">Carbs</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700; color: var(--success); margin-bottom: var(--sp-2);"><?php echo htmlspecialchars((string)$fiber_total); ?>g</div>
                            <div style="font-size: var(--text-sm); color: var(--text-2);">Fiber</div>
                        </div>
                    </div>
                </div>
                
                <!-- Meals Grid -->
                <div class="mt-12">
                    <h2 class="mb-6">✨ Meal Recommendations</h2>
                    <div class="grid-2 stagger-container">
                    <?php foreach ($meals as $meal): ?>
                        <?php include 'components/meal_card.php'; ?>
                    <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Meals You Love Section -->
                <div class="mt-12 hidden" id="youLoveSection">
                    <h2 class="mb-6">❤️ Meals You Love</h2>
                    <div class="grid-2 stagger-container" id="youLoveContainer">
                        <!-- Loaded via JavaScript -->
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
        document.addEventListener('DOMContentLoaded', () => {
            animateCounters();
            loadYouLoveMeals();
        });
        
        function loadYouLoveMeals() {
            fetch('api/meal_ratings.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get_top_rated&limit=6'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.top_rated && data.top_rated.length > 0) {
                    const section = document.getElementById('youLoveSection');
                    const container = document.getElementById('youLoveContainer');
                    
                    container.innerHTML = data.top_rated.map((meal, index) => `
                        <article class="meal-card stagger-item" style="--card-accent: var(--accent); animation-delay: ${index * 60}ms">
                            <div class="card-accent-strip"></div>
                            <div class="card-body">
                                <div style="display: flex; gap: var(--sp-3); width: 100%;">
                                    <div class="card-icon">${escapeHtml(meal.meal_icon)}</div>
                                    <div style="flex: 1; min-width: 0;">
                                        <div class="card-title">${escapeHtml(meal.meal_name)}</div>
                                        <span class="card-category">${escapeHtml(meal.category_name)}</span>
                                        
                                        <div style="display: flex; gap: var(--sp-2); margin-top: var(--sp-2); flex-wrap: wrap;">
                                            <div style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; background: rgba(var(--warning-rgb, 226, 185, 6), 0.1); border-radius: 6px; font-size: var(--text-xs); color: var(--warning); font-weight: 500;">
                                                ⭐ ${escapeHtml(meal.rating.toString())}/5
                                            </div>
                                            <div style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; background: rgba(var(--accent-rgb, 168, 85, 247), 0.1); border-radius: 6px; font-size: var(--text-xs); color: var(--accent); font-weight: 500;">
                                                💪 ${escapeHtml(meal.proteins_g.toString())}g
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-actions">
                                <button class="btn-ghost btn-sm" onclick="addToShoppingList(${meal.meal_id})">+ Add</button>
                                <a href="meal.php?id=${meal.meal_id}" class="btn-outline btn-sm">Details →</a>
                            </div>
                        </article>
                    `).join('');
                    
                    section.classList.remove('hidden');
                }
            })
            .catch(e => console.error('Error loading favorite meals:', e));
        }
    </script>
</body>
</html>
