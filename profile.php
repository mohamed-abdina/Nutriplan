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
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        
        if (!empty($first_name) && !empty($last_name)) {
            $conn->query("UPDATE users SET first_name = '$first_name', last_name = '$last_name' WHERE user_id = $user_id");
            $user['first_name'] = $first_name;
            $user['last_name'] = $last_name;
            $update_success = true;
        }
    }
}

// Get user stats
$meals_info = pdo_fetch_one("SELECT COUNT(DISTINCT DATE(created_at)) as days, COUNT(*) as total FROM meals") ?? ['days' => 0, 'total' => 0];

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
</head>
<body>
    <div class="app-shell">
        <?php include 'components/sidebar.php'; ?>
        
        <main class="main page-enter">
            <!-- Profile Header -->
            <div class="responsive-profile-card card">
                <div style="height: 4px; background: var(--grad-primary); margin: -24px -24px 0; margin-bottom: var(--sp-6);"></div>
                <div style="position: relative; width: 96px; height: 96px; margin: 0 auto var(--sp-4);">
                    <div class="profile-avatar" style="width: 100%; height: 100%; background: var(--grad-primary); border-radius: 50%; background-size: cover; background-position: center;"></div>
                    <button onclick="document.getElementById('avatar-input').click()" style="position: absolute; bottom: 0; right: 0; width: 32px; height: 32px; background: var(--primary); border: 2px solid var(--bg); border-radius: 50%; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center;">✏️</button>
                    <input type="file" id="avatar-input" accept="image/*" style="display: none;" onchange="const file=this.files[0];  if(file)handleAvatarUpload(file);">
                </div>
                
                <h2><?php echo $user['first_name']; ?> <?php echo $user['last_name']; ?></h2>
                <p style="color: var(--text-2); margin-top: var(--sp-2);">@<?php echo $user['username']; ?></p>
                
                <!-- Stats -->
                <div class="stats-grid-auto">
                    <div>
                        <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--primary);"><?php echo $meals_info['total']; ?></div>
                        <div style="font-size: var(--text-xs); color: var(--text-2);">Meals Saved</div>
                    </div>
                    <div>
                        <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--success);"><?php echo $list_info['lists']; ?></div>
                        <div style="font-size: var(--text-xs); color: var(--text-2);">Lists Created</div>
                    </div>
                    <div>
                        <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--accent);">12</div>
                        <div style="font-size: var(--text-xs); color: var(--text-2);">Weeks Active</div>
                    </div>
                </div>
            </div>
            
            <!-- Settings Tabs -->
            <div class="tab-button-group" role="tablist">
                <button class="tab-btn tab-button active" data-tab="personal" role="tab" aria-selected="true" aria-controls="personal-panel" id="personal-tab">👤 Personal Info</button>
                <button class="tab-btn tab-button" data-tab="preferences" role="tab" aria-selected="false" aria-controls="preferences-panel" id="preferences-tab">⚙️ Preferences</button>
                <button class="tab-btn tab-button" data-tab="security" role="tab" aria-selected="false" aria-controls="security-panel" id="security-tab">🔐 Security</button>
            </div>
            
            <!-- Personal Info Tab -->
            <div id="personal-panel" role="tabpanel" aria-labelledby="personal-tab" class="tab-panel active">
                <div style="max-width: 500px;">
                    <?php if ($update_success): ?>
                    <div style="background: rgba(52, 211, 153, 0.15); border: 1px solid var(--success); border-radius: 8px; padding: var(--sp-4); margin-bottom: var(--sp-6); color: var(--success); font-size: var(--text-sm);">
                        ✓ Profile updated successfully
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-grid-2">
                            <div class="field">
                                <input type="text" name="first_name" value="<?php echo $user['first_name']; ?>" placeholder=" " required>
                                <label>First Name</label>
                            </div>
                            <div class="field">
                                <input type="text" name="last_name" value="<?php echo $user['last_name']; ?>" placeholder=" " required>
                                <label>Last Name</label>
                            </div>
                        </div>
                        
                        <div class="field">
                            <input type="email" value="<?php echo $user['email']; ?>" placeholder=" " disabled>
                            <label>Email (cannot change)</label>
                        </div>
                        
                        <div class="field">
                            <input type="text" value="<?php echo $user['username']; ?>" placeholder=" " disabled>
                            <label>Username (cannot change)</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
            
            <!-- Preferences Tab -->
            <div id="preferences-panel" role="tabpanel" aria-labelledby="preferences-tab" class="tab-panel hidden">
                <div style="max-width: 500px;">
                    <div id="preferences-content" style="background: var(--elevated); border-radius: 12px; padding: var(--sp-6);">
                        <div class="field">
                            <select id="portion-size" style="width: 100%; padding: var(--sp-3); background: var(--inset); border: 1px solid var(--border); border-radius: 8px; color: var(--text-1);">
                                <option value="small">Small</option>
                                <option value="normal" selected>Normal</option>
                                <option value="large">Large</option>
                                <option value="extra-large">Extra Large</option>
                            </select>
                            <label style="display: block; margin-top: var(--sp-2);">Portion Size</label>
                        </div>
                        
                        <div class="field" style="margin-top: var(--sp-4);">
                            <input type="text" id="dietary-restrictions" placeholder="e.g., Vegetarian, Vegan, Gluten-free" style="width: 100%; padding: var(--sp-3); background: var(--inset); border: 1px solid var(--border); border-radius: 8px; color: var(--text-1);">
                            <label style="display: block; margin-top: var(--sp-2);">Dietary Restrictions</label>
                        </div>
                        
                        <div class="field" style="margin-top: var(--sp-4);">
                            <input type="text" id="allergies" placeholder="e.g., Peanuts, Shellfish, Dairy" style="width: 100%; padding: var(--sp-3); background: var(--inset); border: 1px solid var(--border); border-radius: 8px; color: var(--text-1);">
                            <label style="display: block; margin-top: var(--sp-2);">Allergies</label>
                        </div>
                        
                        <div class="field" style="margin-top: var(--sp-4);">
                            <input type="text" id="preferred-cuisine" placeholder="e.g., African, Asian, Mediterranean" style="width: 100%; padding: var(--sp-3); background: var(--inset); border: 1px solid var(--border); border-radius: 8px; color: var(--text-1);">
                            <label style="display: block; margin-top: var(--sp-2);">Preferred Cuisine</label>
                        </div>
                        
                        <div style="margin-top: var(--sp-6);">
                            <label style="display: flex; align-items: center; gap: var(--sp-2); cursor: pointer;">
                                <input type="checkbox" id="notifications" checked style="width: 20px; height: 20px; cursor: pointer;">
                                <span style="color: var(--text-1);">Enable Notifications</span>
                            </label>
                        </div>
                        
                        <button class="btn btn-primary" onclick="savePreferences()" style="margin-top: var(--sp-6); width: 100%;">Save Preferences</button>
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
            <div style="margin-top: var(--sp-12);">
                <h3 style="color: var(--danger); margin-bottom: var(--sp-6);">Danger Zone</h3>
                <div style="background: rgba(248, 113, 113, 0.1); border: 1px solid var(--danger); border-radius: 12px; padding: var(--sp-6);">
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
        // Load preferences on page load
        window.addEventListener('load', loadPreferences);
        
        // Tab switching - update ARIA attributes
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');
                const tabPanel = document.getElementById(tabName + '-panel');
                
                // Hide all tab panels and update ARIA
                document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('active');
                    b.setAttribute('aria-selected', 'false');
                });
                
                // Show selected tab panel and update ARIA
                if (tabPanel) {
                    tabPanel.classList.remove('hidden');
                    this.classList.add('active');
                    this.setAttribute('aria-selected', 'true');
                    this.focus();
                }
            });
        });
        
        function loadPreferences() {
            fetch('api/user_preferences.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get'
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
            const formData = new URLSearchParams({
                action: 'update',
                portion_size: document.getElementById('portion-size').value,
                dietary_restrictions: document.getElementById('dietary-restrictions').value,
                allergies: document.getElementById('allergies').value,
                preferred_cuisine: document.getElementById('preferred-cuisine').value,
                notifications_enabled: document.getElementById('notifications').checked ? 1 : 0,
                theme_preference: document.documentElement.getAttribute('data-theme') || 'dark'
            });
            
            fetch('api/user_preferences.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Preferences saved successfully!', 'success');
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
            fetch('deregister.php', {method: 'POST'})
                .then(() => {
                    showToast('Account deleted', 'success');
                    setTimeout(() => location.href = 'index.php', 1500);
                });
        }
    </script>
</body>
</html>
