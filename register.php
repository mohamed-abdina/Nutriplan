<?php
session_start();
require_once 'includes/db_connect.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password)) {
        $error = 'All fields required';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        // Check if username/email already exists
        $check = $conn->query("SELECT user_id FROM users WHERE username = '$username' OR email = '$email'");
        if ($check->num_rows > 0) {
            $error = 'Username or email already taken';
        } else {
            // Create user
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $query = "INSERT INTO users (username, email, password_hash, first_name, last_name) 
                     VALUES ('$username', '$email', '$hash', '$first_name', '$last_name')";
            
            if ($conn->query($query)) {
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['username'] = $username;
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Registration error: ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - NutriPlan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
</head>
<body>
    <div class="flex" style="min-height: 100dvh;">
        <!-- Left Panel -->
        <div style="width: 44%; background: var(--surface); padding: var(--sp-8); display: flex; flex-direction: column; justify-content: center;">
            <div style="margin-bottom: var(--sp-12);">
                <div style="font-size: var(--text-3xl); font-weight: 800; margin-bottom: var(--sp-6);">🍽 NutriPlan</div>
                <h1 class="text-gradient">Join 1000s of Planners</h1>
                
                <!-- Progress indicator -->
                <div style="margin-top: var(--sp-12);">
                    <div style="display: flex; align-items: center; margin-bottom: var(--sp-6);">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--grad-primary); color: #030712; display: flex; align-items: center; justify-content: center; font-weight: 700;">1</div>
                        <div style="flex: 1; height: 1px; background: var(--border); margin: 0 var(--sp-3);"></div>
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--border); color: var(--text-2); display: flex; align-items: center; justify-content: center; font-weight: 700;">2</div>
                        <div style="flex: 1; height: 1px; background: var(--border); margin: 0 var(--sp-3);"></div>
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--border); color: var(--text-2); display: flex; align-items: center; justify-content: center; font-weight: 700;">3</div>
                    </div>
                    <p style="font-size: var(--text-sm); color: var(--text-2);">Account Creation</p>
                </div>
            </div>
        </div>
        
        <!-- Right Panel (Form) -->
        <div style="width: 56%; background: var(--bg); padding: var(--sp-8); display: flex; flex-direction: column; justify-content: center; overflow-y: auto;">
            <div style="max-width: 400px; margin: 0 auto; width: 100%; padding: var(--sp-6) 0;">
                <h2 style="margin-bottom: var(--sp-8);">Create Account</h2>
                
                <?php if (!empty($error)): ?>
                <div style="background: rgba(248, 113, 113, 0.15); border: 1px solid var(--danger); border-radius: 8px; padding: var(--sp-4); margin-bottom: var(--sp-6); color: var(--danger); font-size: var(--text-sm);">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--sp-4);">
                        <div class="field">
                            <input type="text" id="first_name" name="first_name" placeholder=" " required>
                            <label for="first_name">First Name</label>
                        </div>
                        <div class="field">
                            <input type="text" id="last_name" name="last_name" placeholder=" " required>
                            <label for="last_name">Last Name</label>
                        </div>
                    </div>
                    
                    <div class="field">
                        <input type="text" id="username" name="username" placeholder=" " required minlength="3" onblur="checkUsernameAvailability(this.value)">
                        <label for="username">Username</label>
                        <div class="username-status" style="font-size: var(--text-xs); margin-top: var(--sp-2);"></div>
                    </div>
                    
                    <div class="field">
                        <input type="email" id="email" name="email" placeholder=" " required>
                        <label for="email">Email address</label>
                    </div>
                    
                    <div class="field">
                        <input type="password" id="password" name="password" placeholder=" " required minlength="8" data-strength onkeyup="initPasswordStrength();">
                        <label for="password">Password</label>
                        <div class="strength-meter">
                            <div class="strength-bar"></div>
                        </div>
                        <div class="strength-text"></div>
                    </div>
                    
                    <div class="field">
                        <input type="password" id="confirm" name="confirm" placeholder=" " required minlength="8">
                        <label for="confirm">Confirm Password</label>
                    </div>
                    
                    <div class="checkbox" style="margin-bottom: var(--sp-6);">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to Terms and Conditions</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full" style="margin-bottom: var(--sp-4);">Create Account</button>
                </form>
                
                <p style="text-align: center; color: var(--text-2); font-size: var(--text-sm);">
                    Already have an account? <a href="login.php" style="color: var(--primary); text-decoration: none;">Sign in</a>
                </p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js" defer></script>
</body>
</html>
