<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';

$meal_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($meal_id < 1) {
    header('Location: search.php');
    exit;
}

$sql = "SELECT m.meal_id, m.meal_name, m.meal_icon, m.description, m.preparation_time,
        c.category_name, n.calories, n.proteins_g, n.carbs_g, n.fats_g, n.fiber_g, n.iron_mg, n.vitamins
        FROM meals m
        JOIN categories c ON m.category_id = c.category_id
        JOIN nutrition n ON m.meal_id = n.meal_id
        WHERE m.meal_id = $meal_id";

$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    header('Location: search.php');
    exit;
}

$meal = $result->fetch_assoc();
$total_macros = $meal['proteins_g'] + $meal['carbs_g'] + $meal['fats_g'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $meal['meal_name']; ?> - NutriPlan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
</head>
<body>
    <div class="app-shell">
        <?php include 'components/sidebar.php'; ?>
        
        <main class="main page-enter">
            <!-- Back Link -->
            <a href="search.php" style="display: inline-flex; align-items: center; gap: var(--sp-2); color: var(--primary); text-decoration: none; margin-bottom: var(--sp-6);">← Back to Search</a>
            
            <!-- Two Column Layout -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--sp-8); margin-bottom: var(--sp-8);">
                <!-- Left: Meal Hero -->
                <div>
                    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: var(--sp-6); text-align: center;">
                        <div style="font-size: 4rem; margin-bottom: var(--sp-4);"><?php echo $meal['meal_icon']; ?></div>
                        <h1><?php echo $meal['meal_name']; ?></h1>
                        <span class="chip active" style="display: inline-block; margin-top: var(--sp-4);">🏷️ <?php echo $meal['category_name']; ?></span>
                        <?php if (!empty($meal['preparation_time'])): ?>
                        <p style="color: var(--text-2); margin-top: var(--sp-4);">⏱️ <?php echo $meal['preparation_time']; ?> min</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right: Nutrition Info -->
                <div>
                    <h3 style="margin-bottom: var(--sp-4);">Nutrition Info</h3>
                    
                    <!-- Nutrition Ring -->
                    <svg viewBox="0 0 120 120" class="nutrition-ring" style="width: 200px; height: 200px; margin: 0 auto; display: block; margin-bottom: var(--sp-6);">
                        <circle cx="60" cy="60" r="50" fill="none" stroke="var(--elevated)" stroke-width="12"/>
                        <circle cx="60" cy="60" r="50" fill="none" stroke="var(--accent)" stroke-width="12" stroke-dasharray="<?php echo ($meal['proteins_g']/$total_macros)*314; ?> 314" stroke-dashoffset="-78"/>
                        <circle cx="60" cy="60" r="50" fill="none" stroke="var(--primary)" stroke-width="12" stroke-dasharray="<?php echo ($meal['carbs_g']/$total_macros)*314; ?> 314" stroke-dashoffset="<?php echo -(78 + ($meal['proteins_g']/$total_macros)*314); ?>"/>
                        <text x="60" y="58" text-anchor="middle" fill="var(--text-1)" font-size="18" font-weight="700"><?php echo $meal['calories']; ?></text>
                        <text x="60" y="74" text-anchor="middle" fill="var(--text-2)" font-size="10">kcal</text>
                    </svg>
                    
                    <!-- Macro Labels -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--sp-4); margin-bottom: var(--sp-6);">
                        <div style="background: rgba(192, 132, 252, 0.15); padding: var(--sp-4); border-radius: 8px; text-align: center;">
                            <div style="font-size: var(--text-xs); color: var(--text-2); margin-bottom: var(--sp-1);">PROTEIN</div>
                            <div style="font-size: var(--text-xl); font-weight: 700; color: var(--accent);"><?php echo $meal['proteins_g']; ?>g</div>
                        </div>
                        <div style="background: rgba(96, 165, 250, 0.15); padding: var(--sp-4); border-radius: 8px; text-align: center;">
                            <div style="font-size: var(--text-xs); color: var(--text-2); margin-bottom: var(--sp-1);">CARBS</div>
                            <div style="font-size: var(--text-xl); font-weight: 700; color: var(--primary);"><?php echo $meal['carbs_g']; ?>g</div>
                        </div>
                        <div style="background: rgba(251, 146, 60, 0.15); padding: var(--sp-4); border-radius: 8px; text-align: center;">
                            <div style="font-size: var(--text-xs); color: var(--text-2); margin-bottom: var(--sp-1);">FATS</div>
                            <div style="font-size: var(--text-xl); font-weight: 700; color: var(--warning);"><?php echo $meal['fats_g']; ?>g</div>
                        </div>
                    </div>
                    
                    <button class="btn btn-primary btn-full" onclick="addToShoppingList(<?php echo $meal['meal_id']; ?>)">+ Add to Shopping List</button>
                </div>
            </div>
            
            <!-- Tabs -->
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: var(--sp-6);">
                <div style="display: flex; gap: var(--sp-6); margin-bottom: var(--sp-6); border-bottom: 1px solid var(--border); padding-bottom: var(--sp-4);">
                    <button class="tab-btn active" data-tab="ingredients" style="background: none; border: none; color: var(--text-1); font-size: var(--text-sm); font-weight: 600; cursor: pointer;">📋 Ingredients</button>
                    <button class="tab-btn" data-tab="nutrition" style="background: none; border: none; color: var(--text-2); font-size: var(--text-sm); font-weight: 600; cursor: pointer;">💪 Nutrition</button>
                    <button class="tab-btn" data-tab="preparation" style="background: none; border: none; color: var(--text-2); font-size: var(--text-sm); font-weight: 600; cursor: pointer;">👨‍🍳 Preparation</button>
                </div>
                
                <!-- Ingredients Tab -->
                <div id="ingredients" class="tab-panel active">
                    <p style="color: var(--text-2); margin-bottom: var(--sp-4);">Typical ingredients for this meal. Adjust quantities based on serving size.</p>
                    <ul style="list-style: none;">
                        <li style="padding: var(--sp-3) 0; border-bottom: 1px solid var(--border);">
                            <span style="color: var(--text-1); font-weight: 500;">Main ingredient</span>
                            <span style="float: right; color: var(--text-2);">varies</span>
                        </li>
                        <li style="padding: var(--sp-3) 0; border-bottom: 1px solid var(--border);">
                            <span style="color: var(--text-1); font-weight: 500;">Seasoning</span>
                            <span style="float: right; color: var(--text-2);">to taste</span>
                        </li>
                        <li style="padding: var(--sp-3) 0;">
                            <span style="color: var(--text-1); font-weight: 500;">Optional extras</span>
                            <span style="float: right; color: var(--text-2);">varies</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Nutrition Tab -->
                <div id="nutrition" class="tab-panel hidden">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--sp-6);">
                        <div>
                            <div style="padding: var(--sp-3); border-bottom: 1px solid var(--border);">
                                <div style="color: var(--text-2); font-size: var(--text-sm);">Calories</div>
                                <div style="font-size: var(--text-xl); font-weight: 700; color: var(--warning); margin-top: var(--sp-1);"><?php echo $meal['calories']; ?> kcal</div>
                            </div>
                            <div style="padding: var(--sp-3); border-bottom: 1px solid var(--border);">
                                <div style="color: var(--text-2); font-size: var(--text-sm);">Protein</div>
                                <div style="font-size: var(--text-lg); font-weight: 600; color: var(--accent); margin-top: var(--sp-1);"><?php echo $meal['proteins_g']; ?>g</div>
                            </div>
                            <div style="padding: var(--sp-3);">
                                <div style="color: var(--text-2); font-size: var(--text-sm);">Iron</div>
                                <div style="font-size: var(--text-lg); font-weight: 600; color: var(--primary); margin-top: var(--sp-1);"><?php echo $meal['iron_mg']; ?>mg</div>
                            </div>
                        </div>
                        <div>
                            <div style="padding: var(--sp-3); border-bottom: 1px solid var(--border);">
                                <div style="color: var(--text-2); font-size: var(--text-sm);">Carbs</div>
                                <div style="font-size: var(--text-lg); font-weight: 600; color: var(--primary); margin-top: var(--sp-1);"><?php echo $meal['carbs_g']; ?>g</div>
                            </div>
                            <div style="padding: var(--sp-3); border-bottom: 1px solid var(--border);">
                                <div style="color: var(--text-2); font-size: var(--text-sm);">Fats</div>
                                <div style="font-size: var(--text-lg); font-weight: 600; color: var(--warning); margin-top: var(--sp-1);"><?php echo $meal['fats_g']; ?>g</div>
                            </div>
                            <div style="padding: var(--sp-3);">
                                <div style="color: var(--text-2); font-size: var(--text-sm);">Fiber</div>
                                <div style="font-size: var(--text-lg); font-weight: 600; color: var(--success); margin-top: var(--sp-1);"><?php echo $meal['fiber_g']; ?>g</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Preparation Tab -->
                <div id="preparation" class="tab-panel hidden">
                    <ol style="list-style: decimal; padding-left: var(--sp-6);">
                        <li style="margin-bottom: var(--sp-4); color: var(--text-1);">
                            <span style="font-weight: 500;">Prepare ingredients</span>
                            <p style="color: var(--text-2); font-size: var(--text-sm); margin-top: var(--sp-1);">Wash and cut all ingredients according to recipe requirements.</p>
                        </li>
                        <li style="margin-bottom: var(--sp-4); color: var(--text-1);">
                            <span style="font-weight: 500;">Cook</span>
                            <p style="color: var(--text-2); font-size: var(--text-sm); margin-top: var(--sp-1);">Heat your cooking medium and begin cooking. Follow traditional preparation methods.</p>
                        </li>
                        <li style="color: var(--text-1);">
                            <span style="font-weight: 500;">Season and serve</span>
                            <p style="color: var(--text-2); font-size: var(--text-sm); margin-top: var(--sp-1);">Add seasonings to taste and serve hot while fresh.</p>
                        </li>
                    </ol>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js" defer></script>
</body>
</html>
