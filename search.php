<?php
require_once __DIR__ . '/includes/session.php';
secure_session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

// Get all categories for filter chips
$categories = pdo_fetch_all("SELECT category_id, category_name, category_icon FROM categories ORDER BY category_id") ?? [];
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Meals - NutriPlan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
</head>
<body>
    <div class="app-shell">
        <?php include 'components/sidebar.php'; ?>
        
        <main class="main page-enter">
            <!-- Topbar -->
            <div class="topbar">
                <h1>🔍 Search Meals</h1>
            </div>
            
            <!-- Search Bar -->
            <div style="background: var(--surface); padding: var(--sp-6); border-radius: 14px; border: 1px solid var(--border); margin-bottom: var(--sp-8);">
                <div class="field" style="margin-bottom: 0;">
                    <input type="text" id="searchInput" placeholder=" " autocomplete="off" onkeyup="handleSearch()">
                    <label for="searchInput">Search by meal name or ingredient</label>
                </div>
            </div>
            
            <!-- Filter Chips -->
            <div style="margin-bottom: var(--sp-8);">
                <p style="font-size: var(--text-sm); color: var(--text-2); margin-bottom: var(--sp-3);">Category</p>
                <div class="chip-group">
                    <button class="chip active" data-filter="all" onclick="handleSearch()">All</button>
                    <?php foreach ($categories as $cat): ?>
                    <button class="chip" data-filter="<?php echo $cat['category_id']; ?>" onclick="handleSearch()"><?php echo $cat['category_icon']; ?> <?php echo $cat['category_name']; ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Results Grid -->
            <div id="results-container" class="grid-2 stagger-container">
                <!-- Results will be loaded here -->
            </div>
            
            <!-- No Results -->
            <div id="no-results" class="hidden" style="text-align: center; padding: var(--sp-12);">
                <div style="font-size: 3rem; margin-bottom: var(--sp-4);">🍽</div>
                <h3>No meals found</h3>
                <p style="color: var(--text-2); margin-top: var(--sp-2);">Try different keywords or filters</p>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js" defer></script>
    <script>
        async function handleSearch() {
            const query = document.getElementById('searchInput').value;
            const activeChip = document.querySelector('.chip.active');
            const category = activeChip && activeChip.dataset.filter !== 'all' ? activeChip.dataset.filter : '';
            
            if (!query && !category) {
                document.getElementById('results-container').innerHTML = '';
                return;
            }
            
            try {
                const url = `/api/search_api.php?q=${encodeURIComponent(query)}&cat=${category}`;
                const response = await fetch(url);
                const data = await response.json();
                
                const container = document.getElementById('results-container');
                const noResults = document.getElementById('no-results');
                
                if (data.meals && data.meals.length > 0) {
                    container.innerHTML = data.meals.map((meal, index) => `
                        <article class="meal-card stagger-item" style="--card-accent: var(--primary); animation-delay: ${index * 60}ms">
                            <div class="card-accent-strip"></div>
                            <div class="card-body">
                                <div class="card-icon">${meal.meal_icon}</div>
                                <div style="flex: 1;">
                                    <div class="card-title">${meal.meal_name}</div>
                                    <span class="card-category">${meal.category_name}</span>
                                    <p class="card-nutrients">Cal: ${meal.calories} · Protein: ${meal.proteins_g}g</p>
                                </div>
                            </div>
                            <div class="card-actions">
                                <button class="btn-ghost btn-sm" onclick="addToShoppingList(${meal.meal_id})">+ Add</button>
                                <a href="meal.php?id=${meal.meal_id}" class="btn-outline btn-sm">Details →</a>
                            </div>
                        </article>
                    `).join('');
                    noResults.classList.add('hidden');
                } else {
                    container.innerHTML = '';
                    noResults.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Search error:', error);
                showToast('Search failed', 'error');
            }
        }
        
        // Load initial meals
        handleSearch();
    </script>
</body>
</html>
