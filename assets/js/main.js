/* ========================================
   NUTRIPLAN - MAIN JAVASCRIPT
   All shared interactions and utilities
   ======================================== */

// ========================================
// SECTION 0: CSRF TOKEN HELPER
// ========================================

/**
 * Safely retrieve CSRF token from meta tag
 * @returns {string} CSRF token or empty string if not found
 */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

/**
 * Build a root-relative URL for API endpoints so fetch calls resolve
 * correctly on any server where the document root is the repo root.
 * Accepts paths with or without a leading slash and normalises them.
 *
 * @param {string} path - API path e.g. 'api/search_api.php'
 * @returns {string} Root-relative URL e.g. '/api/search_api.php'
 */
function apiUrl(path) {
    return '/' + path.replace(/^\/+/, '');
}

// ========================================
// SECTION 1: INITIALIZATION
// ========================================

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initMobileMenu();
    initIntersectionObserver();
    initFormValidation();
});

// ========================================
// SECTION 2: THEME TOGGLE
// ========================================

function initThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    const saved = localStorage.getItem('theme') || 'dark';
    
    html.setAttribute('data-theme', saved);
    updateThemeIcon(saved);
    
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const current = html.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            updateThemeIcon(next);
        });
    }
}

function updateThemeIcon(theme) {
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.textContent = theme === 'dark' ? '☀️ Light Mode' : '🌙 Dark Mode';
    }
}

// ========================================
// SECTION 3: MOBILE MENU
// ========================================

function initMobileMenu() {
    const hamburger = document.querySelector('.hamburger');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (hamburger && sidebar) {
        // Toggle menu: add/remove 'is-open' class on mobile
        const toggleMenu = () => {
            const isOpen = sidebar.classList.toggle('is-open');
            hamburger.classList.toggle('is-open', isOpen);
            
            if (overlay) overlay.classList.toggle('visible', isOpen);
            
            // Lock/unlock body scroll when menu is open
            if (isOpen) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        };
        
        // Hamburger button click to toggle
        hamburger.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleMenu();
        });

        // Close sidebar when clicking on overlay backdrop
        if (overlay) {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay && window.innerWidth <= 1023) {
                    sidebar.classList.remove('is-open');
                    hamburger.classList.remove('is-open');
                    overlay.classList.remove('visible');
                    document.body.style.overflow = '';
                }
            });
        }

        // Close sidebar when clicking outside (not on sidebar, not on hamburger, not on overlay)
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 1023) {
                const isClickOnSidebar = sidebar.contains(e.target);
                const isClickOnHamburger = hamburger.contains(e.target);
                const isClickOnOverlay = overlay && overlay.contains(e.target);
                
                if (!isClickOnSidebar && !isClickOnHamburger && !isClickOnOverlay) {
                    if (sidebar.classList.contains('is-open')) {
                        sidebar.classList.remove('is-open');
                        hamburger.classList.remove('is-open');
                        if (overlay) overlay.classList.remove('visible');
                        document.body.style.overflow = '';
                    }
                }
            }
        });

        // Close sidebar when a nav item is clicked
        document.querySelectorAll('.nav-item a').forEach(item => {
            item.addEventListener('click', (e) => {
                if (window.innerWidth <= 1023) {
                    e.stopPropagation();
                    sidebar.classList.remove('is-open');
                    hamburger.classList.remove('is-open');
                    if (overlay) overlay.classList.remove('visible');
                    document.body.style.overflow = '';
                }
            });
        });
        
        // Close sidebar on ESC key press
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && window.innerWidth <= 1023) {
                if (sidebar.classList.contains('is-open')) {
                    sidebar.classList.remove('is-open');
                    hamburger.classList.remove('is-open');
                    if (overlay) overlay.classList.remove('visible');
                    document.body.style.overflow = '';
                }
            }
        });
        
        // Close sidebar when window is resized to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1023) {
                sidebar.classList.remove('is-open');
                hamburger.classList.remove('is-open');
                if (overlay) overlay.classList.remove('visible');
                document.body.style.overflow = '';
            }
        });
    }
}

