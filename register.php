<?php
require_once __DIR__ . '/includes/session.php';
secure_session_start();

require_once 'includes/db_connect.php';
require_once __DIR__ . '/includes/csrf.php';

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
    $csrf_token = $_POST['csrf_token'] ?? '';

    // CSRF check
    if (!validate_csrf($csrf_token)) {
        $error = 'Invalid CSRF token. Please refresh and try again.';
    } elseif (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password)) {
        $error = 'All fields required';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        // Check if username/email already exists (PDO)
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = :username OR email = :email');
        $stmt->execute([':username' => $username, ':email' => $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email already taken';
        } else {
            // Create user (PDO)
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, first_name, last_name) VALUES (:username, :email, :hash, :first_name, :last_name)');
            $ok = $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':hash' => $hash,
                ':first_name' => $first_name,
                ':last_name' => $last_name
            ]);
            if ($ok) {
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Registration error: Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - NutriPlan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
</head>
<body>    <!-- Skip to content link for accessibility -->
    <a href="#main-content" class="sr-only-skip">Skip to registration form</a>
        <div class="split-layout">
        <!-- Left Panel -->
        <div class="split-layout-left">
            <div style="margin-bottom: var(--sp-12);">
                <div style="font-size: var(--text-3xl); font-weight: 800; margin-bottom: var(--sp-6);">🍽 NutriPlan</div>
                <h1 class="text-gradient">Join 1000s of Planners</h1>
                
                <!-- Progress indicator -->
                <div style="margin-top: var(--sp-12);">
                    <div style="display: flex; align-items: center; margin-bottom: var(--sp-6); gap: clamp(4px, 2vw, 12px);">
                        <div style="width: clamp(28px, 8vw, 36px); height: clamp(28px, 8vw, 36px); border-radius: 50%; background: var(--grad-primary); color: #030712; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: clamp(0.75rem, 2vw, 0.875rem);">1</div>
                        <div style="flex: 1; height: 1px; background: var(--border); margin: 0 clamp(4px, 1.5vw, 12px);"></div>
                        <div style="width: clamp(28px, 8vw, 36px); height: clamp(28px, 8vw, 36px); border-radius: 50%; background: var(--border); color: var(--text-2); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: clamp(0.75rem, 2vw, 0.875rem);">2</div>
                        <div style="flex: 1; height: 1px; background: var(--border); margin: 0 clamp(4px, 1.5vw, 12px);"></div>
                        <div style="width: clamp(28px, 8vw, 36px); height: clamp(28px, 8vw, 36px); border-radius: 50%; background: var(--border); color: var(--text-2); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: clamp(0.75rem, 2vw, 0.875rem);">3</div>
                    </div>
                    <p style="font-size: var(--text-sm); color: var(--text-2);">Account Creation</p>
                </div>
            </div>
        </div>
        
        <!-- Right Panel (Form) -->
        <div class="split-layout-right" id="main-content">
            <div style="max-width: clamp(280px, 95vw, 400px); margin: 0 auto; width: 100%; padding: var(--sp-6) 0;">
                <h2 style="margin-bottom: var(--sp-8);">Create Account</h2>
                
                <?php if (!empty($error)): ?>
                <div class="alert-error" role="alert" aria-live="polite">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" novalidate>
                    <?php echo csrf_field(); ?>
                    <div class="form-grid-2">
                        <div class="field">
                            <input type="text" id="first_name" name="first_name" placeholder=" " required aria-label="First name">
                            <label for="first_name">First Name</label>
                        </div>
                        <div class="field">
                            <input type="text" id="last_name" name="last_name" placeholder=" " required aria-label="Last name">
                            <label for="last_name">Last Name</label>
                        </div>
                    </div>
                    
                    <div class="field">
                        <input type="text" id="username" name="username" placeholder=" " required minlength="3" aria-label="Username" onblur="checkUsernameAvailability(this.value)">
                        <label for="username">Username</label>
                        <div class="username-status" style="font-size: var(--text-xs); margin-top: var(--sp-2);" role="status" aria-live="polite"></div>
                    </div>
                    
                    <div class="field">
                        <input type="email" id="email" name="email" placeholder=" " required aria-label="Email address">
                        <label for="email">Email address</label>
                    </div>
                    
                    <div class="field">
                        <input type="password" id="password" name="password" placeholder=" " required minlength="8" data-strength aria-label="Password" aria-describedby="strength-text" onkeyup="updatePasswordStrengthDisplay();">
                        <label for="password">Password</label>
                        <div class="strength-meter">
                            <div class="strength-bar"></div>
                        </div>
                        <div class="strength-text" id="strength-text">Minimum 8 characters, include uppercase, numbers, and symbols</div>
                    </div>
                    
                    <div class="field">
                        <input type="password" id="confirm" name="confirm" placeholder=" " required minlength="8" aria-label="Confirm password" oninput="checkPasswordMatch()">
                        <label for="confirm">Confirm Password</label>
                        <div id="password-match-status" style="font-size: var(--text-xs); margin-top: var(--sp-2); min-height: 18px;"></div>
                    </div>
                    
                    <div class="checkbox" style="margin-bottom: var(--sp-6);">
                        <input type="checkbox" id="terms" name="terms" required aria-label="I agree to Terms and Conditions">
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
    <script>
        function updatePasswordStrengthDisplay() {
            const field = document.getElementById('password');
            if (!field) return;
            
            const strength = getPasswordStrength(field.value);
            const meter = field.parentElement.querySelector('.strength-meter');
            const text = field.parentElement.querySelector('.strength-text');
            
            if (!meter || !text) return;
            
            const bar = meter.querySelector('.strength-bar');
            const labels = ['', 'Weak — Add uppercase, numbers, or symbols', 'Fair — Add more variety', 'Good — Strong password', 'Strong — Excellent!'];
            const colors = ['', 'var(--danger)', 'var(--warning)', 'var(--primary)', 'var(--success)'];
            const classes = ['', 'has-weak', 'has-fair', 'has-good', 'has-strong'];
            
            bar.style.width = (strength / 4) * 100 + '%';
            bar.style.backgroundColor = colors[strength];
            
            text.textContent = labels[strength];
            text.className = 'strength-text ' + (classes[strength] || '');
        }
        
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm').value;
            const statusEl = document.getElementById('password-match-status');
            
            if (!confirm) {
                statusEl.textContent = '';
                return;
            }
            
            if (password === confirm) {
                statusEl.textContent = '✓ Passwords match';
                statusEl.style.color = 'var(--success)';
            } else {
                statusEl.textContent = '✕ Passwords don\'t match';
                statusEl.style.color = 'var(--danger)';
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            updatePasswordStrengthDisplay();
            
            // Trigger haptic feedback on input
            document.getElementById('password')?.addEventListener('input', () => {
                triggerHaptic('light');
            });
        });
    </script>
