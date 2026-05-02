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

        // File-based fallback for rate limiting (IP-based, not session-based)
        // This ensures consistent rate limiting across all requests from the same IP
        $limiter_dir = sys_get_temp_dir() . '/nutriplan_rate_limits';
        if (!is_dir($limiter_dir)) {
            @mkdir($limiter_dir, 0755, true);
        }

        $limiter_file = $limiter_dir . '/' . md5($this->get_ip_only()) . '_' . preg_replace('/[^a-z0-9_-]/', '', $endpoint) . '.json';
        
        // Read existing limits
        $limits = [];
        if (file_exists($limiter_file)) {
            $content = @file_get_contents($limiter_file);
            if ($content) {
                $limits = json_decode($content, true);
                if (!is_array($limits)) $limits = [];
            }
        }

        // Prune old entries
        $limits = array_filter($limits, function($timestamp) use ($window_start) {
            return $timestamp > $window_start;
        });

        $count = count($limits);
        if ($count >= $max_requests) {
            error_log("Rate limit exceeded (IP-based) for {$endpoint}:" . $this->get_ip_only() . " - {$count} requests in {$window_seconds}s");
            return false;
        }

        // Add current request timestamp
        $limits[$now] = $now;
        @file_put_contents($limiter_file, json_encode($limits), LOCK_EX);
        @chmod($limiter_file, 0600);
        
        return true;
    }

    private function get_ip_only() {
        // Get the real IP address, accounting for proxies
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            // Cloudflare
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Take the first IP if there are multiple (leftmost is the client IP)
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            return $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            return $_SERVER['HTTP_FORWARDED'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }

    private function get_client_id() {
        // Use IP address only for rate limiting - makes it truly per-IP
        // and not session-dependent or user-agent dependent
        return md5($this->get_ip_only());
    }

    public function get_remaining_requests($endpoint, $max_requests = 10, $window_seconds = 60) {
        $now = time();
        $window_start = $now - $window_seconds;

        if ($this->redis) {
            try {
                $client_id = $this->get_client_id();
                $key = $this->limit_prefix . $endpoint . ':' . $client_id;
                $this->redis->zremrangebyscore($key, 0, $window_start);
                $count = $this->redis->zcard($key);
                return max(0, $max_requests - $count);
            } catch (Exception $e) {
                // fallback to file-based
            }
        }

        // File-based fallback
        $limiter_dir = sys_get_temp_dir() . '/nutriplan_rate_limits';
        $limiter_file = $limiter_dir . '/' . md5($this->get_ip_only()) . '_' . preg_replace('/[^a-z0-9_-]/', '', $endpoint) . '.json';
        
        if (!file_exists($limiter_file)) {
            return $max_requests;
        }

        $content = @file_get_contents($limiter_file);
        if (!$content) {
            return $max_requests;
        }

        $limits = json_decode($content, true);
        if (!is_array($limits)) {
            return $max_requests;
        }

        $valid = array_filter($limits, function($ts) use ($window_start) {
            return $ts > $window_start;
        });

        return max(0, $max_requests - count($valid));
    }
}

// Create global instance
$limiter = new RateLimiter();
?>