// ========================================
// SECTION 4: TOAST NOTIFICATIONS
// ========================================

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const icon = {
        'success': '✓',
        'error': '✕',
        'warning': '⚠',
        'info': 'ℹ'
    }[type] || '✓';
    
    toast.innerHTML = `
        <span class="toast-icon">${icon}</span>
        <span class="toast-msg">${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    // Remove after animation
    setTimeout(() => {
        toast.remove();
    }, 3500);
}

// Make showToast globally available for other scripts
window.showToast = showToast;

// ========================================
// SECTION 5: HTML ESCAPING UTILITY
// ========================================

function escapeHtml(unsafe) {
    if (typeof unsafe !== 'string') return '';
    const div = document.createElement('div');
    div.textContent = unsafe;
    return div.innerHTML;
}

// Make escapeHtml globally available for other scripts
window.escapeHtml = escapeHtml;

/**
 * Normalize meal icon slugs to emoji based on category
 * @param {string} icon - Icon slug (e.g., 'pot-of-food', 'cooking')
 * @param {string} categoryName - Meal category name (e.g., 'Breakfast', 'Lunch')
 * @returns {string} Emoji icon or original icon if already emoji
 */
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

// Make normalizeMealIcon globally available
window.normalizeMealIcon = normalizeMealIcon;

/**
 * Generate unified meal card HTML structure (client-side)
 * Mirror of PHP generate_meal_card_html() for consistent rendering
 * Used by search.php, dashboard.php, and other pages
 * 
 * @param {Object} meal Meal data with keys: meal_id, meal_name, meal_icon, category_name, calories, proteins_g
 * @param {Object} options Optional: animation_delay (0-based index), card_accent_override (CSS color)
 * @returns {string} HTML markup for meal card
 */
function generateMealCardHtml(meal, options = {}) {
    // Extract and sanitize meal data
    const meal_id = parseInt(meal.meal_id) || 0;
    const meal_name = escapeHtml(meal.meal_name || '');
    const category_name = escapeHtml(meal.category_name || '');
    const calories = parseInt(meal.calories) || 0;
    const protein = parseInt(meal.proteins_g) || 0;
    
    // Determine icon display
    const meal_icon = normalizeMealIcon(meal.meal_icon, category_name);
    
    // Determine nutrition level and default color
    let nutrition_level = '';
    let nutrition_color = 'var(--primary)';
    
    if (protein > 25) {
        nutrition_level = 'High Protein';
        nutrition_color = 'var(--accent)';
    } else if (calories < 300) {
        nutrition_level = 'Low Cal';
        nutrition_color = 'var(--success)';
    } else if (calories > 700) {
        nutrition_level = 'Hearty';
        nutrition_color = 'var(--warning)';
    }
    
    // Override nutrition color if specified
    if (options.card_accent_override) {
        nutrition_color = options.card_accent_override;
    }
    
    // Build animation delay style
    const animation_style = (options.animation_delay !== undefined) 
        ? `animation-delay: ${options.animation_delay * 60}ms` 
        : '';
    
    // Build nutrition level badge HTML
    const nutritionBadgeHtml = nutrition_level 
        ? `<div class="nutrition-badge badge-level" style="--badge-color: ${nutrition_color};" title="${escapeHtml(nutrition_level)}">
                        ✨ ${escapeHtml(nutrition_level)}
                    </div>`
        : '';
    
    // Build and return the complete HTML
    return `<article class="meal-card stagger-item" style="--card-accent: ${nutrition_color}; ${animation_style}">
    <div class="card-accent-strip"></div>
    <div class="card-body">
        <div style="display: flex; gap: var(--sp-3); width: 100%;">
            <div class="card-icon" aria-hidden="true">${meal_icon}</div>
            <div style="flex: 1; min-width: 0;">
                <div class="card-title">${meal_name}</div>
                <span class="card-category">${category_name}</span>
                <div class="card-badges">
                    <div class="nutrition-badge badge-primary" title="${calories} calories">
                        🔥 ${calories} cal
                    </div>
                    <div class="nutrition-badge badge-accent" title="${protein}g protein">
                        💪 ${protein}g
                    </div>
                    ${nutritionBadgeHtml}
                </div>
            </div>
        </div>
    </div>
    <div class="card-actions">
        <button class="btn btn-ghost btn-sm" onclick="addToShoppingList(${meal_id})" aria-label="Add ${meal_name} to shopping list">+ Add</button>
        <a href="meal.php?id=${meal_id}" class="btn btn-outline btn-sm">Details →</a>
    </div>
