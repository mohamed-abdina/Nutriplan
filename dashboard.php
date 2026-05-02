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

// Get user-specific planned meals and recommendations
$today = date('Y-m-d');
$today_meals = get_today_meals($conn, $user_id);
$meals = [];
$total_calories = 0;
$total_protein = 0;
$carbs_total = 0;
$fiber_total = 0;

if (is_array($today_meals) && !empty($today_meals)) {
    foreach ($today_meals as $row) {
        $total_calories += (int)$row['calories'];
        $total_protein += (int)$row['proteins_g'];
        $carbs_total += (int)$row['carbs_g'];
        $fiber_total += (int)$row['fiber_g'];
    }
}

$week_stats = get_week_stats($conn, $user_id);

$recommended_sql = "SELECT m.meal_id, m.meal_name, m.meal_icon, c.category_name, n.calories, n.proteins_g, n.carbs_g, n.fiber_g
        FROM meals m
        JOIN categories c ON m.category_id = c.category_id
        JOIN nutrition n ON m.meal_id = n.meal_id
        ORDER BY m.meal_id DESC
        LIMIT 8";

$fetched_recommendations = pdo_fetch_all($recommended_sql);
if ($fetched_recommendations !== false && is_array($fetched_recommendations)) {
    $meals = $fetched_recommendations;
}

