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
                    <button class="chip active" data-filter="all" aria-pressed="true" onclick="setActiveCategory(this)">All</button>
                    <?php foreach ($categories as $cat): ?>
                    <button class="chip" data-filter="<?php echo $cat['category_id']; ?>" aria-pressed="false" onclick="setActiveCategory(this)"><?php echo $cat['category_icon']; ?> <?php echo $cat['category_name']; ?></button>
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
    
    <script src="assets/js/main.js"></script>
    <script>
        // ========================================
        // SEARCH PAGE SCRIPT
        // Ensure main.js has loaded first
        // ========================================
        
        // Wait for generateMealCardHtml to be available before search runs
        let functionCheckInterval;
        const maxAttempts = 20;
        let attempts = 0;
        let searchInitialized = false;
           const SEARCH_REQUEST_TIMEOUT_MS = 10000;
           const SEARCH_STATE_KEY = 'nutriplan_search_state';
           const SEARCH_DEBUG_ENABLED = true;
           let searchRequestSequence = 0;

           function searchDebug(step, payload = null) {
               if (!SEARCH_DEBUG_ENABLED) return;
               const timestamp = new Date().toISOString();
               if (payload === null || payload === undefined) {
                   console.log(`[SEARCH_DEBUG ${timestamp}] ${step}`);
                   return;
               }
               console.log(`[SEARCH_DEBUG ${timestamp}] ${step}`, payload);
           }

           window.addEventListener('error', (event) => {
               searchDebug('Window error captured', {
                   message: event.message,
                   source: event.filename,
                   line: event.lineno,
                   column: event.colno
               });
           });

           window.addEventListener('unhandledrejection', (event) => {
               searchDebug('Unhandled promise rejection captured', {
                   reason: event.reason && event.reason.message ? event.reason.message : String(event.reason)
               });
           });

        function waitForGenerateMealCardHtml() {
               searchDebug('Checking generateMealCardHtml availability', {
                   attempt: attempts + 1,
                   maxAttempts,
                   readyState: document.readyState,
                   hasFunction: typeof window.generateMealCardHtml === 'function'
               });

            if (typeof window.generateMealCardHtml === 'function') {
                clearInterval(functionCheckInterval);
                console.log('✓ generateMealCardHtml is available, initializing search...');
                initializeSearch();
                return;
            }

            attempts++;
            if (attempts >= maxAttempts) {
                clearInterval(functionCheckInterval);
                console.error('generateMealCardHtml never became available');
                   searchDebug('generateMealCardHtml unavailable after max attempts; showing fallback state');
                const container = document.getElementById('results-container');
                const noResults = document.getElementById('no-results');
                if (container) {
                    container.innerHTML = '';
                    container.setAttribute('aria-busy', 'false');
                }
                if (noResults) {
                    noResults.classList.remove('hidden');
                }
            }
        }

        function startSearchBootstrap() {
               searchDebug('startSearchBootstrap invoked', { readyState: document.readyState });
            functionCheckInterval = setInterval(waitForGenerateMealCardHtml, 200);
            waitForGenerateMealCardHtml();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', startSearchBootstrap, { once: true });
        } else {
            startSearchBootstrap();
        }
        // Pagination state
        let currentOffset = 0;
        let canLoadMore = false;
        let isLoadingMore = false;
        let searchTimeout;
        const searchDebugState = {
            initialized: false,
            initAttempts: 0,
            lastRequestId: null,
            lastRequestUrl: null,
            lastResponseStatus: null,
            lastResponseOk: null,
            lastResponseContentType: null,
            lastResponseBodyPreview: null,
            lastApiSuccessFlag: null,
            lastApiMessage: null,
            lastMealCount: null,
            lastError: null,
            lastUpdatedAt: null
        };

        function getSearchState() {
            const activeChip = document.querySelector('.chip.active');
            return {
                query: document.getElementById('searchInput').value,
                category: activeChip ? activeChip.dataset.filter : 'all',
                sort: document.getElementById('sortBy').value,
                minCal: document.getElementById('minCal').value,
                maxCal: document.getElementById('maxCal').value,
                minProtein: document.getElementById('minProtein').value,
                maxProtein: document.getElementById('maxProtein').value
            };
        }

        window.__searchDebug = {
            enabled: SEARCH_DEBUG_ENABLED,
            getSearchState,
            getRenderState: () => {
                const container = document.getElementById('results-container');
                const noResults = document.getElementById('no-results');
                const loadMoreContainer = document.getElementById('loadMoreContainer');
                return {
                    readyState: document.readyState,
                    hasGenerateMealCardHtml: typeof window.generateMealCardHtml === 'function',
                    dom: {
                        hasResultsContainer: !!container,
                        hasNoResults: !!noResults,
                        hasLoadMoreContainer: !!loadMoreContainer,
                        hasSearchInput: !!document.getElementById('searchInput'),
                        hasSortBy: !!document.getElementById('sortBy')
                    },
                    isBusy: container ? container.getAttribute('aria-busy') : null,
                    renderedCards: container ? container.querySelectorAll('.meal-card:not(.skeleton)').length : 0,
                    skeletonCards: container ? container.querySelectorAll('.meal-card.skeleton').length : 0,
                    noResultsVisible: !!(noResults && !noResults.classList.contains('hidden')),
                    loadMoreVisible: !!(loadMoreContainer && !loadMoreContainer.classList.contains('hidden')),
                    currentOffset,
                    canLoadMore,
                    isLoadingMore,
                    debugState: { ...searchDebugState }
                };
            },
            rerunSearch: () => handleSearch()
        };

        function saveSearchState() {
            try {
                localStorage.setItem(SEARCH_STATE_KEY, JSON.stringify(getSearchState()));
                searchDebug('Search state saved to localStorage', {
                    key: SEARCH_STATE_KEY,
                    state: getSearchState()
                });
            } catch (e) {
                console.warn('Could not persist search state:', e);
                searchDebug('Failed to save search state', { message: e.message });
            }
        }

        function applyActiveCategory(categoryValue = 'all') {
            const chips = document.querySelectorAll('.chip');
            let matched = false;

            chips.forEach(chip => {
                const isActive = chip.dataset.filter === String(categoryValue);
                chip.classList.toggle('active', isActive);
                chip.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                if (isActive) matched = true;
            });

            if (!matched) {
                const allChip = document.querySelector('.chip[data-filter="all"]');
                if (allChip) {
                    allChip.classList.add('active');
                    allChip.setAttribute('aria-pressed', 'true');
                }
            }
        }

        function restoreSearchState() {
            try {
                const raw = localStorage.getItem(SEARCH_STATE_KEY);
                if (!raw) {
                    searchDebug('No persisted search state found', { key: SEARCH_STATE_KEY });
                    return;
                }

                const state = JSON.parse(raw);
                document.getElementById('searchInput').value = state.query || '';
                document.getElementById('sortBy').value = state.sort || 'name';
                document.getElementById('minCal').value = state.minCal || '0';
                document.getElementById('maxCal').value = state.maxCal || '5000';
                document.getElementById('minProtein').value = state.minProtein || '0';
                document.getElementById('maxProtein').value = state.maxProtein || '200';
                applyActiveCategory(state.category || 'all');
                searchDebug('Search state restored from localStorage', {
                    key: SEARCH_STATE_KEY,
                    state
                });
            } catch (e) {
                console.warn('Could not restore search state:', e);
                searchDebug('Failed to restore search state', { message: e.message });
            }
        }

        function setActiveCategory(button) {
            if (!button) return;
            applyActiveCategory(button.dataset.filter || 'all');
            searchDebug('Active category changed', {
                category: button.dataset.filter || 'all'
            });
            saveSearchState();
            handleSearch();
        }

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
            document.getElementById('searchInput').value = '';
            applyActiveCategory('all');
            searchDebug('Filters reset to defaults');
            saveSearchState();
            handleSearch();
        }

        // Debounced search to prevent too many requests
        function debounceSearchHandle() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchDebug('Debounced search triggered', { query: document.getElementById('searchInput').value });
                handleSearch();
            }, 300);
        }

        async function handleSearch() {
            currentOffset = 0;
            const container = document.getElementById('results-container');
            if (!container) {
                searchDebug('handleSearch aborted: results container missing');
                return;
            }
            container.innerHTML = '';
               searchDebug('handleSearch invoked', {
                   resetOffsetTo: currentOffset,
                   state: getSearchState()
               });
            saveSearchState();
            performSearch();
        }

        async function performSearch(append = false) {
               const requestId = ++searchRequestSequence;
             searchDebugState.lastRequestId = requestId;
             searchDebugState.lastError = null;
             searchDebugState.lastUpdatedAt = new Date().toISOString();
            const query = document.getElementById('searchInput').value;
            const activeChip = document.querySelector('.chip.active');
            const category = activeChip && activeChip.dataset.filter !== 'all' ? activeChip.dataset.filter : '';
            const sort = document.getElementById('sortBy').value;
            const minCal = document.getElementById('minCal').value;
            const maxCal = document.getElementById('maxCal').value;
            const minProtein = document.getElementById('minProtein').value;
            const maxProtein = document.getElementById('maxProtein').value;
            
            // Keep active chip + persisted state synchronized
            applyActiveCategory(activeChip ? activeChip.dataset.filter : 'all');
            saveSearchState();

            const container = document.getElementById('results-container');
            const noResults = document.getElementById('no-results');
            const loadMoreContainer = document.getElementById('loadMoreContainer');

            if (!container || !noResults || !loadMoreContainer) {
                searchDebug('performSearch aborted: required DOM nodes missing', {
                    hasContainer: !!container,
                    hasNoResults: !!noResults,
                    hasLoadMoreContainer: !!loadMoreContainer
                });
                return;
            }

               searchDebug('performSearch started', {
                   requestId,
                   append,
                   filters: {
                       query,
                       category,
                       sort,
                       minCal,
                       maxCal,
                       minProtein,
                       maxProtein,
                       currentOffset
                   },
                   hasGenerateMealCardHtml: typeof window.generateMealCardHtml === 'function'
               });

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

                   searchDebug('Skeleton loaders rendered', {
                       requestId,
                       skeletonCount,
                       skeletonInDom: container.querySelectorAll('.meal-card.skeleton').length
                   });
            }

            let timeoutId = null;
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

                   const requestUrl = `/api/search_api.php?${params}`;
                   searchDebugState.lastRequestUrl = requestUrl;
                   searchDebug('Issuing search API request', {
                       requestId,
                       requestUrl,
                       timeoutMs: SEARCH_REQUEST_TIMEOUT_MS
                   });

                const controller = new AbortController();
                timeoutId = setTimeout(() => controller.abort(), SEARCH_REQUEST_TIMEOUT_MS);
                   const response = await fetch(requestUrl, {
                    signal: controller.signal
                });

                   const responseText = await response.text();
                   searchDebug('Search API response received', {
                       requestId,
                       status: response.status,
                       ok: response.ok,
                       rateLimitRemaining: response.headers.get('X-RateLimit-Remaining'),
                       rateLimitReset: response.headers.get('X-RateLimit-Reset'),
                       contentType: response.headers.get('content-type'),
                       bodyPreview: responseText.slice(0, 500)
                   });
                   searchDebugState.lastResponseStatus = response.status;
                   searchDebugState.lastResponseOk = response.ok;
                   searchDebugState.lastResponseContentType = response.headers.get('content-type');
                   searchDebugState.lastResponseBodyPreview = responseText.slice(0, 500);
                   searchDebugState.lastUpdatedAt = new Date().toISOString();

                   if (!response.ok) {
                       throw new Error(`Search API returned ${response.status}`);
                   }

                   let data;
                   try {
                       data = JSON.parse(responseText);
                   } catch (jsonError) {
                       searchDebug('Failed to parse search API JSON', {
                           requestId,
                           parseError: jsonError.message,
                           bodyPreview: responseText.slice(0, 500)
                       });
                       throw jsonError;
                   }

                container.setAttribute('aria-busy', 'false');
                   searchDebug('Search API JSON parsed', {
                       requestId,
                       success: data.success,
                       message: data.message || null,
                       mealCount: Array.isArray(data.meals) ? data.meals.length : 'non-array',
                       hasMore: data.has_more,
                       total: data.total,
                       offset: data.offset
                   });
                   searchDebugState.lastApiSuccessFlag = data.success;
                   searchDebugState.lastApiMessage = data.message || null;
                   searchDebugState.lastMealCount = Array.isArray(data.meals) ? data.meals.length : null;
                   searchDebugState.lastUpdatedAt = new Date().toISOString();

                if (data.meals && data.meals.length > 0) {
                    // Use unified generateMealCardHtml function from main.js
                    // If function not available, fall back to error message
                    const mealHtml = data.meals.map((meal, index) => {
                        if (typeof generateMealCardHtml !== 'function') {
                            console.error('generateMealCardHtml function not available');
                            return `<div class="meal-card">Error: generateMealCardHtml not available</div>`;
                        }
                        try {
                            const html = generateMealCardHtml(meal, { animation_delay: index });
                            if (!html || typeof html !== 'string') {
                                console.error('generateMealCardHtml returned invalid type:', typeof html, html);
                                   searchDebug('generateMealCardHtml returned invalid output', {
                                       requestId,
                                       index,
                                       returnedType: typeof html,
                                       meal
                                   });
                                return `<div class="meal-card">Error: Invalid return type</div>`;
                            }
                               if (index === 0) {
                                   searchDebug('First meal rendered to HTML', {
                                       requestId,
                                       meal,
                                       htmlLength: html.length,
                                       htmlPreview: html.slice(0, 250)
                                   });
                               }
                            return html;
                        } catch (err) {
                            console.error('Error in generateMealCardHtml:', err, meal);
                               searchDebug('Exception in generateMealCardHtml', {
                                   requestId,
                                   error: err.message,
                                   meal
                               });
                            return `<div class="meal-card">Error: ${err.message}</div>`;
                        }
                    }).join('');

                    if (mealHtml && mealHtml.length > 0) {
                        if (append) {
                            container.innerHTML += mealHtml;
                        } else {
                            container.innerHTML = mealHtml;
                        }
                           searchDebug('Meal HTML injected into DOM', {
                               requestId,
                               append,
                               htmlLength: mealHtml.length,
                               renderedCards: container.querySelectorAll('.meal-card:not(.skeleton)').length,
                               skeletonCards: container.querySelectorAll('.meal-card.skeleton').length
                           });
                    } else {
                        console.warn('mealHtml is empty');
                           searchDebug('mealHtml was empty after mapping meals', { requestId });
                    }

                    noResults.classList.add('hidden');
                    canLoadMore = data.has_more;
                    
                    if (canLoadMore) {
                        loadMoreContainer.classList.remove('hidden');
                    } else {
                        loadMoreContainer.classList.add('hidden');
                    }
                       searchDebug('Post-render visibility state (with meals)', {
                           requestId,
                           noResultsHidden: noResults.classList.contains('hidden'),
                           loadMoreHidden: loadMoreContainer.classList.contains('hidden'),
                           canLoadMore
                       });
                } else {
                    if (!append) {
                        container.innerHTML = '';
                        noResults.classList.remove('hidden');
                        loadMoreContainer.classList.add('hidden');
                    }
                       searchDebug('No meals returned; toggled no-results state', {
                           requestId,
                           append,
                           noResultsHidden: noResults.classList.contains('hidden'),
                           loadMoreHidden: loadMoreContainer.classList.contains('hidden')
                       });
                }
            } catch (error) {
                console.error('Search error:', error);
                   searchDebugState.lastError = `${error.name}: ${error.message}`;
                   searchDebugState.lastUpdatedAt = new Date().toISOString();
                   searchDebug('performSearch caught exception', {
                       requestId,
                       name: error.name,
                       message: error.message,
                       stack: error.stack ? error.stack.split('\n').slice(0, 4).join(' | ') : ''
                   });
                container.setAttribute('aria-busy', 'false');
                showToast('Search failed', 'error');
                if (!append) {
                    container.innerHTML = '';
                    noResults.classList.remove('hidden');
                }
            } finally {
                if (timeoutId) {
                    clearTimeout(timeoutId);
                }
                   searchDebug('performSearch completed', {
                       requestId,
                       finalBusy: container.getAttribute('aria-busy'),
                       renderedCards: container.querySelectorAll('.meal-card:not(.skeleton)').length,
                       skeletonCards: container.querySelectorAll('.meal-card.skeleton').length,
                       noResultsHidden: noResults.classList.contains('hidden')
                   });
            }
        }

        function loadMoreMeals() {
            if (isLoadingMore || !canLoadMore) return;
            isLoadingMore = true;
            searchDebug('loadMoreMeals triggered', {
                previousOffset: currentOffset,
                nextOffset: currentOffset + 12,
                canLoadMore
            });
            
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
        function initializeSearch() {
            if (searchInitialized) return;
            if (typeof window.generateMealCardHtml !== 'function') return;

            searchInitialized = true;
               searchDebugState.initialized = true;
               searchDebugState.initAttempts = attempts;
               searchDebugState.lastUpdatedAt = new Date().toISOString();
               searchDebug('initializeSearch running', {
                   readyState: document.readyState,
                   hasGenerateMealCardHtml: typeof window.generateMealCardHtml === 'function'
               });
            restoreSearchState();
            handleSearch();
        }
    </script>
</body>
</html>