</article>`;
}

// Make generateMealCardHtml globally available for search and other pages
window.generateMealCardHtml = generateMealCardHtml;

// ========================================
// SECTION 6: MODAL MANAGEMENT
// ========================================

function openModal(modalId) {
    const backdrop = document.querySelector('.modal-backdrop');
    const modal = document.getElementById(modalId);
    
    if (modal) {
        modal.classList.add('open');
        if (backdrop) backdrop.classList.add('open');
    }
}

function closeModal(modalId) {
    const backdrop = document.querySelector('.modal-backdrop');
    const modal = document.getElementById(modalId);
    
    if (modal) {
        modal.classList.remove('open');
        if (backdrop) backdrop.classList.remove('open');
    }
}

// Close modal when clicking outside
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.classList.remove('open');
        document.querySelectorAll('.modal').forEach(m => m.classList.remove('open'));
    }
});

// Close modal with close button
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-close')) {
        const modal = e.target.closest('.modal');
        const backdrop = document.querySelector('.modal-backdrop');
        if (modal) modal.classList.remove('open');
        if (backdrop) backdrop.classList.remove('open');
    }
});

// Close modal on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.open').forEach(m => m.classList.remove('open'));
        document.querySelector('.modal-backdrop.open')?.classList.remove('open');
    }
});

// ========================================
// SECTION 6: FORM VALIDATION
// ========================================

function initFormValidation() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const fields = form.querySelectorAll('[data-validate]');
    
    fields.forEach(field => {
        const rules = field.dataset.validate.split('|');
        const value = field.value.trim();
        
        let fieldValid = true;
        
        for (const rule of rules) {
            if (rule === 'required' && !value) {
                fieldValid = false;
                break;
            }
            if (rule === 'email' && !validateEmail(value)) {
                fieldValid = false;
                break;
            }
            if (rule.startsWith('minlength:')) {
                const len = parseInt(rule.split(':')[1]);
                if (value.length < len) {
                    fieldValid = false;
                    break;
                }
            }
            if (rule === 'password' && !validatePassword(value)) {
                fieldValid = false;
                break;
            }
        }
        
        if (fieldValid) {
            field.parentElement.classList.remove('field-error');
        } else {
            field.parentElement.classList.add('field-error');
            isValid = false;
        }
    });
    
    return isValid;
}

function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function validatePassword(password) {
    // At least 8 chars, 1 uppercase, 1 number
    return password.length >= 8 && /[A-Z]/.test(password) && /[0-9]/.test(password);
}

// ========================================
// SECTION 7: PASSWORD STRENGTH METER
// ========================================

function initPasswordStrength() {
    const passwordFields = document.querySelectorAll('input[type="password"][data-strength]');
    
    passwordFields.forEach(field => {
        field.addEventListener('keyup', () => {
            updatePasswordStrength(field);
        });
    });
}

function updatePasswordStrength(field) {
    const strength = getPasswordStrength(field.value);
    const meter = field.parentElement.querySelector('.strength-meter');
    const text = field.parentElement.querySelector('.strength-text');
    
    if (!meter || !text) return;
    
    const bar = meter.querySelector('.strength-bar');
    const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    const colors = ['', 'var(--danger)', 'var(--warning)', 'var(--primary)', 'var(--success)'];
    
    bar.style.width = (strength / 4) * 100 + '%';
    bar.style.backgroundColor = colors[strength];
    text.textContent = labels[strength];
}

function getPasswordStrength(password) {
    let score = 0;
    if (password.length >= 8) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    return score;
}

// ========================================
// SECTION 8: SEARCH DEBOUNCE
// ========================================

// searchTimeout is declared globally to avoid conflicts with search.php
if (!window.searchTimeout) {
    window.searchTimeout = null;
}

function debounceSearch(query, callback) {
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(() => {
        callback(query);
    }, 300);
}

// ========================================
// SECTION 9: FILTER CHIPS
// ========================================

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('chip')) {
        e.target.classList.toggle('active');
        
        // Trigger search/filter if needed
        const filter = e.target.dataset.filter;
        if (filter && window.onFilterChange) {
            window.onFilterChange(filter);
        }
    }
});

// ========================================
// SECTION 10: SHOPPING LIST ACTIONS
// ========================================

/**
 * CART FUNCTIONS (renamed from shopping)
 */

/**
 * Enhanced addToCart with visual feedback and toast notifications
 * Changes button state: '+ Add to Cart' → '✓ In Cart' with success styling
 */
async function addToCart(mealId) {
    try {
        showLoader(true);
        const csrf = getCsrfToken();
        const btn = document.querySelector(`[onclick*="addToCart(${mealId})"]`);
        if (btn) btn.disabled = true;
        
        const response = await fetch(apiUrl('api/cart_action.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `action=add_to_cart&meal_id=${mealId}&csrf_token=${encodeURIComponent(csrf)}`
        });
        
        const contentType = response.headers.get('content-type') || '';
        let result;
        
        if (contentType.includes('application/json')) {
            try {
                result = await response.json();
            } catch (err) {
                console.error('Failed to parse JSON:', err);
                showToast('🔴 Server error: invalid response', 'error');
                return;
            }
        } else {
            showToast('🔴 Server error: expected JSON response', 'error');
            return;
        }
        
        if (result.success) {
            // Update button styling with active state
            if (btn) {
                btn.classList.add('in-cart');
                btn.textContent = '✓ In Cart';
                // Disable after adding to prevent multiple clicks
                setTimeout(() => { btn.disabled = true; }, 100);
            }
            showToast('✓ Added to cart! 🛒', 'success');
            // Haptic feedback
            if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
        } else {
            showToast('🔴 Error: ' + (result.message || 'Could not add to cart'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('🔴 Error adding to cart', 'error');
    } finally {
        const btn = document.querySelector(`[onclick*="addToCart(${mealId})"]`);
        if (btn) btn.disabled = false;
        showLoader(false);
    }
}

async function toggleCartItem(itemId) {
    try {
        showLoader(true);
        const csrf = getCsrfToken();
        const response = await fetch(apiUrl('api/cart_action.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `action=toggle_cart_item&item_id=${itemId}&csrf_token=${encodeURIComponent(csrf)}`
        });
        
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            showToast('Server error: expected JSON response', 'error');
            return;
        }
        
        let result;
        try {
            result = await response.json();
        } catch (err) {
            console.error('Failed to parse JSON:', err);
            showToast('Server error: invalid response', 'error');
            return;
        }
        
        if (result.success) {
            // Update UI
            const itemElement = document.querySelector(`[data-item-id="${itemId}"]`);
            if (itemElement) {
                itemElement.classList.toggle('checked');
                const checkbox = itemElement.querySelector('.list-checkbox');
                checkbox.classList.toggle('checked');
            }
            updateProgressBar();
            // Haptic feedback
            if (navigator.vibrate) navigator.vibrate(100);
        }
    } catch (error) {
        console.error('Error:', error);
    } finally {
        showLoader(false);
    }
}

async function removeFromCart(itemId) {
    if (confirm('Remove this item from cart?')) {
        try {
            showLoader(true);
            const csrf = getCsrfToken();
            const response = await fetch(apiUrl('api/cart_action.php'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=remove_from_cart&item_id=${itemId}&csrf_token=${encodeURIComponent(csrf)}`
            });
            
            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                showToast('Server error: expected JSON response', 'error');
                return;
            }
            
            let result;
            try {
                result = await response.json();
            } catch (err) {
                console.error('Failed to parse JSON:', err);
                showToast('Server error: invalid response', 'error');
                return;
            }
            
            if (result.success) {
                const itemElement = document.querySelector(`[data-item-id="${itemId}"]`);
                itemElement.remove();
                updateProgressBar();
                showToast('Removed from cart', 'success');
            }
        } catch (error) {
            console.error('Error:', error);
        } finally {
            showLoader(false);
        }
    }
}

