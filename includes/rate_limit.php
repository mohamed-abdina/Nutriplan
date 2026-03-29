<?php
// Rate limiting middleware - Redis-backed when available, otherwise session-backed
// Usage: $limiter->check_rate_limit('api_endpoint_name', $max_requests, $window_seconds)

require_once __DIR__ . '/session.php';
secure_session_start();

// Try to load Predis if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

class RateLimiter {
    private $limit_prefix = 'rate_limit:';
    private $redis = null;

    public function __construct() {
        // Prefer Redis if REDIS_URL is set and Predis is installed
        $redis_url = $_ENV['REDIS_URL'] ?? ($_ENV['REDIS'] ?? getenv('REDIS_URL'));
        if ($redis_url && class_exists('Predis\Client')) {
            try {
                $this->redis = new Predis\Client($redis_url);
                // Test connection
                $this->redis->ping();
            } catch (Exception $e) {
                error_log('Redis not available for rate limiting: ' . $e->getMessage());
                $this->redis = null;
            }
        }
    }

    public function check_rate_limit($endpoint, $max_requests = 10, $window_seconds = 60) {
        $client_id = $this->get_client_id();
        $key = $this->limit_prefix . $endpoint . ':' . $client_id;
        $now = time();
        $window_start = $now - $window_seconds;

        if ($this->redis) {
            try {
                // Remove old timestamps
                $this->redis->zremrangebyscore($key, 0, $window_start);
                $count = $this->redis->zcard($key);
                if ($count >= $max_requests) {
                    error_log("Rate limit exceeded for {$endpoint}:{$client_id} - {$count} requests in {$window_seconds}s");
                    return false;
                }
                // Add current timestamp
                $this->redis->zadd($key, [$now => $now]);
                $this->redis->expire($key, $window_seconds + 5);
                return true;
            } catch (Exception $e) {
                error_log('Redis error in rate limiter: ' . $e->getMessage());
                // Fall through to session fallback
            }
        }

        // Session-based fallback
        if (!isset($_SESSION[$this->limit_prefix])) {
            $_SESSION[$this->limit_prefix] = [];
        }

        if (!isset($_SESSION[$this->limit_prefix][$key])) {
            $_SESSION[$this->limit_prefix][$key] = [];
        }

        // Prune old entries
        $_SESSION[$this->limit_prefix][$key] = array_filter(
            $_SESSION[$this->limit_prefix][$key],
            function($timestamp) use ($window_start) { return $timestamp > $window_start; }
        );

        $count = count($_SESSION[$this->limit_prefix][$key]);
        if ($count >= $max_requests) {
            error_log("Rate limit exceeded (session) for {$endpoint}:{$client_id} - {$count} requests in {$window_seconds}s");
            return false;
        }

        $_SESSION[$this->limit_prefix][$key][] = $now;
        return true;
    }

    private function get_client_id() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return md5($ip . $ua);
    }

    public function get_remaining_requests($endpoint, $max_requests = 10, $window_seconds = 60) {
        $client_id = $this->get_client_id();
        $key = $this->limit_prefix . $endpoint . ':' . $client_id;
        $now = time();
        $window_start = $now - $window_seconds;

        if ($this->redis) {
            try {
                $this->redis->zremrangebyscore($key, 0, $window_start);
                $count = $this->redis->zcard($key);
                return max(0, $max_requests - $count);
            } catch (Exception $e) {
                // fallback
            }
        }

        if (!isset($_SESSION[$this->limit_prefix][$key])) return $max_requests;
        $valid = array_filter($_SESSION[$this->limit_prefix][$key], function($ts) use ($window_start) { return $ts > $window_start; });
        return max(0, $max_requests - count($valid));
    }
}

// Create global instance
$limiter = new RateLimiter();
?>
