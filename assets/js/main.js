/* ========================================
   NUTRIPLAN - MAIN JAVASCRIPT
   All shared interactions and utilities
   ======================================== */

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
        // Simple toggle: add/remove 'open' class on mobile
        hamburger.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('visible');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 1023) {
                if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
                    sidebar.classList.remove('open');
                    if (overlay) overlay.classList.remove('visible');
                }
            }
        });

        // Close sidebar when a nav item is clicked
        document.querySelectorAll('.nav-item a').forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth <= 1023) {
                    sidebar.classList.remove('open');
                    if (overlay) overlay.classList.remove('visible');
                }
            });
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

// ========================================
// SECTION 5: MODAL MANAGEMENT
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

let searchTimeout;

function debounceSearch(query, callback) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
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

async function addToShoppingList(mealId) {
    try {
        showLoader(true);
        const csrf = window.CSRF_TOKEN || '';
        const response = await fetch('/api/shopping_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `action=add&meal_id=${mealId}&csrf_token=${encodeURIComponent(csrf)}`
        });
        
        const contentType = response.headers.get('content-type') || '';
        let result;
        
        if (contentType.includes('application/json')) {
            try {
                result = await response.json();
            } catch (err) {
                console.error('Failed to parse JSON:', err);
                showToast('Server error: invalid response', 'error');
                return;
            }
        } else {
            showToast('Server error: expected JSON response', 'error');
            return;
        }
        
        if (result.success) {
            showToast('Added to shopping list!', 'success');
        } else {
            showToast('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error adding to list', 'error');
    } finally {
        showLoader(false);
    }
}

async function toggleShoppingItem(itemId) {
    try {
        showLoader(true);
        const csrf = window.CSRF_TOKEN || '';
        const response = await fetch('/api/shopping_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `action=toggle&item_id=${itemId}&csrf_token=${encodeURIComponent(csrf)}`
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
        }
    } catch (error) {
        console.error('Error:', error);
    } finally {
        showLoader(false);
    }
}

async function deleteShoppingItem(itemId) {
    if (confirm('Delete this item?')) {
        try {
            showLoader(true);
            const csrf = window.CSRF_TOKEN || '';
            const response = await fetch('/api/shopping_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=delete&item_id=${itemId}&csrf_token=${encodeURIComponent(csrf)}`
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
                showToast('Item deleted', 'success');
            }
        } catch (error) {
            console.error('Error:', error);
        } finally {
            showLoader(false);
        }
    }
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
        const response = await fetch('/api/upload_avatar.php', {
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
            const response = await fetch(`/api/check_username.php?username=${username}`);
            
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

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    initPasswordStrength();
    animateCounters();
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