// BACKWARD COMPATIBILITY: Keep old function names
async function addToShoppingList(mealId) {
    return addToCart(mealId);
}

async function toggleShoppingItem(itemId) {
    return toggleCartItem(itemId);
}

async function deleteShoppingItem(itemId) {
    return removeFromCart(itemId);
}

/**
 * WISHLIST FUNCTIONS (renamed from favorites)
 */

/**
 * Enhanced toggleWishlist with improved visual feedback
 * Changes button: '🤍 Add to Wishlist' ↔ '❤️ Wishlisted' with CSS active state
 */
async function toggleWishlist(mealId) {
    try {
        showLoader(true);
        const csrf = getCsrfToken();
        const btn = document.querySelector(`[data-wishlist-btn="${mealId}"]`);
        if (btn) btn.disabled = true;
        
        const response = await fetch(apiUrl('api/wishlist_api.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `action=toggle_wishlist&meal_id=${mealId}&csrf_token=${encodeURIComponent(csrf)}`
        });
        
        const contentType = response.headers.get('content-type') || '';
        const result = contentType.includes('application/json') ? await response.json() : {};
        
        if (result.success) {
            if (btn) {
                btn.classList.toggle('wishlisted');
                if (result.is_wishlisted) {
                    btn.textContent = '❤️ Wishlisted';
                    btn.style.borderColor = 'var(--warning)';
                    showToast('❤️ Added to wishlist!', 'success');
                } else {
                    btn.textContent = '🤍 Add to Wishlist';
                    btn.style.borderColor = 'var(--border)';
                    showToast('💔 Removed from wishlist', 'info');
                }
            }
            // Haptic feedback
            if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
        } else {
            showToast('🔴 Error: ' + (result.message || 'Could not update wishlist'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('🔴 Error updating wishlist', 'error');
    } finally {
        const btn = document.querySelector(`[data-wishlist-btn="${mealId}"]`);
        if (btn) btn.disabled = false;
        showLoader(false);
    }
}

// BACKWARD COMPATIBILITY
async function toggleFavorite(mealId) {
    return toggleWishlist(mealId);
}

/**
 * Remove item from cart with visual feedback
 */
async function removeFromCart(itemId, mealId = null) {
    try {
        showLoader(true);
        const csrf = getCsrfToken();
        const btn = mealId ? document.querySelector(`[onclick*="addToCart(${mealId})"]`) : null;
        if (btn) btn.disabled = true;
        
        const response = await fetch(apiUrl('api/cart_action.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `action=remove_from_cart&item_id=${itemId}&csrf_token=${encodeURIComponent(csrf)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (btn) {
                btn.classList.remove('in-cart');
                btn.textContent = '+ Add to Cart';
                btn.disabled = false;
            }
            showToast('✓ Removed from cart', 'info');
            if (navigator.vibrate) navigator.vibrate([100]);
            
            if (window.location.pathname.includes('shopping.php')) {
                setTimeout(() => location.reload(), 600);
            }
        } else {
            showToast('🔴 Error: ' + (result.message || 'Could not remove from cart'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('🔴 Error removing from cart', 'error');
    } finally {
        showLoader(false);
    }
}

/**
 * Check if a meal is already in the user's cart
 */
async function getCartStatus(mealId) {
    try {
        const csrf = getCsrfToken();
        const response = await fetch(apiUrl('api/cart_action.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `action=check_in_cart&meal_id=${mealId}&csrf_token=${encodeURIComponent(csrf)}`
        });
        
        const result = await response.json();
        return result.success && result.in_cart ? result : null;
    } catch (error) {
        console.error('Error checking cart status:', error);
        return null;
    }
}

/**
 * Initialize cart button state on page load for visual feedback
 */
async function initCartButtonState() {
    const btn = document.querySelector(`button[onclick*="addToCart"]`);
    if (!btn) return;
    
    const onclickAttr = btn.getAttribute('onclick') || '';
    const mealIdMatch = onclickAttr.match(/addToCart\((\d+)\)/);
    if (!mealIdMatch) return;
    
    const mealId = parseInt(mealIdMatch[1]);
    const status = await getCartStatus(mealId);
    
    if (status && status.in_cart) {
        btn.classList.add('in-cart');
        btn.textContent = '✓ In Cart';
        btn.disabled = true;
    }
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCartButtonState);
} else {
    initCartButtonState();
}




function updateProgressBar() {
    const items = document.querySelectorAll('[data-item-id]');
    const checked = document.querySelectorAll('[data-item-id].checked');
    const progress = Math.round((checked.length / items.length) * 100);
    
    const progressBar = document.querySelector('.progress-bar');
    const progressText = document.querySelector('.progress-text');
    
    if (progressBar) {
        progressBar.style.width = progress + '%';
    }
    if (progressText) {
        progressText.textContent = `${checked.length} of ${items.length}`;
    }
}

// ========================================
// SECTION 11: INTERSECTION OBSERVER
// ========================================

function initIntersectionObserver() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                
                // Stagger items
                entry.target.querySelectorAll('.stagger-item').forEach((item, index) => {
                    item.style.animationDelay = (index * 60) + 'ms';
                });
                
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.stagger-container').forEach(c => {
        observer.observe(c);
    });
}

// ========================================
// SECTION 12: AVATAR UPLOAD
// ========================================

async function handleAvatarUpload(file) {
    if (!file) return;
    
    // Preview
    const reader = new FileReader();
    reader.onload = (e) => {
        const avatar = document.querySelector('.profile-avatar');
        if (avatar) {
            avatar.style.backgroundImage = `url('${e.target.result}')`;
        }
    };
    reader.readAsDataURL(file);
    
    // Upload
    const formData = new FormData();
    formData.append('avatar', file);
    
    try {
        const response = await fetch(apiUrl('api/upload_avatar.php'), {
            method: 'POST',
            body: formData
        });
        
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            showToast('Server error: expected JSON response', 'error');
            return;
        }
        
        let result;
        try {
            result = await response.json();
        } catch (err) {
            console.error('Failed to parse JSON:', err);
            showToast('Server error: invalid response', 'error');
            return;
        }
        
        if (result.success) {
            showToast('Avatar updated!', 'success');
        } else {
            showToast('Upload failed', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Upload error', 'error');
    }
}

// ========================================
// SECTION 13: USERNAME AVAILABILITY CHECK
// ========================================

let usernameCheckTimeout;

async function checkUsernameAvailability(username) {
    if (username.length < 3) {
        clearUsernameStatus();
        return;
    }
    
    clearTimeout(usernameCheckTimeout);
    usernameCheckTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`${apiUrl('api/check_username.php')}?username=${username}`);
            
            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                clearUsernameStatus();
                return;
            }
            
            let result;
            try {
                result = await response.json();
            } catch (err) {
                console.error('Failed to parse JSON:', err);
                clearUsernameStatus();
                return;
            }
            
            const statusEl = document.querySelector('.username-status');
            if (statusEl) {
                if (result.available) {
                    statusEl.textContent = '✓ Available';
                    statusEl.style.color = 'var(--success)';
                } else {
                    statusEl.textContent = '✕ Taken';
                    statusEl.style.color = 'var(--danger)';
                }
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }, 500);
}