// Get cart stats
$cart_stats = pdo_fetch_one(
    "SELECT COUNT(DISTINCT ci.item_id) as total, 
            SUM(CASE WHEN ci.purchased = 0 THEN 1 ELSE 0 END) as unpurchased
        FROM carts c
        LEFT JOIN cart_items ci ON c.list_id = ci.list_id
        WHERE c.user_id = ?",
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
    <?php require_once __DIR__ . '/includes/csrf.php'; ?>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
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
                    ['label' => 'Meals Planned Today', 'value' => count($today_meals), 'color' => 'var(--primary)', 'icon' => '🗓'],
                    ['label' => 'Today Protein', 'value' => $total_protein . 'g', 'color' => 'var(--accent)', 'icon' => '💪', 'trend' => $protein_trend],
                    ['label' => 'Today Calories', 'value' => (int)$total_calories, 'color' => 'var(--warning)', 'icon' => '🔥', 'trend' => $calories_trend],
                    ['label' => 'Weekly Planned Meals', 'value' => (int)($week_stats['total_meals'] ?? 0), 'color' => 'var(--success)', 'icon' => '📌'],
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

                <div class="mt-12 bg-overlay border radius-14 p-6">
                    <h2 class="mb-4">🗓 Weekly Meal Plan Builder</h2>
                    <p style="color: var(--text-2); margin-bottom: var(--sp-4);">Plan breakfast, lunch, snack, and dinner for each day. This writes directly to your meal schedule and supports reminders and sharing.</p>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: var(--sp-3); margin-bottom: var(--sp-4);">
                        <div>
                            <label for="planner-date" style="display: block; margin-bottom: var(--sp-2); color: var(--text-2);">Date</label>
                            <input id="planner-date" type="date" style="width: 100%; padding: var(--sp-2); border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text-1);">
                        </div>
                        <div>
                            <label for="planner-meal-type" style="display: block; margin-bottom: var(--sp-2); color: var(--text-2);">Meal Type</label>
                            <select id="planner-meal-type" style="width: 100%; padding: var(--sp-2); border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text-1);">
                                <option value="breakfast">Breakfast</option>
                                <option value="lunch">Lunch</option>
                                <option value="snack">Snack</option>
                                <option value="dinner">Dinner</option>
                            </select>
                        </div>
                        <div>
                            <label for="planner-meal" style="display: block; margin-bottom: var(--sp-2); color: var(--text-2);">Meal</label>
                            <select id="planner-meal" style="width: 100%; padding: var(--sp-2); border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text-1);">
                                <?php foreach ($meals as $meal_option): ?>
                                    <option value="<?php echo (int)$meal_option['meal_id']; ?>"><?php echo htmlspecialchars($meal_option['meal_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="planner-portion" style="display: block; margin-bottom: var(--sp-2); color: var(--text-2);">Portion Scale</label>
                            <input id="planner-portion" type="number" min="0.5" max="3" step="0.25" value="1" style="width: 100%; padding: var(--sp-2); border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text-1);">
                        </div>
                        <div>
                            <label for="planner-reminder" style="display: block; margin-bottom: var(--sp-2); color: var(--text-2);">Reminder</label>
                            <input id="planner-reminder" type="datetime-local" style="width: 100%; padding: var(--sp-2); border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text-1);">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr auto; gap: var(--sp-3); margin-bottom: var(--sp-4);">
                        <input id="planner-notes" type="text" placeholder="Optional notes" style="width: 100%; padding: var(--sp-2); border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text-1);">
                        <button class="btn btn-primary" onclick="saveMealPlan()">Save To Plan</button>
                    </div>

                    <div id="weekly-plan-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--sp-3);"></div>
                </div>

                <div class="mt-12" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--sp-4);">
                    <div class="bg-overlay border radius-14 p-6">
                        <h3 style="margin-bottom: var(--sp-4);">🎯 Nutritional Goals</h3>
                        <div style="display: grid; gap: var(--sp-3);">
                            <input id="goal-calories" type="number" min="800" max="6000" placeholder="Daily calories target" style="padding: var(--sp-2); border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text-1);">
                            <input id="goal-protein" type="number" min="20" max="400" placeholder="Daily protein target (g)" style="padding: var(--sp-2); border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text-1);">
                            <input id="goal-carbs" type="number" min="20" max="800" placeholder="Daily carbs target (g)" style="padding: var(--sp-2); border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text-1);">
                            <input id="goal-fats" type="number" min="10" max="300" placeholder="Daily fats target (g)" style="padding: var(--sp-2); border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text-1);">
                            <button class="btn btn-primary" onclick="saveNutritionGoals()">Save Goals</button>
                        </div>
                    </div>

                    <div class="bg-overlay border radius-14 p-6">
                        <h3 style="margin-bottom: var(--sp-4);">🤝 Share Weekly Plan</h3>
                        <p style="color: var(--text-2); margin-bottom: var(--sp-3);">Share one day of your plan with another user.</p>
                        <div style="display: grid; gap: var(--sp-3);">
                            <input id="share-username" type="text" placeholder="Teammate username" style="padding: var(--sp-2); border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text-1);">
                            <input id="share-date" type="date" style="padding: var(--sp-2); border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text-1);">
                            <label style="display: flex; align-items: center; gap: var(--sp-2); color: var(--text-2);">
                                <input id="share-can-edit" type="checkbox"> Allow collaborator edits
                            </label>
                            <button class="btn btn-outline" onclick="shareMealPlanDay()">Share Day Plan</button>
                        </div>
                    </div>

                    <div class="bg-overlay border radius-14 p-6">
                        <h3 style="margin-bottom: var(--sp-4);">⏰ Upcoming Reminders</h3>
                        <div id="reminders-list" style="display: grid; gap: var(--sp-2); color: var(--text-2);">
                            <p>No reminders loaded yet.</p>
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
                
                <!-- My Wishlist Section -->
                <div class="mt-12 hidden" id="youLoveSection">
                    <h2 class="mb-6">❤️ My Wishlist</h2>
                    <div class="grid-2 stagger-container" id="youLoveContainer">
                        <!-- Wishlist items loaded via JavaScript -->
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
    
    <script src="assets/js/main.js?v=<?php echo filemtime(__DIR__ . '/assets/js/main.js'); ?>" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            animateCounters();
            loadYouLoveMeals();
            initializePlannerDefaults();
            loadWeeklyPlan();
            loadNutritionGoals();
            loadReminders();
        });

        function plannerCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        function initializePlannerDefaults() {
            const today = new Date().toISOString().slice(0, 10);
            const dateField = document.getElementById('planner-date');
            const shareDateField = document.getElementById('share-date');
            if (dateField && !dateField.value) dateField.value = today;
            if (shareDateField && !shareDateField.value) shareDateField.value = today;
        }

        function weekStartISO() {
            const now = new Date();
            const day = now.getDay();
            const diff = now.getDate() - day + (day === 0 ? -6 : 1);
            const monday = new Date(now.setDate(diff));
            return monday.toISOString().slice(0, 10);
        }

        async function loadWeeklyPlan() {
            try {
                const start = weekStartISO();
                const response = await fetch(`${apiUrl('api/meal_planning.php')}?action=get_week&start_date=${encodeURIComponent(start)}`);
                const data = await response.json();
                if (!data.success) return;

                const plans = data.plans || [];
                const grouped = {};
                (data.week_dates || []).forEach(date => {
                    grouped[date] = [];
                });
                plans.forEach(plan => {
                    if (!grouped[plan.planned_date]) grouped[plan.planned_date] = [];
                    grouped[plan.planned_date].push(plan);
                });

                const grid = document.getElementById('weekly-plan-grid');
                if (!grid) return;
                grid.innerHTML = (data.week_dates || []).map(date => {
                    const dayPlans = grouped[date] || [];
                    const dayLabel = new Date(date + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
                    const plansHtml = dayPlans.length > 0
                        ? dayPlans.map(plan => `
                            <div style="padding: var(--sp-2); border: 1px solid var(--border); border-radius: 8px; background: var(--surface); margin-bottom: var(--sp-2);">
                                <div style="display: flex; justify-content: space-between; gap: var(--sp-2); align-items: center;">
                                    <div>
                                        <strong>${escapeHtml(plan.meal_type)}</strong>: ${escapeHtml(plan.meal_name)}
                                        <div style="font-size: var(--text-xs); color: var(--text-2);">Portion x${escapeHtml(String(plan.portion_multiplier || 1))}${plan.owner_username ? ` • by ${escapeHtml(plan.owner_username)}` : ''}</div>
                                    </div>
                                    ${Number(plan.user_id) === <?php echo (int)$user_id; ?> ? `<button class="btn btn-ghost btn-sm" onclick="removeMealPlan(${Number(plan.plan_id)})">Remove</button>` : ''}
                                </div>
                            </div>
                        `).join('')
                        : '<p style="color: var(--text-3); font-size: var(--text-sm);">No meals planned.</p>';

                    return `
                        <div style="padding: var(--sp-3); border: 1px solid var(--border); border-radius: 10px; background: var(--overlay);">
                            <h4 style="margin-bottom: var(--sp-3);">${escapeHtml(dayLabel)}</h4>
                            ${plansHtml}
                        </div>
                    `;
                }).join('');
            } catch (e) {
                console.error('Failed to load weekly plan:', e);
            }
        }

        async function saveMealPlan() {
            const formData = new URLSearchParams({
                action: 'add_plan',
                meal_id: document.getElementById('planner-meal').value,
                planned_date: document.getElementById('planner-date').value,
                meal_type: document.getElementById('planner-meal-type').value,
                notes: document.getElementById('planner-notes').value,
                portion_multiplier: document.getElementById('planner-portion').value || '1',
                reminder_at: document.getElementById('planner-reminder').value,
                csrf_token: plannerCsrfToken()
            });

            try {
                const response = await fetch(apiUrl('api/meal_planning.php'), {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    showToast('Meal added to weekly plan', 'success');
                    document.getElementById('planner-notes').value = '';
                    await loadWeeklyPlan();
                    await loadReminders();
                } else {
                    showToast(data.message || 'Could not save plan', 'error');
                }
            } catch (e) {
                console.error('Failed to save meal plan:', e);
                showToast('Could not save meal plan', 'error');
            }
        }

        async function removeMealPlan(planId) {
            const formData = new URLSearchParams({
                action: 'remove_plan',
                plan_id: String(planId),
                csrf_token: plannerCsrfToken()
            });
            try {
                const response = await fetch(apiUrl('api/meal_planning.php'), {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    showToast('Removed from plan', 'info');
                    await loadWeeklyPlan();
                }
            } catch (e) {
                console.error('Failed to remove meal plan:', e);
            }
        }

        async function loadNutritionGoals() {
            try {
                const response = await fetch(`${apiUrl('api/meal_planning.php')}?action=get_goals`);
                const data = await response.json();
                if (!data.success) return;
                const g = data.goals || {};
                document.getElementById('goal-calories').value = g.daily_calories_target || 2000;
                document.getElementById('goal-protein').value = g.daily_protein_target || 75;
                document.getElementById('goal-carbs').value = g.daily_carbs_target || 250;
                document.getElementById('goal-fats').value = g.daily_fats_target || 70;
            } catch (e) {
                console.error('Failed to load goals:', e);
            }
        }

        async function saveNutritionGoals() {
            const formData = new URLSearchParams({
                action: 'update_goals',
                daily_calories_target: document.getElementById('goal-calories').value,
                daily_protein_target: document.getElementById('goal-protein').value,
                daily_carbs_target: document.getElementById('goal-carbs').value,
                daily_fats_target: document.getElementById('goal-fats').value,
                csrf_token: plannerCsrfToken()
            });

            try {
                const response = await fetch(apiUrl('api/meal_planning.php'), {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    showToast('Nutrition goals saved', 'success');
                } else {
                    showToast(data.message || 'Failed to save goals', 'error');
                }
            } catch (e) {
                console.error('Failed to save goals:', e);
                showToast('Failed to save goals', 'error');
            }
        }

        async function shareMealPlanDay() {
            const formData = new URLSearchParams({
                action: 'share_plan',
                target_username: document.getElementById('share-username').value,
                planned_date: document.getElementById('share-date').value,
                can_edit: document.getElementById('share-can-edit').checked ? '1' : '0',
                csrf_token: plannerCsrfToken()
            });

            try {
                const response = await fetch(apiUrl('api/meal_planning.php'), {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    showToast('Plan shared successfully', 'success');
                    document.getElementById('share-username').value = '';
                } else {
                    showToast(data.message || 'Failed to share plan', 'error');
                }
            } catch (e) {
                console.error('Failed to share meal plan:', e);
                showToast('Failed to share plan', 'error');
            }
        }

        async function loadReminders() {
            try {
                const response = await fetch(`${apiUrl('api/meal_planning.php')}?action=get_reminders`);
                const data = await response.json();
                const list = document.getElementById('reminders-list');
                if (!list) return;

                if (!data.success || !Array.isArray(data.reminders) || data.reminders.length === 0) {
                    list.innerHTML = '<p>No reminders in the next 7 days.</p>';
                    return;
                }

                list.innerHTML = data.reminders.map(item => {
                    const when = new Date(item.reminder_at).toLocaleString();
                    return `<div style="padding: var(--sp-2); border: 1px solid var(--border); border-radius: 8px; background: var(--surface);">
                        <strong>${escapeHtml(item.meal_name)}</strong> (${escapeHtml(item.meal_type)})
                        <div style="font-size: var(--text-xs); color: var(--text-2);">${escapeHtml(when)}</div>
                    </div>`;
                }).join('');
            } catch (e) {
                console.error('Failed to load reminders:', e);
            }
        }

        function normalizeMealIcon(icon, categoryName = '') {
            const raw = String(icon || '').trim();
            const category = String(categoryName || '').toLowerCase();

            if (!raw || /^[a-z0-9\-\_\s]+$/i.test(raw)) {
                if (category.includes('breakfast')) return '🍳';
                if (category.includes('lunch')) return '🥗';
                if (category.includes('dinner') || category.includes('supper')) return '🍽️';
                if (category.includes('snack')) return '🥜';
                return '🍽️';
            }

            return raw;
        }
        
        function loadYouLoveMeals() {
            fetch(apiUrl('api/wishlist_api.php'), {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get_wishlist&limit=6'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && (data.wishlist || data.favorites) && (data.wishlist || data.favorites).length > 0) {
                    const section = document.getElementById('youLoveSection');
                    const container = document.getElementById('youLoveContainer');
                    const meals = data.wishlist || data.favorites;
                    
                    container.innerHTML = meals.map((meal, index) => 
                        generateMealCardHtml(meal, { 
                            animation_delay: index,
                            card_accent_override: 'var(--accent)'
                        })
                    ).join('');
                    
                    section.classList.remove('hidden');
                }
            })
            .catch(e => console.error('Error loading wishlist meals:', e));
        }
    </script>
</body>
</html>
