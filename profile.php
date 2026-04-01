<?php
require_once __DIR__ . '/includes/session.php';
secure_session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Get user info
$user = pdo_fetch_one("SELECT * FROM users WHERE user_id = ?", [$user_id]) ?? [];

// Handle profile updates
$update_error = '';
$update_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($csrf)) {
        $update_error = 'Security error: Session expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
    
        if ($action === 'update_profile') {
            $first_name = sanitize_input($_POST['first_name'] ?? '');
            $last_name = sanitize_input($_POST['last_name'] ?? '');
            
            if (!empty($first_name) && !empty($last_name)) {
                pdo_query("UPDATE users SET first_name = ?, last_name = ? WHERE user_id = ?", [$first_name, $last_name, $user_id]);
                $update_success = true;
            } else {
                $update_error = 'First name and last name are required.';
            }
        }
    }
}

// Fix: Ensure meals_info is always an array to prevent "Trying to access array offset on value of type bool" error
$meals_info_result = pdo_fetch_one("SELECT COUNT(*) as total FROM meals WHERE user_id = ?", [$user_id]);
$meals_info = (is_array($meals_info_result) && isset($meals_info_result['total'])) 
    ? $meals_info_result 
    : ['total' => 0];

$list_info = pdo_fetch_one("SELECT COUNT(*) as lists FROM shopping_lists WHERE user_id = ?", [$user_id]) ?? ['lists' => 0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - NutriPlan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
    <?php require_once __DIR__ . '/includes/csrf.php'; ?>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
    <div class="app-shell">
        <?php include 'components/sidebar.php'; ?>
        
        <main class="main page-enter">
            <!-- Breadcrumb Navigation -->
            <div style="display: flex; align-items: center; gap: var(--sp-2); margin-bottom: var(--sp-6); color: var(--text-3); font-size: var(--text-sm);">
                <a href="dashboard.php" style="color: var(--text-2); text-decoration: none; transition: color 0.2s;">Dashboard</a>
                <span>/</span>
                <span style="color: var(--text-1); font-weight: 500;">Profile</span>
            </div>
            <!-- Profile Header -->
            <div class="responsive-profile-card card">
                <div style="height: 4px; background: var(--grad-primary); margin: -24px -24px 0; margin-bottom: var(--sp-6);"></div>
                
                <!-- Avatar with upload affordance -->
                <div style="position: relative; width: clamp(80px, 25vw, 96px); height: clamp(80px, 25vw, 96px); margin: 0 auto var(--sp-4);">
                    <div class="profile-avatar" style="width: 100%; height: 100%; background: var(--grad-primary); border-radius: 50%; background-size: cover; background-position: center;"></div>
                    <button onclick="document.getElementById('avatar-input').click()" title="Upload new avatar" style="position: absolute; bottom: 0; right: 0; width: 40px; height: 40px; background: var(--primary); border: 3px solid var(--bg); border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; min-width: 40px; min-height: 40px; transition: all 0.2s ease;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    <input type="file" id="avatar-input" accept="image/*" style="display: none;" onchange="const file=this.files[0];  if(file)handleAvatarUpload(file);">
                </div>
                
                <h2><?php echo htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($user['last_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <p style="color: var(--text-2); margin-top: var(--sp-2);">@<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></p>
                
                <!-- Stats - Improved layout -->
                <div class="stats-grid-auto">
                    <div style="text-align: center; padding: var(--sp-3);">
                        <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--primary); line-height: 1;"><?php echo (int)($meals_info['total'] ?? 0); ?></div>
                        <div style="font-size: var(--text-xs); color: var(--text-2); margin-top: var(--sp-1);">Meals Saved</div>
                    </div>
                    <div style="text-align: center; padding: var(--sp-3);">
                        <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--success); line-height: 1;"><?php echo (int)($list_info['lists'] ?? 0); ?></div>
                        <div style="font-size: var(--text-xs); color: var(--text-2); margin-top: var(--sp-1);">Lists Created</div>
                    </div>
                    <div style="text-align: center; padding: var(--sp-3);">
                        <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--accent); line-height: 1;">12</div>
                        <div style="font-size: var(--text-xs); color: var(--text-2); margin-top: var(--sp-1);">Weeks Active</div>
                    </div>
                </div>
            </div>
            
            <!-- Settings Tabs -->
            <div class="tab-button-group" role="tablist" style="margin-bottom: var(--sp-8); display: flex; gap: var(--sp-2); overflow-x: auto; overflow-y: hidden;">
                <button class="tab-btn tab-button active" data-tab="personal" role="tab" aria-selected="true" aria-controls="personal-panel" id="personal-tab" title="Edit personal information">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 0.5em;">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>Personal
                </button>
                <button class="tab-btn tab-button" data-tab="favorites" role="tab" aria-selected="false" aria-controls="favorites-panel" id="favorites-tab" title="View your favorite meals">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 0.5em;">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>Favorites
                </button>
                <button class="tab-btn tab-button" data-tab="analytics" role="tab" aria-selected="false" aria-controls="analytics-panel" id="analytics-tab" title="View your nutrition insights">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 0.5em;">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>Analytics
                </button>
                <button class="tab-btn tab-button" data-tab="preferences" role="tab" aria-selected="false" aria-controls="preferences-panel" id="preferences-tab" title="Manage your preferences">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 0.5em;">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m5.08 5.08l4.24 4.24M1 12h6m6 0h6M4.22 19.78l4.24-4.24m5.08-5.08l4.24-4.24"></path>
                    </svg>Preferences
                </button>
                <button class="tab-btn tab-button" data-tab="security" role="tab" aria-selected="false" aria-controls="security-panel" id="security-tab" title="Security settings">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 0.5em;">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>Security
                </button>
            </div>
            
            <!-- Personal Info Tab -->
            <div id="personal-panel" role="tabpanel" aria-labelledby="personal-tab" class="tab-panel active">
                <div style="max-width: 600px;">
                    <div style="display: flex; gap: var(--sp-4); margin-bottom: var(--sp-6);">
                        <div style="flex: 1;">
                            <?php if ($update_success): ?>
                            <div style="background: rgba(52, 211, 153, 0.15); border: 1px solid var(--success); border-radius: 8px; padding: var(--sp-4); color: var(--success); font-size: var(--text-sm); display: flex; gap: var(--sp-2); align-items: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                Profile updated successfully
                            </div>
                            <?php endif; ?>
                            <?php if ($update_error): ?>
                            <div style="background: rgba(248, 113, 113, 0.15); border: 1px solid var(--danger); border-radius: 8px; padding: var(--sp-4); color: var(--danger); font-size: var(--text-sm); display: flex; gap: var(--sp-2); align-items: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                <?php echo htmlspecialchars($update_error, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <?php echo csrf_field(); ?>
                        
                        <div class="form-grid-2">
                            <div class="field">
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8'); ?>" placeholder=" " required>
                                <label>First Name</label>
                            </div>
                            <div class="field">
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'], ENT_QUOTES, 'UTF-8'); ?>" placeholder=" " required>
                                <label>Last Name</label>
                            </div>
                        </div>
                        
                        <div class="field" style="position: relative;">
                            <input type="email" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" placeholder=" " disabled style="padding-right: 40px;">
                            <label>Email</label>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none;" title="This field cannot be changed">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <small style="color: var(--text-3); margin-top: var(--sp-1); display: block; font-style: italic;">💡 Cannot be changed for security reasons</small>
                        </div>
                        
                        <div class="field" style="position: relative;">
                            <input type="text" value="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>" placeholder=" " disabled style="padding-right: 40px;">
                            <label>Username</label>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none;" title="This field cannot be changed">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <small style="color: var(--text-3); margin-top: var(--sp-1); display: block; font-style: italic;">💡 Cannot be changed for security reasons</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Save Changes</button>
                    </form>
                </div>
            </div>
            
            <!-- Favorites Tab -->
            <div id="favorites-panel" role="tabpanel" aria-labelledby="favorites-tab" class="tab-panel hidden">
                <div>
                    <h3 style="margin-bottom: var(--sp-6);">⭐ Your Favorite Meals</h3>
                    <div id="favoritesList" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: var(--sp-4);">
                        <!-- Favorites will load here -->
                    </div>
                    <div id="noFavorites" class="hidden" style="text-align: center; padding: var(--sp-12); color: var(--text-2);">
                        <div style="font-size: 3rem; margin-bottom: var(--sp-4);">💔</div>
                        <p>No favorite meals yet. Rate meals as you try them!</p>
                    </div>
                </div>
            </div>
            
            <!-- Analytics Tab -->
            <div id="analytics-panel" role="tabpanel" aria-labelledby="analytics-tab" class="tab-panel hidden">
                <div style="max-width: 100%;">
                    <h3 style="margin-bottom: var(--sp-6);">📊 Your Nutrition Insights</h3>
                    
                    <!-- Weekly Stats -->
                    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: var(--sp-6); margin-bottom: var(--sp-8);">
                        <h4 style="margin-bottom: var(--sp-4);">📈 Weekly Calorie Intake</h4>
                        <div id="weeklyChart" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: var(--sp-2); height: 200px; align-items: flex-end;">
                            <!-- Chart bars will load here -->
                        </div>
                    </div>
                    
                    <!-- Nutrition Trends -->
                    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: var(--sp-6); margin-bottom: var(--sp-8);">
                        <h4 style="margin-bottom: var(--sp-4);">💪 30-Day Nutrition Average</h4>
                        <div id="nutritionTrends" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: var(--sp-4);">
                            <!-- Trends will load here -->
                        </div>
                    </div>
                    
                    <!-- Most Frequent Meals -->
                    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: var(--sp-6); margin-bottom: var(--sp-8);">
                        <h4 style="margin-bottom: var(--sp-4);">🔁 Your Go-To Meals</h4>
                        <div id="frequentMeals" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: var(--sp-4);">
                            <!-- Meals will load here -->
                        </div>
                    </div>
                    
                    <!-- Export Data -->
                    <button class="btn btn-primary" onclick="exportMealData()" style="width: 100%;">📥 Export Meal History (CSV)</button>
                </div>
            </div>
            
            <!-- Preferences Tab -->
            <div id="preferences-panel" role="tabpanel" aria-labelledby="preferences-tab" class="tab-panel hidden">
                <div style="max-width: 600px;">
                    <h3 style="margin-bottom: var(--sp-6);">🎯 Meal Preferences</h3>
                    
                    <div id="preferences-content" style="background: var(--elevated); border-radius: 12px; padding: var(--sp-6);">
                        <!-- Portion Size -->
                        <div style="margin-bottom: var(--sp-6);">
                            <label for="portion-size" style="font-size: var(--text-sm); font-weight: 600; color: var(--text-1); display: block; margin-bottom: var(--sp-3);">🍽️ Portion Size</label>
                            <select id="portion-size" style="width: 100%; padding: var(--sp-3); background: var(--inset); border: 1px solid var(--border); border-radius: 8px; color: var(--text-1); cursor: pointer; font-size: var(--text-sm);">
                                <option value="small">Small (300-500 cal)</option>
                                <option value="normal">Normal (500-700 cal)</option>
                                <option value="large">Large (700-900 cal)</option>
                                <option value="extra-large">Extra Large (900+ cal)</option>
                            </select>
                        </div>
                        
                        <!-- Dietary Restrictions -->
                        <div style="margin-bottom: var(--sp-6);">
                            <label for="dietary-restrictions" style="font-size: var(--text-sm); font-weight: 600; color: var(--text-1); display: block; margin-bottom: var(--sp-3);">🥗 Dietary Restrictions</label>
                            <textarea id="dietary-restrictions" placeholder="e.g., Vegetarian, Vegan, Gluten-free (comma-separated)" style="width: 100%; padding: var(--sp-3); background: var(--inset); border: 1px solid var(--border); border-radius: 8px; color: var(--text-1); font-family: inherit; resize: vertical; min-height: clamp(60px, auto, 100px);"></textarea>
                        </div>
                        
                        <!-- Allergies -->
                        <div style="margin-bottom: var(--sp-6);">
                            <label for="allergies" style="font-size: var(--text-sm); font-weight: 600; color: var(--text-1); display: block; margin-bottom: var(--sp-3);">⚠️ Allergies</label>
                            <textarea id="allergies" placeholder="e.g., Peanuts, Shellfish, Dairy (comma-separated)" style="width: 100%; padding: var(--sp-3); background: var(--inset); border: 1px solid var(--border); border-radius: 8px; color: var(--text-1); font-family: inherit; resize: vertical; min-height: clamp(60px, auto, 100px);"></textarea>
                        </div>
                        
                        <!-- Preferred Cuisine -->
                        <div style="margin-bottom: var(--sp-6);">
                            <label for="preferred-cuisine" style="font-size: var(--text-sm); font-weight: 600; color: var(--text-1); display: block; margin-bottom: var(--sp-3);">🌍 Preferred Cuisines</label>
                            <select id="preferred-cuisine" multiple style="width: 100%; padding: var(--sp-3); background: var(--inset); border: 2px solid var(--border); border-radius: 8px; color: var(--text-1); cursor: pointer; min-height: clamp(120px, auto, 160px); font-size: var(--text-base); line-height: 1.8;">
                                <option value="african">African</option>
                                <option value="asian">Asian</option>
                                <option value="european">European</option>
                                <option value="indian">Indian</option>
                                <option value="italian">Italian</option>
                                <option value="mediterranean">Mediterranean</option>
                                <option value="mexican">Mexican</option>
                                <option value="middle-eastern">Middle Eastern</option>
                            </select>
                            <small style="color: var(--text-2); margin-top: var(--sp-2); display: block;">📱 Tap options to select • 💻 Hold Ctrl/Cmd to select multiple</small>
                        </div>
                        
                        <!-- Notifications Toggle -->
                        <div style="margin-bottom: var(--sp-6);">
                            <label style="display: flex; align-items: center; gap: var(--sp-3); cursor: pointer; padding: var(--sp-2); background: var(--surface); border-radius: 8px; min-height: 48px;">
                                <input type="checkbox" id="notifications" checked style="width: 44px; height: 44px; cursor: pointer; flex-shrink: 0; accent-color: var(--primary);">
                                <div>
                                    <div style="color: var(--text-1); font-weight: 500;">🔔 Enable Notifications</div>
                                    <small style="color: var(--text-2);">Get updates on new meal recommendations</small>
                                </div>
                            </label>
                        </div>
                        
                        <button class="btn btn-primary" onclick="savePreferences()" style="width: 100%;">💾 Save Preferences</button>
                    </div>
                </div>
            </div>
            
            <!-- Security Tab -->
            <div id="security-panel" role="tabpanel" aria-labelledby="security-tab" class="tab-panel hidden">
                <div style="max-width: 500px;">
                    <p style="color: var(--text-2); margin-bottom: var(--sp-6);">Manage your account security settings.</p>
                    
                    <div class="card" style="background: var(--overlay); border-color: var(--border);">
                        <h3 style="margin-bottom: var(--sp-4);">Change Password</h3>
                        <p style="color: var(--text-2); font-size: var(--text-sm); margin-bottom: var(--sp-6);">Coming soon: Password change functionality</p>
                    </div>
                </div>
            </div>
            
            <!-- Danger Zone -->
            <div style="margin-top: var(--sp-12); border-top: 2px solid var(--border); padding-top: var(--sp-12);">
                <h3 style="color: var(--danger); margin-bottom: var(--sp-6);">🗑️ Danger Zone</h3>
                <div style="background: rgba(248, 113, 113, 0.1); border: 2px solid var(--danger); border-radius: 12px; padding: var(--sp-6);">
                    <h4 style="margin-bottom: var(--sp-2);">Delete Account</h4>
                    <p style="color: var(--text-2); font-size: var(--text-sm); margin-bottom: var(--sp-4);">Permanently delete your account and all associated data. This action cannot be undone.</p>
                    <button class="btn btn-danger" onclick="openModal('delete-modal')">Delete Account</button>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal-backdrop modal-backdrop"></div>
    <div class="modal" id="delete-modal">
        <div class="modal-header">
            <h3 class="modal-title">Delete Account</h3>
            <button type="button" class="modal-close">✕</button>
        </div>
        <div class="modal-body">
            <p style="color: var(--text-1); margin-bottom: var(--sp-4);">This will permanently delete your account and all your data.</p>
            <p style="color: var(--text-2); font-size: var(--text-sm); margin-bottom: var(--sp-6);">Type <strong>DELETE</strong> to confirm:</p>
            <input type="text" id="delete-confirm-input" style="width: 100%; padding: var(--sp-3); background: var(--inset); border: 1px solid var(--border); border-radius: 8px; color: var(--text-1); margin-bottom: var(--sp-6);" placeholder="Type DELETE here">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('delete-modal')">Cancel</button>
            <button type="button" id="delete-final-btn" class="btn btn-danger" disabled onclick="confirmDelete()">Delete Account</button>
        </div>
    </div>
    
    <script src="assets/js/main.js" defer></script>
    <script>
        // Load preferences and favorites on page load
        window.addEventListener('load', () => {
            loadPreferences();
            // Add tab click listeners
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (btn.id === 'favorites-tab') {
                        loadFavorites();
                    } else if (btn.id === 'analytics-tab') {
                        loadAnalytics();
                    }
                });
            });
        });
        
        function loadFavorites() {
            const formData = new URLSearchParams({
                action: 'get_favorites',
                limit: '12',
                csrf_token: getCsrfToken()
            });
            
            fetch('api/meal_ratings.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                const list = document.getElementById('favoritesList');
                const noFavorites = document.getElementById('noFavorites');
                
                if (data.success && data.favorites && data.favorites.length > 0) {
                    list.innerHTML = data.favorites.map(meal => `
                        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: var(--sp-4); overflow: hidden;">
                            <div style="font-size: 2rem; margin-bottom: var(--sp-2);">${escapeHtml(meal.meal_icon)}</div>
                            <h4 style="margin-bottom: var(--sp-2);">${escapeHtml(meal.meal_name)}</h4>
                            <p style="color: var(--text-2); font-size: var(--text-xs); margin-bottom: var(--sp-3);">${escapeHtml(meal.category_name)}</p>
                            
                            <div style="display: flex; gap: var(--sp-2); margin-bottom: var(--sp-3); flex-wrap: wrap;">
                                <div style="background: rgba(var(--primary-rgb, 59, 130, 246), 0.1); padding: 4px 8px; border-radius: 6px; font-size: var(--text-xs); color: var(--primary); font-weight: 500;">
                                    🔥 ${escapeHtml(meal.calories.toString())} cal
                                </div>
                                <div style="background: rgba(var(--accent-rgb, 168, 85, 247), 0.1); padding: 4px 8px; border-radius: 6px; font-size: var(--text-xs); color: var(--accent); font-weight: 500;">
                                    💪 ${escapeHtml(meal.proteins_g.toString())}g
                                </div>
                            </div>
                            
                            ${meal.rating ? `<div style="color: var(--warning); font-size: var(--text-sm); margin-bottom: var(--sp-3);">⭐ Rating: ${escapeHtml(meal.rating.toString())}/5</div>` : ''}
                            
                            <div style="display: flex; gap: var(--sp-2);">
                                <a href="meal.php?id=${meal.meal_id}" class="btn btn-outline btn-sm" style="flex: 1; text-align: center;">Details</a>
                                <button class="btn btn-ghost btn-sm" onclick="addToShoppingList(${meal.meal_id})">+ Add</button>
                            </div>
                        </div>
                    `).join('');
                    noFavorites.classList.add('hidden');
                } else {
                    list.innerHTML = '';
                    noFavorites.classList.remove('hidden');
                }
            })
            .catch(e => {
                console.error('Error loading favorites:', e);
                showToast('Error loading favorites', 'error');
            });
        }
        
        function loadAnalytics() {
            // Load weekly stats
            Promise.all([
                fetch('api/analytics.php?action=weekly_stats').then(r => r.json()),
                fetch('api/analytics.php?action=nutrition_trends').then(r => r.json()),
                fetch('api/analytics.php?action=meal_frequency').then(r => r.json())
            ])
            .then(([weeklyData, trendsData, frequencyData]) => {
                // Weekly Chart
                if (weeklyData.success && weeklyData.stats) {
                    const chart = document.getElementById('weeklyChart');
                    const maxCal = Math.max(...weeklyData.stats.map(s => s.calories || 1), 1);
                    
                    chart.innerHTML = weeklyData.stats.map(day => {
                        const heightPercent = (day.calories / maxCal * 100) || 0;
                        return `
                            <div style="display: flex; flex-direction: column; justify-content: flex-end; align-items: center; gap: 4px; height: 100%;">
                                <div style="background: linear-gradient(to top, var(--primary), var(--primary)); width: 100%; height: ${heightPercent}%; border-radius: 4px; min-height: 20px; transition: all 0.3s ease;"></div>
                                <div style="font-size: var(--text-xs); color: var(--text-2);">${escapeHtml(day.day)}</div>
                                <div style="font-size: var(--text-xs); font-weight: 600; color: var(--text-1);">${(day.calories).toLocaleString()}</div>
                            </div>
                        `;
                    }).join('');
                }
                
                // Nutrition Trends
                if (trendsData.success && trendsData.trends) {
                    const t = trendsData.trends;
                    const trends = document.getElementById('nutritionTrends');
                    trends.innerHTML = `
                        <div>
                            <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--warning);">${Math.round(t.avg_calories)}</div>
                            <div style="font-size: var(--text-xs); color: var(--text-2);">Avg Calories</div>
                        </div>
                        <div>
                            <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--accent);">${Math.round(t.avg_protein)}g</div>
                            <div style="font-size: var(--text-xs); color: var(--text-2);">Avg Protein</div>
                        </div>
                        <div>
                            <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--primary);">${Math.round(t.avg_carbs)}g</div>
                            <div style="font-size: var(--text-xs); color: var(--text-2);">Avg Carbs</div>
                        </div>
                        <div>
                            <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--success);">${Math.round(t.avg_fats)}g</div>
                            <div style="font-size: var(--text-xs); color: var(--text-2);">Avg Fats</div>
                        </div>
                    `;
                }
                
                // Frequent Meals
                if (frequencyData.success && frequencyData.meals) {
                    const meals = document.getElementById('frequentMeals');
                    meals.innerHTML = frequencyData.meals.map(meal => `
                        <div style="background: var(--inset); border: 1px solid var(--border); border-radius: 12px; padding: var(--sp-3); text-align: center;">
                            <div style="font-size: 2rem; margin-bottom: var(--sp-1);">${escapeHtml(meal.meal_icon)}</div>
                            <h5 style="margin-bottom: var(--sp-1);">${escapeHtml(meal.meal_name)}</h5>
                            <div style="font-size: var(--text-xs); color: var(--text-2); margin-bottom: var(--sp-2);">Added ${escapeHtml(meal.times_added.toString())} times</div>
                            ${meal.avg_rating > 0 ? `<div style="color: var(--warning); font-size: var(--text-sm);">⭐ ${meal.avg_rating.toFixed(1)}/5</div>` : ''}
                        </div>
                    `).join('');
                }
            })
            .catch(e => {
                console.error('Error loading analytics:', e);
                showToast('Error loading analytics', 'error');
            });
        }
        
        function exportMealData() {
            window.location.href = 'api/analytics.php?action=export_data';
        }
        
        function loadPreferences() {
            const formData = new URLSearchParams({
                action: 'get',
                csrf_token: getCsrfToken()
            });
            
            fetch('api/user_preferences.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.preferences) {
                    const p = data.preferences;
                    document.getElementById('portion-size').value = p.portion_size || 'normal';
                    document.getElementById('dietary-restrictions').value = p.dietary_restrictions || '';
                    document.getElementById('allergies').value = p.allergies || '';
                    document.getElementById('preferred-cuisine').value = p.preferred_cuisine || '';
                    document.getElementById('notifications').checked = p.notifications_enabled;
                }
            })
            .catch(e => console.error('Error loading preferences:', e));
        }
        
        function savePreferences() {
            const cuisineSelect = document.getElementById('preferred-cuisine');
            const selectedCuisines = Array.from(cuisineSelect.selectedOptions).map(opt => opt.value).join(',');
            
            const formData = new URLSearchParams({
                action: 'update',
                portion_size: document.getElementById('portion-size').value,
                dietary_restrictions: document.getElementById('dietary-restrictions').value,
                allergies: document.getElementById('allergies').value,
                preferred_cuisine: selectedCuisines,
                notifications_enabled: document.getElementById('notifications').checked ? 1 : 0,
                theme_preference: document.documentElement.getAttribute('data-theme') || 'dark',
                csrf_token: getCsrfToken()
            });
            
            fetch('api/user_preferences.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('✓ Preferences saved!', 'success');
                } else {
                    showToast('Error saving preferences', 'error');
                }
            })
            .catch(e => {
                console.error('Error:', e);
                showToast('Error saving preferences', 'error');
            });
        }
        
        document.getElementById('delete-confirm-input').addEventListener('input', (e) => {
            document.getElementById('delete-final-btn').disabled = e.target.value !== 'DELETE';
        });
        
        function confirmDelete() {
            const formData = new URLSearchParams({
                csrf_token: getCsrfToken()
            });
            fetch('deregister.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Account deleted', 'success');
                    setTimeout(() => location.href = 'index.php', 1500);
                } else {
                    showToast(data.message || 'Error deleting account', 'error');
                }
            })
            .catch(e => {
                console.error('Error:', e);
                showToast('Error deleting account', 'error');
            });
        }
    </script>
</body>
</html>