function clearUsernameStatus() {
    const statusEl = document.querySelector('.username-status');
    if (statusEl) {
        statusEl.textContent = '';
    }
}

// ========================================
// SECTION 14: TAB SWITCHING
// ========================================

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('tab-btn')) {
        const tabGroup = e.target.parentElement;
        const tabId = e.target.dataset.tab;
        
        // Remove active from all buttons and panels
        tabGroup.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-selected', 'false');
        });
        document.querySelectorAll('.tab-panel').forEach(panel => {
            panel.classList.remove('active');
            panel.classList.add('hidden');
        });
        
        // Add active to clicked button and matching panel
        e.target.classList.add('active');
        e.target.setAttribute('aria-selected', 'true');
        
        // Find the panel by matching the aria-controls or ID pattern
        const panelId = e.target.getAttribute('aria-controls') || (tabId + '-panel');
        const panel = document.getElementById(panelId);
        if (panel) {
            panel.classList.add('active');
            panel.classList.remove('hidden');
        }
        
        e.target.focus();
    }
});

// ========================================
// SECTION 15: PAGE ENTER ANIMATION
// ========================================

function animatePageEnter() {
    const main = document.querySelector('.main');
    if (main) {
        main.classList.add('page-enter');
    }
}

// Initialize page animation on page load
window.addEventListener('load', animatePageEnter);

// ========================================
// SECTION 16: SERVICE WORKER REGISTRATION (dev toggle)
// ========================================

if ('serviceWorker' in navigator) {
    // Skip registration on common local dev hosts to avoid caching interference
    const hostname = window.location.hostname;
    const skipLocal = (hostname === 'localhost' || hostname === '127.0.0.1');
    if (!skipLocal) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('Service Worker registered:', registration);
                })
                .catch(error => {
                    console.warn('Service Worker registration failed:', error);
                });
        });
    } else {
        console.log('Skipping Service Worker registration on local dev host:', hostname);
        // Cleanup old registrations/caches that may still control localhost
        window.addEventListener('load', async () => {
            try {
                const registrations = await navigator.serviceWorker.getRegistrations();
                await Promise.all(registrations.map((registration) => registration.unregister()));

                if (window.caches && typeof window.caches.keys === 'function') {
                    const cacheKeys = await window.caches.keys();
                    await Promise.all(
                        cacheKeys
                            .filter((key) => key.startsWith('nutriplan-'))
                            .map((key) => window.caches.delete(key))
                    );
                }

                console.log('Local dev SW/cache cleanup complete');
            } catch (error) {
                console.warn('Local dev SW/cache cleanup failed:', error);
            }
        });
    }
}

