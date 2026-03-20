<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];

// Get user info
$user_result = $conn->query("SELECT * FROM users WHERE user_id = $user_id");
$user = $user_result->fetch_assoc();

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
$meals_result = $conn->query("SELECT COUNT(DISTINCT DATE(created_at)) as days, COUNT(*) as total FROM meals");
$meals_info = $meals_result->fetch_assoc();

$list_result = $conn->query("SELECT COUNT(*) as lists FROM shopping_lists WHERE user_id = $user_id");
$list_info = $list_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
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
            <div class="card" style="text-align: center; margin-bottom: var(--sp-8);">
                <div style="height: 4px; background: var(--grad-primary); margin: -24px -24px 0; margin-bottom: var(--sp-6);"></div>
                <div style="position: relative; width: 96px; height: 96px; margin: 0 auto var(--sp-4);">
                    <div class="profile-avatar" style="width: 100%; height: 100%; background: var(--grad-primary); border-radius: 50%; background-size: cover; background-position: center;"></div>
                    <button onclick="document.getElementById('avatar-input').click()" style="position: absolute; bottom: 0; right: 0; width: 32px; height: 32px; background: var(--primary); border: 2px solid var(--bg); border-radius: 50%; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center;">✏️</button>
                    <input type="file" id="avatar-input" accept="image/*" style="display: none;" onchange="const file=this.files[0];  if(file)handleAvatarUpload(file);">
                </div>
                
                <h2><?php echo $user['first_name']; ?> <?php echo $user['last_name']; ?></h2>
                <p style="color: var(--text-2); margin-top: var(--sp-2);">@<?php echo $user['username']; ?></p>
                
                <!-- Stats -->
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--sp-6); margin-top: var(--sp-8);">
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
            <div style="display: flex; gap: var(--sp-4); margin-bottom: var(--sp-6); border-bottom: 1px solid var(--border); padding-bottom: var(--sp-4);">
                <button class="tab-btn active" data-tab="personal" style="background: none; border: none; color: var(--text-1); font-weight: 600; cursor: pointer; padding: 0;">👤 Personal Info</button>
                <button class="tab-btn" data-tab="security" style="background: none; border: none; color: var(--text-2); font-weight: 600; cursor: pointer; padding: 0;">🔐 Security</button>
            </div>
            
            <!-- Personal Info Tab -->
            <div id="personal" class="tab-panel active">
                <div style="max-width: 500px;">
                    <?php if ($update_success): ?>
                    <div style="background: rgba(52, 211, 153, 0.15); border: 1px solid var(--success); border-radius: 8px; padding: var(--sp-4); margin-bottom: var(--sp-6); color: var(--success); font-size: var(--text-sm);">
                        ✓ Profile updated successfully
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--sp-4); margin-bottom: var(--sp-6);">
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
            
            <!-- Security Tab -->
            <div id="security" class="tab-panel hidden">
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
