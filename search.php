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
<html lang="en">
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
                    <input type="text" id="searchInput" placeholder=" " autocomplete="off" aria-label="Search meals by name or ingredient" role="searchbox" onkeyup="debounceSearchHandle()">
                    <label for="searchInput">Search by meal name or ingredient</label>
                </div>
            </div>
            
            <!-- Filter Chips -->
            <div style="margin-bottom: var(--sp-8);">
                <p style="font-size: var(--text-sm); color: var(--text-2); margin-bottom: var(--sp-3);">Category</p>
                <div class="chip-group" role="group" aria-label="Filter meals by category">
                    <button class="chip active" data-filter="all" aria-pressed="true" onclick="handleSearch()">All</button>
                    <?php foreach ($categories as $cat): ?>
                    <button class="chip" data-filter="<?php echo $cat['category_id']; ?>" aria-pressed="false" onclick="handleSearch()"><?php echo $cat['category_icon']; ?> <?php echo $cat['category_name']; ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Advanced Filters Section -->
            <div style="margin-bottom: var(--sp-8);">
                <button class="btn-ghost" id="toggleAdvanced" onclick="toggleAdvancedFilters()" style="width: 100%; text-align: left; padding: var(--sp-3); margin-bottom: var(--sp-4); display: flex; justify-content: space-between; align-items: center;">
                    <span>🎯 Advanced Filters</span>
                    <span id="advancedToggleIcon">▼</span>
                </button>
                
                <div id="advancedFilters" class="hidden" style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: var(--sp-6); margin-bottom: var(--sp-6);">
                    <!-- Sorting -->
                    <div style="margin-bottom: var(--sp-6);">
                        <label for="sortBy" style="font-size: var(--text-sm); color: var(--text-2); display: block; margin-bottom: var(--sp-2);">Sort By</label>
                        <select id="sortBy" onchange="handleSearch()" style="width: 100%; padding: var(--sp-3); background: var(--inset); border: 1px solid var(--border); border-radius: 8px; color: var(--text-1); cursor: pointer;">
                            <option value="name">Name (A-Z)</option>
                            <option value="relevance">Relevance</option>
                            <option value="calories_low">Calories (Low to High)</option>
                            <option value="calories_high">Calories (High to Low)</option>
                            <option value="protein_high">Protein (High to Low)</option>
                            <option value="protein_low">Protein (Low to High)</option>
                        </select>
                    </div>
                    
                    <!-- Calorie Range -->
                    <div style="margin-bottom: var(--sp-6);">
                        <label style="font-size: var(--text-sm); color: var(--text-2); display: block; margin-bottom: var(--sp-2);">🔥 Calorie Range</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--sp-3);">
                            <div>
                                <input type="number" id="minCal" placeholder="Min" min="0" max="5000" value="0" onchange="handleSearch()" style="width: 100%; padding: var(--sp-2); background: var(--inset); border: 1px solid var(--border); border-radius: 8px; color: var(--text-1);">
                            </div>
                            <div>
                                <input type="number" id="maxCal" placeholder="Max" min="0" max="5000" value="5000" onchange="handleSearch()" style="width: 100%; padding: var(--sp-2); background: var(--inset); border: 1px solid var(--border); border-radius: 8px; color: var(--text-1);">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Protein Range -->
                    <div style="margin-bottom: var(--sp-6);">
                        <label style="font-size: var(--text-sm); color: var(--text-2); display: block; margin-bottom: var(--sp-2);">💪 Protein Range (g)</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--sp-3);">
                            <div>
                                <input type="number" id="minProtein" placeholder="Min" min="0" max="200" value="0" onchange="handleSearch()" style="width: 100%; padding: var(--sp-2); background: var(--inset); border: 1px solid var(--border); border-radius: 8px; color: var(--text-1);">
                            </div>
                            <div>
                                <input type="number" id="maxProtein" placeholder="Max" min="0" max="200" value="200" onchange="handleSearch()" style="width: 100%; padding: var(--sp-2); background: var(--inset); border: 1px solid var(--border); border-radius: 8px; color: var(--text-1);">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reset Button -->
                    <button class="btn btn-outline btn-sm" onclick="resetFilters()" style="width: 100%;">Reset Filters</button>
                </div>
            </div>
            
            <!-- Results Grid -->
            <div id="results-container" class="grid-2 stagger-container" role="region" aria-label="Search results" aria-live="polite" aria-busy="false">
                <!-- Results will be loaded here -->
            </div>
            
            <!-- Load More Button -->
            <div id="loadMoreContainer" class="hidden" style="text-align: center; margin: var(--sp-8) 0;">
                <button id="loadMoreBtn" class="btn btn-primary" onclick="loadMoreMeals()">📬 Load More Meals</button>
            </div>
            
            <!-- No Results -->
            <div id="no-results" class="hidden" style="text-align: center; padding: var(--sp-12);" role="status" aria-live="polite" aria-label="No meals found">
                <div style="font-size: 3rem; margin-bottom: var(--sp-4);">🍽</div>
                <h3>No meals found</h3>
                <p style="color: var(--text-2); margin-top: var(--sp-2);">Try different keywords or filters</p>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js" defer></script>
    <script>
        // Pagination state
        let currentOffset = 0;
        let canLoadMore = false;
        let isLoadingMore = false;
        let searchTimeout;

        // Toggle advanced filters visibility
        function toggleAdvancedFilters() {
            const filters = document.getElementById('advancedFilters');
            const icon = document.getElementById('advancedToggleIcon');
            filters.classList.toggle('hidden');
            icon.textContent = filters.classList.contains('hidden') ? '▼' : '▲';
        }

        // Reset all filters
        function resetFilters() {
            document.getElementById('minCal').value = '0';
            document.getElementById('maxCal').value = '5000';
            document.getElementById('minProtein').value = '0';
            document.getElementById('maxProtein').value = '200';
            document.getElementById('sortBy').value = 'name';
            handleSearch();
        }

        // Debounced search to prevent too many requests
        function debounceSearchHandle() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                handleSearch();
            }, 300);
        }

        async function handleSearch() {
            currentOffset = 0;
            const container = document.getElementById('results-container');
            container.innerHTML = '';
            performSearch();
        }

        async function performSearch(append = false) {
            const query = document.getElementById('searchInput').value;
            const activeChip = document.querySelector('.chip.active');
            const category = activeChip && activeChip.dataset.filter !== 'all' ? activeChip.dataset.filter : '';
            const sort = document.getElementById('sortBy').value;
            const minCal = document.getElementById('minCal').value;
            const maxCal = document.getElementById('maxCal').value;
            const minProtein = document.getElementById('minProtein').value;
            const maxProtein = document.getElementById('maxProtein').value;
            
            // Update aria-pressed state on chips
            document.querySelectorAll('.chip').forEach(chip => {
                const isActive = chip === activeChip;
                chip.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            const container = document.getElementById('results-container');
            const noResults = document.getElementById('no-results');
            const loadMoreContainer = document.getElementById('loadMoreContainer');

            if (!append) {
                // Mark as loading for screen readers
                container.setAttribute('aria-busy', 'true');

                // Show skeleton loaders
                const skeletonCount = 6;
                container.innerHTML = Array.from({length: skeletonCount}).map(() => `
                    <article class="meal-card skeleton" style="height: 120px; margin-bottom: var(--sp-4);">
                        <div class="card-accent-strip"></div>
                        <div class="card-body flex">
                            <div class="card-icon skeleton" style="width: 48px; height: 48px;"></div>
                            <div style="flex: 1; margin-left: var(--sp-4);">
                                <div class="card-title skeleton" style="width: 60%; height: 18px; margin-bottom: 8px;"></div>
                                <div class="card-category skeleton" style="width: 40%; height: 14px; margin-bottom: 8px;"></div>
                                <div class="card-nutrients skeleton" style="width: 80%; height: 12px;"></div>
                            </div>
                        </div>
                    </article>
                `).join('');
            }

            try {
                const params = new URLSearchParams({
                    q: query,
                    cat: category,
                    offset: currentOffset,
                    sort: sort,
                    min_cal: minCal,
                    max_cal: maxCal,
                    min_protein: minProtein,
                    max_protein: maxProtein
                });

                const response = await fetch(`/api/search_api.php?${params}`);
                const data = await response.json();

                container.setAttribute('aria-busy', 'false');

                if (data.meals && data.meals.length > 0) {
                    const mealHtml = data.meals.map((meal, index) => `
                        <article class="meal-card stagger-item" style="--card-accent: var(--primary); animation-delay: ${index * 60}ms">
                            <div class="card-accent-strip"></div>
                            <div class="card-body">
                                <div class="card-icon" aria-hidden="true">${escapeHtml(meal.meal_icon)}</div>
                                <div style="flex: 1;">
                                    <div class="card-title">${escapeHtml(meal.meal_name)}</div>
                                    <span class="card-category">${escapeHtml(meal.category_name)}</span>
                                    <p class="card-nutrients">Cal: ${escapeHtml(meal.calories.toString())} · Protein: ${escapeHtml(meal.proteins_g.toString())}g</p>
                                </div>
                            </div>
                            <div class="card-actions">
                                <button class="btn-ghost btn-sm" aria-label="Add ${escapeHtml(meal.meal_name)} to shopping list" onclick="addToShoppingList(${meal.meal_id})">+ Add</button>
                                <a href="meal.php?id=${meal.meal_id}" class="btn-outline btn-sm">Details →</a>
                            </div>
                        </article>
                    `).join('');

                    if (append) {
                        container.innerHTML += mealHtml;
                    } else {
                        container.innerHTML = mealHtml;
                    }

                    noResults.classList.add('hidden');
                    canLoadMore = data.has_more;
                    
                    if (canLoadMore) {
                        loadMoreContainer.classList.remove('hidden');
                    } else {
                        loadMoreContainer.classList.add('hidden');
                    }
                } else {
                    if (!append) {
                        container.innerHTML = '';
                        noResults.classList.remove('hidden');
                        loadMoreContainer.classList.add('hidden');
                    }
                }
            } catch (error) {
                console.error('Search error:', error);
                container.setAttribute('aria-busy', 'false');
                showToast('Search failed', 'error');
                if (!append) {
                    container.innerHTML = '';
                    noResults.classList.remove('hidden');
                }
            }
        }

        function loadMoreMeals() {
            if (isLoadingMore || !canLoadMore) return;
            isLoadingMore = true;
            
            document.getElementById('loadMoreBtn').disabled = true;
            document.getElementById('loadMoreBtn').textContent = '⏳ Loading...';
            
            currentOffset += 12;
            performSearch(true).then(() => {
                isLoadingMore = false;
                document.getElementById('loadMoreBtn').disabled = false;
                document.getElementById('loadMoreBtn').textContent = '📬 Load More Meals';
            });
        }
        
        // Load initial meals on page load
        document.addEventListener('DOMContentLoaded', () => {
            handleSearch();
        });
    </script>
</body>
</html>