// ========================================
// SECTION 17: PWA INSTALL PROMPT
// ========================================

let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    const installBtn = document.getElementById('install-btn');
    if (installBtn) {
        installBtn.style.display = 'block';
    }
});

function installApp() {
    if (!deferredPrompt) return;
    
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then((choiceResult) => {
        if (choiceResult.outcome === 'accepted') {
            showToast('App installed!', 'success');
        }
        deferredPrompt = null;
    });
}

// ========================================
// SECTION 18: NUTRITION RING
// ========================================

function initNutritionRing(protein, carbs, fat) {
    const svg = document.querySelector('.nutrition-ring');
    if (!svg) return;
    
    const r = 50; // radius
    const circumference = 2 * Math.PI * r;
    const total = protein + carbs + fat;
    
    // Calculate dash arrays
    const proteinDash = (protein / total) * circumference;
    const carbsDash = (carbs / total) * circumference;
    const fatDash = (fat / total) * circumference;
    
    svg.style.setProperty('--protein-dash', proteinDash);
    svg.style.setProperty('--carbs-dash', carbsDash);
    svg.style.setProperty('--fat-dash', fatDash);
}

// ========================================
// SECTION 19: NAVBAR GLASS EFFECT
// ========================================

window.addEventListener('scroll', () => {
    const nav = document.querySelector('nav');
    if (nav) {
        if (window.scrollY > 50) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    }
});

// ========================================
// SECTION 20: STATS COUNTER ANIMATION
// ========================================

function animateCounters() {
    const counters = document.querySelectorAll('[data-count]');
    if (!counters.length) return;
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = entry.target;
                const count = parseInt(target.dataset.count);
                let current = 0;
                
                const increment = Math.ceil(count / 50);
                const updateCount = setInterval(() => {
                    current += increment;
                    if (current >= count) {
                        current = count;
                        clearInterval(updateCount);
                    }
                    target.textContent = current;
                }, 20);
                
                observer.unobserve(target);
            }
        });
    }, { threshold: 0.1 });
    
    counters.forEach(counter => observer.observe(counter));
}

let countersInitialized = false;

function initCountersWhenReady() {
    if (countersInitialized) return;
    const counters = document.querySelectorAll('[data-count]');
    if (!counters.length) return;

    countersInitialized = true;
    window.requestAnimationFrame(() => {
        setTimeout(() => {
            animateCounters();
        }, 120);
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    initPasswordStrength();
    initCountersWhenReady();
});

window.addEventListener('load', () => {
    initCountersWhenReady();
});

// ========================================
// SECTION 21: CUSTOM FORM SUBMIT HANDLERS
// ========================================

document.addEventListener('submit', async (e) => {
    if (e.target.classList.contains('ajax-form')) {
        e.preventDefault();

        const form = e.target;
        const button = form.querySelector('button[type="submit"]');
        const originalText = button ? button.textContent : '';

        if (button) {
            button.disabled = true;
            button.textContent = 'Loading...';
        }

        // Resolve a safe, absolute URL for the POST
        const targetUrl = new URL(form.action || window.location.pathname, window.location.origin).href;

        try {
            const response = await fetch(targetUrl, {
                method: form.method || 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: new FormData(form)
            });

            const contentType = response.headers.get('content-type') || '';

            // If response is JSON, parse it. Otherwise, fallback to navigation behavior.
            if (contentType.includes('application/json')) {
                let result;
                try {
                    result = await response.json();
                } catch (err) {
                    console.error('Failed to parse JSON response:', err);
                    // Fallback: navigate to the form action or current page
                    window.location.href = targetUrl;
                    return;
                }

                if (result.success) {
                    showToast(result.message || 'Success!', 'success');
                    if (result.redirect) {
                        // Always resolve redirect relative to current path if not absolute
                        let redirectUrl = result.redirect;
                        if (!/^https?:\/\//.test(redirectUrl) && !redirectUrl.startsWith('/')) {
                            // Relative path, resolve against current path
                            const base = window.location.pathname.endsWith('/') ? window.location.pathname : window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
                            redirectUrl = base + redirectUrl;
                        } else if (redirectUrl.startsWith('/')) {
                            // Root-relative, add origin
                            redirectUrl = window.location.origin + redirectUrl;
                        }
                        setTimeout(() => { window.location.href = redirectUrl; }, 500);
                    }
                } else {
                    showToast(result.message || 'Error', 'error');
                }
            } else {
                // Non-JSON response (could be a redirect). If fetch followed a redirect, go there.
                if (response.redirected && response.url) {
                    window.location.href = response.url;
                } else {
                    // Fallback navigation to form action
                    window.location.href = targetUrl;
                }
            }
        } catch (error) {
            console.error('Error during AJAX submit:', error);
            // On network error, perform a normal navigation to attempt server-side handling
            window.location.href = targetUrl;
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = originalText;
            }
        }
    }
});

// ========================================
// SECTION 22: LOADER
// ========================================

