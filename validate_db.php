<?php
/**
 * NutriPlan Database Validation Script
 * Validates database structure and seeded data
 */

$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'meal_planning_db';

// Color codes for terminal output
$green = "\033[92m";
$red = "\033[91m";
$yellow = "\033[93m";
$blue = "\033[94m";
$reset = "\033[0m";

echo "{$blue}═══════════════════════════════════════════════════════{$reset}\n";
echo "{$blue}  NutriPlan Database Validation Script{$reset}\n";
echo "{$blue}═══════════════════════════════════════════════════════{$reset}\n\n";

// Connect to MySQL
$conn = new mysqli($db_host, $db_user, $db_password);

if ($conn->connect_error) {
    echo "{$red}✗ Connection failed: {$conn->connect_error}{$reset}\n";
    exit(1);
}

echo "{$green}✓ Connected to MySQL{$reset}\n";

// Check if database exists
$db_check = $conn->query("SHOW DATABASES LIKE '$db_name'");
if ($db_check->num_rows === 0) {
    echo "{$red}✗ Database '$db_name' not found!{$reset}\n";
    exit(1);
}
echo "{$green}✓ Database '$db_name' exists{$reset}\n";

// Select database
$conn->select_db($db_name);

// Expected tables
$expected_tables = ['users', 'categories', 'meals', 'nutrition', 'shopping_lists', 'shopping_items'];

// Get existing tables
$tables_result = $conn->query("SHOW TABLES");
$existing_tables = [];
while ($row = $tables_result->fetch_row()) {
    $existing_tables[] = $row[0];
}

echo "\n{$blue}━━━ TABLE VALIDATION ━━━{$reset}\n";
$missing_tables = array_diff($expected_tables, $existing_tables);
if (empty($missing_tables)) {
    echo "{$green}✓ All 6 tables exist{$reset}\n";
    foreach ($expected_tables as $table) {
        echo "  {$green}✓{$reset} $table\n";
    }
} else {
    echo "{$red}✗ Missing tables:{$reset}\n";
    foreach ($missing_tables as $table) {
        echo "  {$red}✗{$reset} $table\n";
    }
}

// Validate Categories
echo "\n{$blue}━━━ CATEGORIES VALIDATION ━━━{$reset}\n";
$cat_result = $conn->query("SELECT COUNT(*) as count FROM categories");
$cat_row = $cat_result->fetch_assoc();
$cat_count = $cat_row['count'];

if ($cat_count === 3) {
    echo "{$green}✓ All 3 categories exist{$reset}\n";
    $cats = $conn->query("SELECT category_id, category_name, category_icon FROM categories");
    while ($cat = $cats->fetch_assoc()) {
        echo "  {$green}✓{$reset} ID:{$cat['category_id']} | {$cat['category_name']} {$cat['category_icon']}\n";
    }
} else {
    echo "{$red}✗ Expected 3 categories, found: $cat_count{$reset}\n";
}

// Validate Meals
echo "\n{$blue}━━━ MEALS VALIDATION ━━━{$reset}\n";
$meal_result = $conn->query("SELECT COUNT(*) as count FROM meals");
$meal_row = $meal_result->fetch_assoc();
$meal_count = $meal_row['count'];

