<?php
session_start();
require_once 'includes/db_connect.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password required';
    } else {
        // Check user
        $result = $conn->query("SELECT user_id, username, password_hash FROM users WHERE email = '$email'");
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NutriPlan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
</head>
<body>
    <div class="flex" style="min-height: 100dvh;">
        <!-- Left Panel -->
        <div style="width: 44%; background: var(--surface); padding: var(--sp-8); display: flex; flex-direction: column; justify-content: center;">
            <div style="margin-bottom: var(--sp-12);">
                <div style="font-size: var(--text-3xl); font-weight: 800; margin-bottom: var(--sp-6);">🍽 NutriPlan</div>
                <h1 class="text-gradient">Smart Meal Planning</h1>
                <p style="color: var(--text-2); margin-top: var(--sp-4);">Plan meals, reduce waste, eat better. It's that simple.</p>
            </div>
            
            <div style="background: var(--elevated); padding: var(--sp-6); border-radius: 12px;">
                <p style="font-size: var(--text-sm); color: var(--text-1); font-style: italic; margin-bottom: var(--sp-4);">
                    "Finally, meal planning that actually works. No more food waste!"
                </p>
                <p style="font-size: var(--text-xs); color: var(--text-2);">— Sarah M., Student</p>
            </div>
        </div>
        
        <!-- Right Panel (Form) -->
        <div style="width: 56%; background: var(--bg); padding: var(--sp-8); display: flex; flex-direction: column; justify-content: center;">
            <div style="max-width: 400px; margin: 0 auto; width: 100%;">
                <h2 style="margin-bottom: var(--sp-8);">Welcome Back</h2>
                
                <?php if (!empty($error)): ?>
                <div style="background: rgba(248, 113, 113, 0.15); border: 1px solid var(--danger); border-radius: 8px; padding: var(--sp-4); margin-bottom: var(--sp-6); color: var(--danger); font-size: var(--text-sm);">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="ajax-form" action="">
                    <div class="field">
                        <input type="email" id="email" name="email" placeholder=" " required>
                        <label for="email">Email address</label>
                    </div>
                    
                    <div class="field" style="position: relative;">
                        <input type="password" id="password" name="password" placeholder=" " required>
                        <label for="password">Password</label>
                        <button type="button" class="password-visibility" onclick="togglePassword('password')">👁</button>
                    </div>
                    
                    <a href="#" style="font-size: var(--text-sm); color: var(--primary); text-decoration: none; display: block; margin-bottom: var(--sp-6); text-align: right;">Forgot password?</a>
                    
                    <button type="submit" class="btn btn-primary btn-full" style="margin-bottom: var(--sp-4);">Sign In</button>
                </form>
                
                <p style="text-align: center; color: var(--text-2); font-size: var(--text-sm);">
                    Don't have an account? <a href="register.php" style="color: var(--primary); text-decoration: none;">Sign up free</a>
                </p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js" defer></script>
    <script>
        function togglePassword(id) {
            const pass = document.getElementById(id);
            pass.type = pass.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