function showLoader(show = true) {
    let loader = document.querySelector('.loader');
    if (!loader) {
        loader = document.createElement('div');
        loader.className = 'loader flex-center';
        loader.innerHTML = '<div class="spinner"></div>';
        document.body.appendChild(loader);
    }
    
    loader.style.display = show ? 'flex' : 'none';
}

// ========================================
// SECTION 23: HAPTIC FEEDBACK
// ========================================

function triggerHaptic(intensity = 'light') {
    if (!navigator.vibrate) return;
    
    const patterns = {
        'light': 10,
        'medium': 20,
        'heavy': [20, 10, 20]
    };
    
    const pattern = patterns[intensity] || patterns.light;
    navigator.vibrate(pattern);
}

// Add haptic feedback to buttons and interactive elements
document.addEventListener('click', (e) => {
    if (e.target.closest('button') || e.target.closest('.btn') || e.target.closest('.chip') || e.target.closest('.tab-btn')) {
        triggerHaptic('light');
    }
});

// Haptic on checkbox toggle
document.addEventListener('change', (e) => {
    if (e.target.type === 'checkbox') {
        triggerHaptic('light');
    }
});

// ========================================
// SECTION 24: SWIPE-TO-DELETE SUPPORT (Enhanced)
// ========================================

function initSwipeToDelete() {
    const listItems = document.querySelectorAll('.list-item-layout');
    
    listItems.forEach(item => {
        let touchStartX = 0;
        let touchEndX = 0;
        let isBeingDeleted = false;
        
        item.addEventListener('touchstart', (e) => {
            if (isBeingDeleted) return;
            touchStartX = e.changedTouches[0].screenX;
        }, false);
        
        item.addEventListener('touchmove', (e) => {
            if (isBeingDeleted) return;
            const currentX = e.changedTouches[0].screenX;
            const distance = touchStartX - currentX;
            
            // Show visual feedback during drag - fade out as user swipes left
            if (distance > 20) {
                const opacity = Math.max(0.3, 1 - (distance / 300));
                item.style.opacity = opacity;
                item.style.transform = `translateX(-${Math.min(distance, 100)}px)`;
                
                // Show delete hint more prominently during swipe
                const hint = item.querySelector('.swipe-delete-hint');
                if (hint && distance > 50) {
                    hint.style.opacity = Math.min(1, (distance - 50) / 50);
                }
            }
        }, false);
        
        item.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            const swipeDistance = touchStartX - touchEndX;
            
            // Reset visual state
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
            const hint = item.querySelector('.swipe-delete-hint');
            if (hint) hint.style.opacity = '0';
            
            // Swipe left to delete (> 80px swipe)
            if (swipeDistance > 80) {
                const deleteBtn = item.querySelector('.list-item-delete');
                if (deleteBtn) {
                    triggerHaptic('medium');
                    deleteBtn.click();
                    isBeingDeleted = true;
                }
            }
        }, false);
        
        // Add touch-action to prevent default scrolling conflicts
        item.style.touchAction = 'pan-y';
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    initSwipeToDelete();
});

// ========================================
// SECTION 25: ENHANCED PASSWORD STRENGTH
// ========================================

// Override the updatePasswordStrength to add color classes
const originalUpdatePasswordStrength = updatePasswordStrength;
function updatePasswordStrength(field) {
    const strength = getPasswordStrength(field.value);
    const text = field.parentElement.querySelector('.strength-text');
    
    if (text) {
        text.classList.remove('has-weak', 'has-fair', 'has-good', 'has-strong');
        if (strength === 1) text.classList.add('has-weak');
        else if (strength === 2) text.classList.add('has-fair');
        else if (strength === 3) text.classList.add('has-good');
        else if (strength === 4) text.classList.add('has-strong');
    }
    
    // Call original implementation
    originalUpdatePasswordStrength(field);
}

// ========================================
// SECTION 26: IMPROVED TAB SWITCHING FOR MOBILE
// ========================================

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('tab-btn')) {
        const tabGroup = e.target.parentElement;
        const tabId = e.target.dataset.tab;
        
        // Add haptic feedback
        triggerHaptic('light');
        
        // Remove active from all buttons and panels
        tabGroup.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-selected', 'false');
        });
        document.querySelectorAll('.tab-panel').forEach(panel => {
            panel.classList.remove('active');
            panel.classList.add('hidden');
        });
        
        // Add active to clicked button and matching panel
        e.target.classList.add('active');
        e.target.setAttribute('aria-selected', 'true');
        
        // Find the panel by matching the aria-controls or ID pattern
        const panelId = e.target.getAttribute('aria-controls') || (tabId + '-panel');
        const panel = document.getElementById(panelId);
        if (panel) {
            panel.classList.add('active');
            panel.classList.remove('hidden');
            
            // Smooth scroll panel into view on mobile
            if (window.innerWidth <= 768) {
                setTimeout(() => {
                    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            }
        }
        
        e.target.focus();
    }
});

// ========================================
// SECTION 27: ENHANCED DARK MODE TOGGLE
// ========================================

