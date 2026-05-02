<?php
// Secure session start helper
function secure_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $isCli = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');

    if (!$isCli && !headers_sent()) {
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_httponly', 1);
        if (PHP_VERSION_ID >= 70300) {
            // session_set_cookie_params accepts array with samesite in PHP 7.3+
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            // Ensure domain does not include a port (cookies must not have ports)
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $cookieDomain = $host ? preg_replace('/:\d+$/', '', $host) : '';

            $cookieParams = [
                'lifetime' => 0,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ];

            if ($cookieDomain !== '') {
                $cookieParams['domain'] = $cookieDomain;
            }

            session_set_cookie_params($cookieParams);
        } else {
            // Fallback for older PHP: set httponly and secure where possible
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            // Fallback: strip port from host for older PHP too
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $cookieDomain = $host ? preg_replace('/:\d+$/', '', $host) : '';
            session_set_cookie_params(0, '/', $cookieDomain, $secure, true);
        }
    }

    if ($isCli && headers_sent()) {
        $started = @session_start([
            'use_cookies' => 0,
            'use_only_cookies' => 0,
            'use_trans_sid' => 0,
            'cache_limiter' => '',
        ]);

        if (!$started && session_status() !== PHP_SESSION_ACTIVE) {
            if (!isset($_SESSION) || !is_array($_SESSION)) {
                $_SESSION = [];
            }
        }
    } else {
        session_start();
    }
    // Regenerate session id periodically
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (session_status() === PHP_SESSION_ACTIVE && time() - $_SESSION['created'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Backwards compatibility function
function start_secure_session() {
    secure_session_start();
}
