<?php
// Database connection (PDO) and helper wrappers
require_once __DIR__ . '/../vendor/autoload.php';

// Load .env if present
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}
$require_functions = __DIR__ . '/functions.php';
if (file_exists($require_functions)) {
    require_once $require_functions;
    // Initialize error logger so DB connection failures are recorded with context
    if (file_exists(__DIR__ . '/error_logger.php')) {
        require_once __DIR__ . '/error_logger.php';
    }
}

$db_host = function_exists('get_db_host') ? get_db_host() : ($_ENV['MYSQL_HOST'] ?? ($_ENV['DB_HOST'] ?? 'db'));

// If running inside Docker (detect /.dockerenv) and the env points to localhost,
// prefer the Docker service name `db` so the web container connects to the DB container.
if (file_exists('/.dockerenv') && ($db_host === '127.0.0.1' || strtolower($db_host) === 'localhost')) {
    $db_host = 'db';
}

// If the host is the Docker service name `db` but it doesn't resolve (not in Docker),
// fall back to localhost for local development.
if ($db_host === 'db' && gethostbyname('db') === 'db') {
    $db_host = '127.0.0.1';
}

$db_user = $_ENV['MYSQL_USER'] ?? ($_ENV['DB_USER'] ?? 'root');
$db_password = $_ENV['MYSQL_PASSWORD'] ?? ($_ENV['DB_PASSWORD'] ?? '');
$db_name = $_ENV['MYSQL_DATABASE'] ?? ($_ENV['DB_NAME'] ?? 'meal_planning_db');
$db_charset = 'utf8mb4';

$dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";

try {
    $pdo = new PDO($dsn, $db_user, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        // Set a short timeout for connection attempts in development to avoid long hangs
        PDO::ATTR_TIMEOUT => 5,
    ]);
} catch (PDOException $e) {
    // Log detailed DB connection failure with context (do not log passwords)
    if (isset($error_logger)) {
        $context = [
            'dsn' => $dsn,
            'db_host' => $db_host,
            'db_name' => $db_name,
            'db_user' => $db_user,
            'env' => [
                'MYSQL_HOST' => $_ENV['MYSQL_HOST'] ?? null,
                'DB_HOST' => $_ENV['DB_HOST'] ?? null,
            ]
        ];
        $error_logger->log_error(E_USER_ERROR, 'PDO Connection failed: ' . $e->getMessage(), __FILE__, __LINE__, $context);
    } else {
        error_log('PDO Connection failed: ' . $e->getMessage());
    }

    http_response_code(500);
    exit('Database connection error');
}

// Backwards-compatible alias for existing code that references $conn
$conn = $pdo;

// Sanitize input for non-SQL usage (use prepared statements for queries)
function sanitize_input($data) {
    $data = trim($data);
    return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Helper: execute a prepared query and return statement
function pdo_query($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log('Query Error: ' . $e->getMessage() . ' -- SQL: ' . $sql);
        return false;
    }
}

// Helper: fetch all rows
function pdo_fetch_all($sql, $params = []) {
    $stmt = pdo_query($sql, $params);
    if ($stmt === false) return false;
    return $stmt->fetchAll();
}

// Helper: fetch single row
function pdo_fetch_one($sql, $params = []) {
    $stmt = pdo_query($sql, $params);
    if ($stmt === false) return false;
    return $stmt->fetch();
}

// Return the PDO instance for direct use
function get_db() {
    global $pdo;
    return $pdo;
}

?>