// Ensure theme toggle is always visible by adding it if missing
if (!document.getElementById('themeToggle') && !document.querySelector('.theme-toggle')) {
    setTimeout(() => {
        if (!document.getElementById('themeToggle') && !document.querySelector('.theme-toggle')) {
            const themeToggle = document.createElement('button');
            themeToggle.id = 'themeToggle';
            themeToggle.className = 'theme-toggle';
            themeToggle.setAttribute('aria-label', 'Toggle dark/light mode');
            themeToggle.setAttribute('title', 'Toggle dark/light mode');
            const html = document.documentElement;
            const savedTheme = localStorage.getItem('theme') || 'dark';
            themeToggle.textContent = savedTheme === 'dark' ? '☀️ Light' : '🌙 Dark';
            document.body.appendChild(themeToggle);
            
            themeToggle.addEventListener('click', () => {
                const current = html.getAttribute('data-theme');
                const next = current === 'dark' ? 'light' : 'dark';
                
                html.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
                themeToggle.textContent = next === 'dark' ? '☀️ Light' : '🌙 Dark';
                
                // Haptic feedback for theme toggle
                triggerHaptic('medium');
            });
        }
    }, 500);
}

// ========================================
// SECTION 28: STICKY CATEGORY HEADERS
// ========================================

function initStickyCategoryHeaders() {
    const categoryHeaders = document.querySelectorAll('h3[style*="margin-bottom"]');
    
    categoryHeaders.forEach((header) => {
        // Check if this is a category header (in shopping list context)
        if (header.textContent && header.parentElement && header.parentElement.querySelector('ul')) {
            header.classList.add('category-header');
        }
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    initStickyCategoryHeaders();
});

// ========================================
// SECTION 29: TAB NAVIGATION WITH KEYBOARD SUPPORT
// ========================================

function initProfileTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn[role="tab"]');
    if (!tabButtons.length) return;
    
    tabButtons.forEach((btn, index) => {
        // Click handler
        btn.addEventListener('click', () => {
            switchTab(btn);
        });
        
        // Keyboard navigation: arrow keys
        btn.addEventListener('keydown', (e) => {
            let nextBtn = null;
            if (e.key === 'ArrowRight') {
                nextBtn = tabButtons[(index + 1) % tabButtons.length];
            } else if (e.key === 'ArrowLeft') {
                nextBtn = tabButtons[(index - 1 + tabButtons.length) % tabButtons.length];
            } else if (e.key === 'Home') {
                nextBtn = tabButtons[0];
            } else if (e.key === 'End') {
                nextBtn = tabButtons[tabButtons.length - 1];
            }
            
            if (nextBtn) {
                e.preventDefault();
                switchTab(nextBtn);
                nextBtn.focus();
            }
        });
    });
}

function switchTab(tabBtn) {
    // Deactivate all tabs and panels
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.setAttribute('aria-selected', 'false');
    });
    document.querySelectorAll('[role="tabpanel"]').forEach(panel => {
        panel.classList.add('hidden');
        panel.classList.remove('active');
    });
    
    // Activate selected tab
    const tabId = tabBtn.getAttribute('data-tab');
    const panelId = `${tabId}-panel`;
    
    tabBtn.classList.add('active');
    tabBtn.setAttribute('aria-selected', 'true');
    
    const panel = document.getElementById(panelId);
    if (panel) {
        panel.classList.remove('hidden');
        panel.classList.add('active');
    }
}

// Initialize profile tabs on page load
document.addEventListener('DOMContentLoaded', () => {
    initProfileTabs();
});

// ========================================
// SECTION 30: FORM SUBMISSION FEEDBACK
// ========================================

function enhanceFormFeedback() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            // Add visual feedback while submitting
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.classList.add('btn-saving');
                submitBtn.disabled = true;
                const originalText = submitBtn.textContent;
                submitBtn.textContent = '⟳ Saving...';
                
                // Restore after response
                form.addEventListener('change', () => {
                    submitBtn.classList.remove('btn-saving');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }, { once: true });
            }
        });
    });
}

// ========================================
// SECTION 31: KEYBOARD ACCESSIBILITY ENHANCEMENTS
// ========================================

// Add keyboard navigation for shopping list items
document.addEventListener('keydown', (e) => {
    if (e.target.classList.contains('list-item-checkbox')) {
        if (e.key === 'Delete' || e.key === 'Backspace') {
            e.preventDefault();
            const deleteBtn = e.target.closest('.list-item-layout').querySelector('.list-item-delete');
            if (deleteBtn) {
                triggerHaptic('medium');
                deleteBtn.click();
            }
        }
    }
});

// ========================================
// SECTION 32: PROGRESSIVE ENHANCEMENT CHECK
// ========================================

// Log support for advanced features
console.log('Haptic Feedback Support:', navigator.vibrate ? '✓' : '✗');
console.log('Touch Events Support:', 'ontouchstart' in window ? '✓' : '✗');
console.log('Service Worker Support:', 'serviceWorker' in navigator ? '✓' : '✗');

// ========================================
// SECTION 33: GLOBAL FUNCTION EXPORTS
// ========================================

// Explicitly export key functions to window for access from other contexts
window.generateMealCardHtml = generateMealCardHtml;
window.addToShoppingList = addToShoppingList;
window.escapeHtml = escapeHtml;
window.normalizeMealIcon = normalizeMealIcon;
