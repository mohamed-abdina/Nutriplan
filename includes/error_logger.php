<?php
// Error logging and monitoring utility
// Provides centralized error logging for the application

class ErrorLogger {
    private $log_dir;
    private $log_file;
    private $error_threshold = 100; // Alert if errors exceed this in an hour

    public function __construct($log_dir = null) {
        // Default to project root logs directory for reliable path inside containers
        if ($log_dir === null) {
            $projectRoot = realpath(__DIR__ . '/..') ?: __DIR__ . '/..';
            $log_dir = $projectRoot;
        }

        $this->log_dir = rtrim($log_dir, '\/');
        $this->log_dir .= '/logs';
        $this->ensure_log_directory();
        $this->log_file = $this->log_dir . '/app_' . date('Y-m-d') . '.log';
    }
    
    private function ensure_log_directory() {
        if (!is_dir($this->log_dir)) {
            // Attempt to create with permissive permissions so web server user can write
            mkdir($this->log_dir, 0777, true);
            @chmod($this->log_dir, 0777);
        }
        // If still not writable, try to adjust ownership when possible
        if (!is_writable($this->log_dir)) {
            @chmod($this->log_dir, 0777);
            // If chmod didn't work (e.g., mounted volume with restricted perms),
            // fall back to system temp directory so the app can still log.
            $fallback = sys_get_temp_dir() . '/nutriplan_logs';
            if (!is_dir($fallback)) {
                @mkdir($fallback, 0777, true);
                @chmod($fallback, 0777);
            }
            if (is_writable($fallback)) {
                $this->log_dir = $fallback;
                $this->log_file = $this->log_dir . '/app_' . date('Y-m-d') . '.log';
                error_log("[NOTICE] Log directory not writable; using fallback: {$fallback}");
                return;
            }
        }
    }
    
    public function log_error($errno, $errstr, $errfile, $errline, $context = []) {
        $error_type = $this->get_error_type($errno);
        $timestamp = date('Y-m-d H:i:s');
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'anonymous';
        
        $log_entry = json_encode([
            'timestamp' => $timestamp,
            'type' => $error_type,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'ip' => $client_ip,
            'user_id' => $user_id,
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'context' => $context
        ], JSON_UNESCAPED_SLASHES);
        
        $written = @file_put_contents($this->log_file, $log_entry . PHP_EOL, FILE_APPEND);
        if ($written === false) {
            // Fallback: write to system error log if file write fails
            error_log("[{$error_type}] Failed to write app log; entry: {$log_entry}");
        }
        
        // Log to system error log as well
        error_log("[{$error_type}] {$errstr} in {$errfile}:{$errline}");
        
        // Check for alert conditions
        $this->check_error_threshold();
        
        return true;
    }
    
    public function log_api_call($endpoint, $method, $params = [], $response_status = 200) {
        $timestamp = date('Y-m-d H:i:s');
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'anonymous';
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $log_entry = json_encode([
            'timestamp' => $timestamp,
            'type' => 'API_CALL',
            'endpoint' => $endpoint,
            'method' => $method,
            'params' => $params,
            'status' => $response_status,
            'user_id' => $user_id,
            'ip' => $client_ip
        ], JSON_UNESCAPED_SLASHES);
        
        $written = @file_put_contents($this->log_file, $log_entry . PHP_EOL, FILE_APPEND);
        if ($written === false) {
            error_log("[API_CALL] Failed to write app log; entry: {$log_entry}");
        }
    }
    
    public function log_database_query($query, $execution_time = null, $affected_rows = 0) {
        $timestamp = date('Y-m-d H:i:s');
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'anonymous';
        
        $log_entry = json_encode([
            'timestamp' => $timestamp,
            'type' => 'DB_QUERY',
            'query' => substr($query, 0, 500), // Log first 500 chars only
            'execution_time_ms' => $execution_time,
            'affected_rows' => $affected_rows,
            'user_id' => $user_id
        ], JSON_UNESCAPED_SLASHES);
        
        $written = @file_put_contents($this->log_file, $log_entry . PHP_EOL, FILE_APPEND);
        if ($written === false) {
            error_log("[DB_QUERY] Failed to write app log; entry: {$log_entry}");
        }
    }
    
    public function log_security_event($event_type, $details = []) {
        $timestamp = date('Y-m-d H:i:s');
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'anonymous';
        
        $log_entry = json_encode([
            'timestamp' => $timestamp,
            'type' => 'SECURITY_EVENT',
            'event' => $event_type,
            'details' => $details,
            'ip' => $client_ip,
            'user_id' => $user_id,
            'url' => $_SERVER['REQUEST_URI'] ?? ''
        ], JSON_UNESCAPED_SLASHES);
        
        $written = @file_put_contents($this->log_file, $log_entry . PHP_EOL, FILE_APPEND);
        if ($written === false) {
            error_log("[SECURITY] Failed to write app log; entry: {$log_entry}");
        }
        error_log("[SECURITY] $event_type from $client_ip");
    }
    
    private function get_error_type($errno) {
        $error_types = [
            E_ERROR => 'FATAL_ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE_ERROR',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE_ERROR',
            E_CORE_WARNING => 'CORE_WARNING',
            E_COMPILE_ERROR => 'COMPILE_ERROR',
            E_COMPILE_WARNING => 'COMPILE_WARNING',
            E_USER_ERROR => 'USER_ERROR',
            E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE',
            E_STRICT => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER_DEPRECATED'
        ];
        
        return $error_types[$errno] ?? 'UNKNOWN_ERROR';
    }
    
    private function check_error_threshold() {
        $one_hour_ago = time() - 3600;
        $recent_errors = 0;
        
        if (file_exists($this->log_file)) {
            $lines = file($this->log_file, FILE_IGNORE_NEW_LINES);
            foreach ($lines as $line) {
                $log = json_decode($line, true);
                if (isset($log['timestamp'])) {
                    $log_time = strtotime($log['timestamp']);
                    if ($log_time > $one_hour_ago && strpos($log['type'], 'ERROR') !== false) {
                        $recent_errors++;
                    }
                }
            }
        }
        
        if ($recent_errors > $this->error_threshold) {
            error_log("[ALERT] Error threshold exceeded: $recent_errors errors in last hour");
        }
    }
    
    public function get_error_report($hours = 24) {
        $cutoff_time = time() - ($hours * 3600);
        $errors = [];
        
        if (file_exists($this->log_file)) {
            $lines = file($this->log_file, FILE_IGNORE_NEW_LINES);
            foreach ($lines as $line) {
                $log = json_decode($line, true);
                if (isset($log['timestamp'])) {
                    $log_time = strtotime($log['timestamp']);
                    if ($log_time > $cutoff_time) {
                        $errors[] = $log;
                    }
                }
            }
        }
        
        return $errors;
    }
}

// Initialize global error logger
$error_logger = new ErrorLogger();

// Set up error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    global $error_logger;
    if ($error_logger instanceof ErrorLogger) {
        $error_logger->log_error($errno, $errstr, $errfile, $errline);
    }
    // Return false to use default PHP error handler
    return false;
});

// Set up exception handler
set_exception_handler(function($exception) {
    global $error_logger;
    if ($error_logger instanceof ErrorLogger) {
        $error_logger->log_error(
            E_USER_ERROR,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            ['trace' => $exception->getTraceAsString()]
        );
    }

    if (PHP_SAPI !== 'cli') {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
    exit;
});
?>