if ($meal_count === 22) {
    echo "{$green}✓ All 22 meals exist{$reset}\n";
    
    // Show meals by category
    $categories = ['Breakfast', 'Lunch', 'Supper'];
    foreach ($categories as $cat_name) {
        $meals = $conn->query("
            SELECT m.meal_id, m.meal_name, m.meal_icon, m.preparation_time 
            FROM meals m 
            JOIN categories c ON m.category_id = c.category_id 
            WHERE c.category_name = '$cat_name'
            ORDER BY m.meal_id
        ");
        $cat_meal_count = $meals->num_rows;
        echo "  {$blue}$cat_name ($cat_meal_count meals):{$reset}\n";
        
        while ($meal = $meals->fetch_assoc()) {
            echo "    {$green}✓{$reset} {$meal['meal_icon']} {$meal['meal_name']} ({$meal['preparation_time']} min)\n";
        }
    }
} else {
    echo "{$red}✗ Expected 22 meals, found: $meal_count{$reset}\n";
    
    // Show what we do have
    $meals = $conn->query("SELECT meal_id, meal_name FROM meals LIMIT 10");
    echo "  Sample meals found:\n";
    while ($meal = $meals->fetch_assoc()) {
        echo "    {$yellow}→{$reset} {$meal['meal_name']}\n";
    }
}

// Validate Nutrition Data
echo "\n{$blue}━━━ NUTRITION DATA VALIDATION ━━━{$reset}\n";
$nut_result = $conn->query("SELECT COUNT(*) as count FROM nutrition");
$nut_row = $nut_result->fetch_assoc();
$nut_count = $nut_row['count'];

if ($nut_count === 22) {
    echo "{$green}✓ Nutrition data for all 22 meals{$reset}\n";
    
    // Show sample nutrition data
    $sample = $conn->query("
        SELECT m.meal_name, n.calories, n.proteins_g, n.carbs_g, n.fats_g 
        FROM nutrition n 
        JOIN meals m ON n.meal_id = m.meal_id 
        LIMIT 5
    ");
    echo "  Sample nutrition data:\n";
    while ($nut = $sample->fetch_assoc()) {
        echo "    {$green}✓{$reset} {$nut['meal_name']}: {$nut['calories']} cal, {$nut['proteins_g']}g protein, {$nut['carbs_g']}g carbs, {$nut['fats_g']}g fat\n";
    }
} else {
    echo "{$red}✗ Expected 22 nutrition entries, found: $nut_count{$reset}\n";
}

// Validate Data Integrity
echo "\n{$blue}━━━ DATA INTEGRITY CHECK ━━━{$reset}\n";

// Check for meals without nutrition
$orphan_meals = $conn->query("
    SELECT m.meal_id, m.meal_name 
    FROM meals m 
    LEFT JOIN nutrition n ON m.meal_id = n.meal_id 
    WHERE n.meal_id IS NULL
");

if ($orphan_meals->num_rows === 0) {
    echo "{$green}✓ All meals have nutrition data{$reset}\n";
} else {
    echo "{$red}✗ Found meals without nutrition data:{$reset}\n";
    while ($orphan = $orphan_meals->fetch_assoc()) {
        echo "  {$red}✗{$reset} Meal ID {$orphan['meal_id']}: {$orphan['meal_name']}\n";
    }
}

// Check for meals without category
$invalid_meals = $conn->query("
    SELECT m.meal_id, m.meal_name, m.category_id 
    FROM meals m 
    LEFT JOIN categories c ON m.category_id = c.category_id 
    WHERE c.category_id IS NULL
");

if ($invalid_meals->num_rows === 0) {
    echo "{$green}✓ All meals have valid categories{$reset}\n";
} else {
    echo "{$red}✗ Found meals with invalid categories:{$reset}\n";
    while ($invalid = $invalid_meals->fetch_assoc()) {
        echo "  {$red}✗{$reset} Meal ID {$invalid['meal_id']}: {$invalid['meal_name']} (category {$invalid['category_id']})\n";
    }
}

// Summary Statistics
echo "\n{$blue}━━━ SUMMARY STATISTICS ━━━{$reset}\n";
echo "  Database: {$green}$db_name{$reset}\n";
echo "  Tables: {$green}" . count($existing_tables) . "{$reset}\n";
echo "  Categories: {$green}$cat_count{$reset}\n";
echo "  Meals: {$green}$meal_count{$reset}\n";
echo "  Nutrition records: {$green}$nut_count{$reset}\n";

// Users validation
$users_result = $conn->query("SELECT COUNT(*) as count FROM users");
$users_row = $users_result->fetch_assoc();
echo "  Registered users: {$green}" . $users_row['count'] . "{$reset}\n";

// Shopping lists validation
$shop_result = $conn->query("SELECT COUNT(*) as count FROM shopping_lists");
$shop_row = $shop_result->fetch_assoc();
echo "  Shopping lists: {$green}" . $shop_row['count'] . "{$reset}\n";

// Final Result
echo "\n{$blue}═══════════════════════════════════════════════════════{$reset}\n";

if ($cat_count === 3 && $meal_count === 22 && $nut_count === 22 && $orphan_meals->num_rows === 0) {
    echo "{$green}✓✓✓ DATABASE VALIDATION SUCCESSFUL ✓✓✓{$reset}\n";
    echo "{$green}All data is properly seeded and validated!{$reset}\n";
} else {
    echo "{$yellow}⚠ DATABASE VALIDATION INCOMPLETE{$reset}\n";
    echo "{$yellow}Some data may be missing. Please check the issues above.{$reset}\n";
}

echo "{$blue}═══════════════════════════════════════════════════════{$reset}\n";

$conn->close();
?>
