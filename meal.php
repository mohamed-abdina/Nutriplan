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
        WHERE m.meal_id = ?";

$meal = pdo_fetch_one($sql, [$meal_id]);

if (!$meal) {
    header('Location: search.php');
    exit;
}
$total_macros = (int)$meal['proteins_g'] + (int)$meal['carbs_g'] + (int)$meal['fats_g'];

// Get user rating
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$user_rating = pdo_fetch_one("SELECT rating, review, is_favorite FROM meal_ratings WHERE user_id = ? AND meal_id = ?", [$user_id, $meal_id]);

// Get meal sources
$sources = pdo_fetch_all("SELECT recipe_url, source_name, source_type FROM meal_sources WHERE meal_id = ?", [$meal_id]) ?? [];

// Get average rating
$avg_rating_row = pdo_fetch_one("SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings FROM meal_ratings WHERE meal_id = ? AND rating > 0", [$meal_id]);
$avg_rating = $avg_rating_row && $avg_rating_row['avg_rating'] ? round($avg_rating_row['avg_rating'], 1) : 0;
$total_ratings = $avg_rating_row ? (int)$avg_rating_row['total_ratings'] : 0;
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
            <a href="search.php" class="back-link">← Back to Search</a>
            
            <!-- Two Column Layout -->
            <div class="two-column-layout">
                <!-- Left: Meal Hero -->
                <div>
                    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: var(--sp-6); text-align: center;">
                        <div style="font-size: 4rem; margin-bottom: var(--sp-4);"><?php echo $meal['meal_icon']; ?></div>
                        <h1><?php echo $meal['meal_name']; ?></h1>
                        <span class="chip active" style="display: inline-block; margin-top: var(--sp-4);">🏷️ <?php echo $meal['category_name']; ?></span>
                        <?php if (!empty($meal['preparation_time'])): ?>
                        <p style="color: var(--text-2); margin-top: var(--sp-4);">⏱️ <?php echo $meal['preparation_time']; ?> min</p>
                        <?php endif; ?>
                        
                        <!-- Rating Display -->
                        <?php if ($total_ratings > 0): ?>
                        <div style="margin-top: var(--sp-6); padding-top: var(--sp-6); border-top: 1px solid var(--border);">
                            <div style="font-size: var(--text-sm); color: var(--text-2); margin-bottom: var(--sp-2);">User Rating</div>
                            <div style="font-size: 1.5rem; color: var(--warning);">★ <?php echo $avg_rating; ?>/5 (<?php echo $total_ratings; ?> ratings)</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right: Nutrition Info -->
                <div>
                    <h3 style="margin-bottom: var(--sp-4);">Nutrition Info</h3>
                    
                    <!-- Nutrition Ring -->
                    <svg viewBox="0 0 120 120" class="nutrition-ring">
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
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: var(--sp-4);" class="meal-actions">
                        <button class="btn btn-primary btn-full" onclick="addToShoppingList(<?php echo $meal['meal_id']; ?>)">+ Shopping List</button>
                        <button class="btn btn-secondary btn-full" onclick="toggleFavorite(<?php echo $meal['meal_id']; ?>)" id="favorite-btn" style="border: 2px solid var(--<?php echo $user_rating && $user_rating['is_favorite'] ? 'warning' : 'border'; ?>);">
                            <?php echo ($user_rating && $user_rating['is_favorite']) ? '💛 Favorite' : '🤍 Add to Favorites'; ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Tabs -->
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: var(--sp-6);">
                <div class="tab-button-group">
                    <button class="tab-button active" data-tab="ingredients">📋 Ingredients</button>
                    <button class="tab-button" data-tab="nutrition">💪 Nutrition</button>
                    <button class="tab-button" data-tab="preparation">👨‍🍳 Preparation</button>
                    <button class="tab-button" data-tab="ratings">⭐ Your Rating</button>
                    <button class="tab-button" data-tab="sources">🔗 Recipe Sources</button>
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
                
                <!-- Ratings Tab -->
                <div id="ratings" class="tab-panel hidden">
                    <h4 style="margin-bottom: var(--sp-4);">Rate this Meal</h4>
                    <div style="background: var(--elevated); padding: var(--sp-6); border-radius: 12px; margin-bottom: var(--sp-6);">
                        <div style="margin-bottom: var(--sp-4);">
                            <label style="color: var(--text-2); font-size: var(--text-sm); margin-bottom: var(--sp-2); display: block;">Your Rating</label>
                            <div style="display: flex; gap: var(--sp-2); margin-bottom: var(--sp-4);">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button onclick="selectRating(<?php echo $i; ?>)" class="rating-star" data-rating="<?php echo $i; ?>" style="background: none; border: none; font-size: 2rem; cursor: pointer; opacity: <?php echo ($user_rating && $user_rating['rating'] >= $i) ? '1' : '0.3'; ?>;">★</button>
                                <?php endfor; ?>
                            </div>
                            <div style="color: var(--primary); font-size: var(--text-sm);" id="rating-display"><?php echo $user_rating ? "Your rating: {$user_rating['rating']}/5" : "No rating yet"; ?></div>
                        </div>
                        
                        <div style="margin-bottom: var(--sp-4);">
                            <label style="color: var(--text-2); font-size: var(--text-sm); margin-bottom: var(--sp-2); display: block;">Your Review (optional)</label>
                            <textarea id="review-text" style="width: 100%; padding: var(--sp-3); border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text-1); resize: vertical; min-height: 100px;"><?php echo $user_rating ? htmlspecialchars($user_rating['review']) : ''; ?></textarea>
                        </div>
                        
                        <button class="btn btn-primary btn-full" onclick="submitRating(<?php echo $meal_id; ?>)">Submit Rating</button>
                    </div>
                </div>
                
                <!-- Recipe Sources Tab -->
                <div id="sources" class="tab-panel hidden">
                    <h4 style="margin-bottom: var(--sp-4);">Recipe Sources</h4>
                    <?php if (!empty($sources)): ?>
                    <p style="color: var(--text-2); margin-bottom: var(--sp-4);">Check out these recipes to learn how to prepare this meal:</p>
                    <div style="display: grid; gap: var(--sp-4);">
                        <?php foreach ($sources as $source): ?>
                        <div style="background: var(--elevated); padding: var(--sp-4); border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 600; color: var(--text-1); margin-bottom: var(--sp-1);"><?php echo htmlspecialchars($source['source_name']); ?></div>
                                <div style="font-size: var(--text-sm); color: var(--text-2);"><?php echo htmlspecialchars($source['source_type']); ?></div>
                            </div>
                            <div style="display: flex; gap: var(--sp-2);">
                                <a href="<?php echo htmlspecialchars($source['recipe_url']); ?>" target="_blank" class="btn btn-secondary" style="text-decoration: none; white-space: nowrap;">View Recipe →</a>
                                <button onclick="shareRecipe('<?php echo urlencode($source['recipe_url']); ?>', '<?php echo urlencode($source['source_name']); ?>')" class="btn btn-secondary" style="white-space: nowrap;">Share</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p style="color: var(--text-2);">No recipe sources available for this meal.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js" defer></script>
    <script>
        let currentRating = <?php echo ($user_rating ? $user_rating['rating'] : '0'); ?>;
        const mealId = <?php echo $meal_id; ?>;
        
        function selectRating(rating) {
            currentRating = rating;
            document.querySelectorAll('.rating-star').forEach((star, idx) => {
                star.style.opacity = (idx + 1) <= rating ? '1' : '0.3';
            });
            document.getElementById('rating-display').textContent = `Selected: ${rating}/5`;
        }
        
        function submitRating(mealId) {
            if (currentRating === 0) {
                alert('Please select a rating');
                return;
            }
            
            const review = document.getElementById('review-text').value;
            
            fetch('api/meal_ratings.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=rate&meal_id=${mealId}&rating=${currentRating}&review=${encodeURIComponent(review)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Rating saved!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(e => console.error('Error:', e));
        }
        
        function toggleFavorite(mealId) {
            fetch('api/meal_ratings.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=toggle_favorite&meal_id=${mealId}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const btn = document.getElementById('favorite-btn');
                    if (data.is_favorite) {
                        btn.textContent = '💛 Favorite';
                        btn.style.borderColor = 'var(--warning)';
                    } else {
                        btn.textContent = '🤍 Add to Favorites';
                        btn.style.borderColor = 'var(--border)';
                    }
                }
            })
            .catch(e => console.error('Error:', e));
        }
        
        function shareRecipe(url, source) {
            const text = `Check out this recipe for ${source} on NutriPlan!`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Recipe',
                    text: text,
                    url: decodeURIComponent(url)
                });
            } else {
                // Fallback: open share dialog or copy
                const shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${url}`;
                window.open(shareUrl, '_blank', 'width=550,height=420');
            }
        }
        
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');
                
                // Hide all tabs
                document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.style.color = 'var(--text-2)';
                });
                
                // Show selected tab
                document.getElementById(tabName).classList.remove('hidden');
                this.style.color = 'var(--text-1)';
                this.classList.add('active');
            });
        });
    </script>
