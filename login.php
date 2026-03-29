<?php
// Start output buffering to control any stray output

// Debug: Log request method and AJAX detection (safe, only if writable)
$debug_log = __DIR__ . '/logs/login_debug.log';
if (@is_writable(dirname($debug_log))) {
    @file_put_contents($debug_log, date('c') . " | METHOD: " . $_SERVER['REQUEST_METHOD'] . " | AJAX: " . (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : 'none') . "\n", FILE_APPEND);
}
ob_start();

require_once __DIR__ . '/includes/session.php';
secure_session_start();
require_once 'includes/db_connect.php';
// Optional: centralized logger for detailed debug output
if (file_exists(__DIR__ . '/includes/error_logger.php')) {
    require_once __DIR__ . '/includes/error_logger.php';
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    ob_end_clean();
    header('Location: dashboard.php');
    exit;
}

$error = '';
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    // Log request details for debugging (redact sensitive fields)
    if (isset($error_logger)) {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $masked = ['email' => $email, 'password' => '[REDACTED]'];
        $error_logger->log_api_call('/login.php', 'POST', $masked, null);
        $error_logger->log_error(E_USER_NOTICE, 'Login POST received', __FILE__, __LINE__, ['headers' => $headers]);
    }
    // Debug: Log POST data (redacted password)
    if (@is_writable(dirname($debug_log))) {
        @file_put_contents($debug_log, date('c') . " | POST: " . json_encode(['email' => $email, 'password' => '[REDACTED]']) . "\n", FILE_APPEND);
    }
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password required';
    } else {
        // Check user using PDO prepared statement
        $user = pdo_fetch_one('SELECT user_id, username, password_hash FROM users WHERE email = ?', [$email]);
        if ($user && is_array($user)) {
            $hash = $user['password_hash'] ?? '';
            if (password_verify($password, $hash)) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                // Log successful login event (do not include password)
                if (isset($error_logger)) {
                    $error_logger->log_security_event('LOGIN_SUCCESS', ['user_id' => $user['user_id'], 'email' => $email, 'session_id' => session_id()]);
                }
                
                // If AJAX request, return JSON and exit before outputting HTML
                if ($is_ajax) {
                    ob_end_clean();
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => true, 'message' => 'Login successful', 'redirect' => '/dashboard.php']);
                    exit;
                }
                
                // Non-AJAX: redirect
                ob_end_clean();
                header('Location: /dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password';
                if (isset($error_logger)) {
                    $error_logger->log_security_event('LOGIN_FAILED', ['email' => $email, 'reason' => 'bad_password']);
                }
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
    
    // If we're here and it's AJAX, return error JSON
    if ($is_ajax) {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $error ?: 'Invalid credentials', 'redirect' => null, 'debug' => 'AJAX POST error']);
        // Debug: Log AJAX error response
        if (@is_writable(dirname($debug_log))) {
            @file_put_contents($debug_log, date('c') . " | AJAX ERROR: " . ($error ?: 'Invalid credentials') . "\n", FILE_APPEND);
        }
        exit;
    }
}

// For non-POST AJAX requests, return error
if ($is_ajax) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Invalid request', 'redirect' => null, 'debug' => 'AJAX non-POST error']);
    // Debug: Log AJAX non-POST error
    if (@is_writable(dirname($debug_log))) {
        @file_put_contents($debug_log, date('c') . " | AJAX NON-POST ERROR\n", FILE_APPEND);
    }
    exit;
}

// If we reach here, output the HTML (end buffering first)
ob_end_flush();
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
    <div class="split-layout">
        <!-- Left Panel -->
        <div class="split-layout-left">
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
        <div class="split-layout-right">
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
    <!-- Debug: Show AJAX errors on page -->
    <script>
    // Patch: Show AJAX errors on page for debugging
    document.addEventListener('DOMContentLoaded', function() {
        if (window.sessionStorage && sessionStorage.loginAjaxError) {
            var dbg = document.createElement('div');
            dbg.style = 'background: #fee; color: #b00; border: 1px solid #b00; padding: 8px; margin-bottom: 12px;';
            dbg.textContent = '[AJAX ERROR] ' + sessionStorage.loginAjaxError;
            var form = document.querySelector('form.ajax-form');
            if (form) form.parentNode.insertBefore(dbg, form);
            sessionStorage.removeItem('loginAjaxError');
        }
    });
    // Patch main.js to store AJAX error
    (function() {
        var origFetch = window.fetch;
        window.fetch = function() {
            return origFetch.apply(this, arguments).then(function(resp) {
                if (resp && resp.headers && resp.headers.get('content-type') && resp.headers.get('content-type').includes('application/json')) {
                    return resp.clone().json().then(function(data) {
                        if (data && data.success === false && data.message) {
                            if (window.sessionStorage) sessionStorage.loginAjaxError = data.message;
                        }
                        return resp;
                    }).catch(function() { return resp; });
                }
                return resp;
            });
        };
    })();
    </script>
    <script>
        function togglePassword(id) {
            const pass = document.getElementById(id);
            pass.type = pass.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
