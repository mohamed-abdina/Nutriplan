<?php
/**
 * NutriPlan Database Validation Script (PDO)
 * Validates database structure and seeded data
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load .env if present
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}
$functions_path = __DIR__ . '/includes/functions.php';
if (file_exists($functions_path)) require_once $functions_path;

$db_host = function_exists('get_db_host') ? get_db_host() : ($_ENV['MYSQL_HOST'] ?? ($_ENV['DB_HOST'] ?? 'db'));
$db_user = $_ENV['MYSQL_USER'] ?? ($_ENV['DB_USER'] ?? 'root');
$db_password = $_ENV['MYSQL_PASSWORD'] ?? ($_ENV['DB_PASSWORD'] ?? '');
$db_name = $_ENV['MYSQL_DATABASE'] ?? ($_ENV['DB_NAME'] ?? 'meal_planning_db');
$db_charset = 'utf8mb4';

$green = "\033[92m";
$red = "\033[91m";
$yellow = "\033[93m";
$blue = "\033[94m";
$reset = "\033[0m";

echo "{$blue}═══════════════════════════════════════════════════════{$reset}\n";
echo "{$blue}  NutriPlan Database Validation Script (PDO){$reset}\n";
echo "{$blue}═══════════════════════════════════════════════════════{$reset}\n\n";

$opts = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Connect without a database to check for DB existence
    $dsn_no_db = "mysql:host={$db_host};charset={$db_charset}";
    $pdo = new PDO($dsn_no_db, $db_user, $db_password, $opts);
    echo "{$green}✓ Connected to MySQL host{$reset}\n";
} catch (PDOException $e) {
    echo "{$red}✗ Connection failed: {$e->getMessage()}{$reset}\n";
    exit(1);
}

// Check if database exists
try {
    $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
    $stmt->execute([$db_name]);
    $db_exists = (bool)$stmt->fetch();
    if (!$db_exists) {
        echo "{$red}✗ Database '{$db_name}' not found!{$reset}\n";
        exit(1);
    }
    echo "{$green}✓ Database '{$db_name}' exists{$reset}\n";
} catch (PDOException $e) {
    echo "{$red}✗ Error checking database: {$e->getMessage()}{$reset}\n";
    exit(1);
}

// Connect to the database
try {
    $dsn_db = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";
    $pdo = new PDO($dsn_db, $db_user, $db_password, $opts);
} catch (PDOException $e) {
    echo "{$red}✗ Connection to database failed: {$e->getMessage()}{$reset}\n";
    exit(1);
}

$expected_tables = ['users', 'categories', 'meals', 'nutrition', 'carts', 'cart_items'];

// Get existing tables
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);
    $existing_tables = array_map(function($r){ return $r[0]; }, $tables);
} catch (PDOException $e) {
    echo "{$red}✗ Error listing tables: {$e->getMessage()}{$reset}\n";
    exit(1);
}

echo "\n{$blue}━━━ TABLE VALIDATION ━━━{$reset}\n";
$missing_tables = array_diff($expected_tables, $existing_tables);
if (empty($missing_tables)) {
    echo "{$green}✓ All expected tables exist{$reset}\n";
    foreach ($expected_tables as $table) {
        echo "  {$green}✓{$reset} $table\n";
    }
} else {
    echo "{$red}✗ Missing tables:{$reset}\n";
    foreach ($missing_tables as $table) {
        echo "  {$red}✗{$reset} $table\n";
    }
}

// Categories validation
echo "\n{$blue}━━━ CATEGORIES VALIDATION ━━━{$reset}\n";
$cat_count = (int)$pdo->query("SELECT COUNT(*) AS c FROM categories")->fetchColumn();
if ($cat_count === 3) {
    echo "{$green}✓ All 3 categories exist{$reset}\n";
    $cats = $pdo->query("SELECT category_id, category_name, category_icon FROM categories")->fetchAll();
    foreach ($cats as $cat) {
        echo "  {$green}✓{$reset} ID:{$cat['category_id']} | {$cat['category_name']} {$cat['category_icon']}\n";
    }
} else {
    echo "{$red}✗ Expected 3 categories, found: $cat_count{$reset}\n";
}

// Meals validation
echo "\n{$blue}━━━ MEALS VALIDATION ━━━{$reset}\n";
$meal_count = (int)$pdo->query("SELECT COUNT(*) AS c FROM meals")->fetchColumn();
if ($meal_count === 22) {
    echo "{$green}✓ All 22 meals exist{$reset}\n";
    $categories = ['Breakfast', 'Lunch', 'Supper'];
    foreach ($categories as $cat_name) {
        $stmt = $pdo->prepare("SELECT m.meal_id, m.meal_name, m.meal_icon, m.preparation_time FROM meals m JOIN categories c ON m.category_id = c.category_id WHERE c.category_name = ? ORDER BY m.meal_id");
        $stmt->execute([$cat_name]);
        $meals = $stmt->fetchAll();
        $cat_meal_count = count($meals);
        echo "  {$blue}$cat_name ($cat_meal_count meals):{$reset}\n";
        foreach ($meals as $meal) {
            echo "    {$green}✓{$reset} {$meal['meal_icon']} {$meal['meal_name']} ({$meal['preparation_time']} min)\n";
        }
    }
} else {
    echo "{$red}✗ Expected 22 meals, found: $meal_count{$reset}\n";
    $sample = $pdo->query("SELECT meal_id, meal_name FROM meals LIMIT 10")->fetchAll();
    echo "  Sample meals found:\n";
    foreach ($sample as $m) {
        echo "    {$yellow}→{$reset} {$m['meal_name']}\n";
    }
}

// Nutrition validation
echo "\n{$blue}━━━ NUTRITION DATA VALIDATION ━━━{$reset}\n";
$nut_count = (int)$pdo->query("SELECT COUNT(*) AS c FROM nutrition")->fetchColumn();
if ($nut_count === 22) {
    echo "{$green}✓ Nutrition data for all 22 meals{$reset}\n";
    $sample = $pdo->query("SELECT m.meal_name, n.calories, n.proteins_g, n.carbs_g, n.fats_g FROM nutrition n JOIN meals m ON n.meal_id = m.meal_id LIMIT 5")->fetchAll();
    echo "  Sample nutrition data:\n";
    foreach ($sample as $nut) {
        echo "    {$green}✓{$reset} {$nut['meal_name']}: {$nut['calories']} cal, {$nut['proteins_g']}g protein, {$nut['carbs_g']}g carbs, {$nut['fats_g']}g fat\n";
    }
} else {
    echo "{$red}✗ Expected 22 nutrition entries, found: $nut_count{$reset}\n";
}

// Data integrity checks
echo "\n{$blue}━━━ DATA INTEGRITY CHECK ━━━{$reset}\n";
$orphan = $pdo->query("SELECT m.meal_id, m.meal_name FROM meals m LEFT JOIN nutrition n ON m.meal_id = n.meal_id WHERE n.meal_id IS NULL")->fetchAll();
if (count($orphan) === 0) {
    echo "{$green}✓ All meals have nutrition data{$reset}\n";
} else {
    echo "{$red}✗ Found meals without nutrition data:{$reset}\n";
    foreach ($orphan as $o) {
        echo "  {$red}✗{$reset} Meal ID {$o['meal_id']}: {$o['meal_name']}\n";
    }
}

$invalid = $pdo->query("SELECT m.meal_id, m.meal_name, m.category_id FROM meals m LEFT JOIN categories c ON m.category_id = c.category_id WHERE c.category_id IS NULL")->fetchAll();
if (count($invalid) === 0) {
    echo "{$green}✓ All meals have valid categories{$reset}\n";
} else {
    echo "{$red}✗ Found meals with invalid categories:{$reset}\n";
    foreach ($invalid as $inv) {
        echo "  {$red}✗{$reset} Meal ID {$inv['meal_id']}: {$inv['meal_name']} (category {$inv['category_id']})\n";
    }
}

// Summary
echo "\n{$blue}━━━ SUMMARY STATISTICS ━━━{$reset}\n";
echo "  Database: {$green}{$db_name}{$reset}\n";
echo "  Tables: {$green}" . count($existing_tables) . "{$reset}\n";
echo "  Categories: {$green}{$cat_count}{$reset}\n";
echo "  Meals: {$green}{$meal_count}{$reset}\n";
echo "  Nutrition records: {$green}{$nut_count}{$reset}\n";

$users_count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
echo "  Registered users: {$green}{$users_count}{$reset}\n";

$lists_count = (int)$pdo->query("SELECT COUNT(*) FROM carts")->fetchColumn();
echo "  Shopping lists: {$green}{$lists_count}{$reset}\n";

echo "\n{$blue}═══════════════════════════════════════════════════════{$reset}\n";

if ($cat_count === 3 && $meal_count === 22 && $nut_count === 22 && count($orphan) === 0) {
    echo "{$green}✓✓✓ DATABASE VALIDATION SUCCESSFUL ✓✓✓{$reset}\n";
} else {
    echo "{$yellow}⚠ DATABASE VALIDATION INCOMPLETE{$reset}\n";
}

?>
