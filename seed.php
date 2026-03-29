<?php
// PDO-based Seed database with tables and sample data
// Run this script once, then delete from production

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

$opts = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Connect without specifying a database to allow create/drop
    $dsn_no_db = "mysql:host={$db_host};charset={$db_charset}";
    $pdo = new PDO($dsn_no_db, $db_user, $db_password, $opts);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    // Drop and create database
    $pdo->exec("DROP DATABASE IF EXISTS `{$db_name}`");
    echo "Database dropped (if existed)\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET {$db_charset} COLLATE {$db_charset}_general_ci");
    echo "Database created or already exists: {$db_name}\n";
} catch (PDOException $e) {
    echo "Error creating database: " . $e->getMessage() . "\n";
    exit(1);
}

// Connect to the newly created database
try {
    $dsn_db = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";
    $pdo = new PDO($dsn_db, $db_user, $db_password, $opts);
} catch (PDOException $e) {
    echo "Connection to database failed: " . $e->getMessage() . "\n";
    exit(1);
}

function execSql(PDO $pdo, string $sql, string $message = '') {
    try {
        $pdo->exec($sql);
        if ($message) echo $message . "\n";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Create tables
execSql($pdo, "CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    avatar_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)", "USERS table created or exists");

execSql($pdo, "CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    category_icon VARCHAR(50)
)", "CATEGORIES table created or exists");

execSql($pdo, "CREATE TABLE IF NOT EXISTS meals (
    meal_id INT AUTO_INCREMENT PRIMARY KEY,
    meal_name VARCHAR(100) NOT NULL,
    category_id INT NOT NULL,
    description TEXT,
    preparation_time INT,
    meal_icon VARCHAR(50),
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
)", "MEALS table created or exists");

execSql($pdo, "CREATE TABLE IF NOT EXISTS nutrition (
    nutrition_id INT AUTO_INCREMENT PRIMARY KEY,
    meal_id INT NOT NULL,
    calories INT,
    proteins_g DECIMAL(10,2),
    carbs_g DECIMAL(10,2),
    fats_g DECIMAL(10,2),
    fiber_g DECIMAL(10,2),
    iron_mg DECIMAL(10,2),
    calcium_mg DECIMAL(10,2),
    vitamins VARCHAR(200),
    FOREIGN KEY (meal_id) REFERENCES meals(meal_id) ON DELETE CASCADE
)", "NUTRITION table created or exists");

execSql($pdo, "CREATE TABLE IF NOT EXISTS shopping_lists (
    list_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    list_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)", "SHOPPING_LISTS table created or exists");

execSql($pdo, "CREATE TABLE IF NOT EXISTS shopping_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    list_id INT NOT NULL,
    meal_id INT,
    item_name VARCHAR(100),
    quantity VARCHAR(50),
    purchased BOOLEAN DEFAULT FALSE,
    custom_item BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (list_id) REFERENCES shopping_lists(list_id) ON DELETE CASCADE,
    FOREIGN KEY (meal_id) REFERENCES meals(meal_id)
)", "SHOPPING_ITEMS table created or exists");

execSql($pdo, "CREATE TABLE IF NOT EXISTS user_preferences (
    preference_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    portion_size VARCHAR(50) DEFAULT 'normal',
    dietary_restrictions TEXT,
    allergies TEXT,
    preferred_cuisine TEXT,
    notifications_enabled BOOLEAN DEFAULT TRUE,
    theme_preference VARCHAR(10) DEFAULT 'dark',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)", "USER_PREFERENCES table created or exists");

execSql($pdo, "CREATE TABLE IF NOT EXISTS meal_ratings (
    rating_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    meal_id INT NOT NULL,
    rating INT,
    review TEXT,
    is_favorite BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_meal (user_id, meal_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (meal_id) REFERENCES meals(meal_id) ON DELETE CASCADE
)", "MEAL_RATINGS table created or exists");

execSql($pdo, "CREATE TABLE IF NOT EXISTS meal_sources (
    source_id INT AUTO_INCREMENT PRIMARY KEY,
    meal_id INT NOT NULL,
    recipe_url VARCHAR(500),
    source_name VARCHAR(100),
    source_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meal_id) REFERENCES meals(meal_id) ON DELETE CASCADE
)", "MEAL_SOURCES table created or exists");

// Insert categories
$categories = [
    ['Breakfast', 'croissant'],
    ['Lunch', 'plate-with-cutlery'],
    ['Supper', 'fork-and-knife-with-plate']
];

$stmtCat = $pdo->prepare("INSERT IGNORE INTO categories (category_name, category_icon) VALUES (?, ?)");
foreach ($categories as $cat) {
    $stmtCat->execute([$cat[0], $cat[1]]);
}

// Category ID mapping (will reflect autoincrement ids)
$category_map = [];
$rows = $pdo->query("SELECT category_id, category_name FROM categories")->fetchAll();
foreach ($rows as $r) {
    $category_map[$r['category_name']] = (int)$r['category_id'];
}

// Insert meals
$meals = [
    ['Ugali & Sukuma', 'Breakfast', 'Ground maize served with sautéed greens', 30, 'egg'],
    ['Mandazi', 'Breakfast', 'Deep-fried dough pastry', 15, 'fried-egg'],
    ['Chapati & Beans', 'Breakfast', 'Flatbread with bean stew', 40, 'croissant'],
    ['Oatmeal with Fruits', 'Breakfast', 'Oats topped with berries', 20, 'bowl-with-spoon'],
    ['Eggs & Toast', 'Breakfast', 'Scrambled eggs with buttered toast', 25, 'cooking'],
    ['Ugali & Nyama', 'Lunch', 'Maize meal with grilled meat', 45, 'meat-on-bone'],
    ['Rice & Stew', 'Lunch', 'Fragrant rice with vegetable stew', 50, 'rice-bowl'],
    ['Githeri', 'Lunch', 'Corn and beans in tomato gravy', 35, 'corn'],
    ['Samosa & Chai', 'Lunch', 'Pastry with spiced filling', 20, 'dumpling'],
    ['Lentil Soup', 'Lunch', 'Protein-packed legume soup', 40, 'pot-of-food'],
    ['Nyama Choma', 'Supper', 'Grilled meat with spices', 60, 'meat-on-bone'],
    ['Vegetable Curry', 'Supper', 'Mixed vegetables in coconut curry', 45, 'curry-rice'],
    ['Fish & Chips', 'Supper', 'Crispy fish with potato fries', 50, 'fish-cake'],
    ['Ugali & Greens', 'Supper', 'Maize meal with cooked vegetables', 35, 'leafy-green'],
    ['Pilau', 'Supper', 'Spiced rice with meat and spices', 55, 'rice-bowl'],
    ['Kachumbari Salad', 'Lunch', 'Fresh tomato and onion salad', 15, 'green-salad'],
    ['Muamba Stew', 'Lunch', 'Peanut-based vegetable stew', 50, 'pot-of-food'],
    ['Matoke', 'Supper', 'Steamed banana with sauce', 40, 'banana'],
    ['Sukuma Wiki Fry', 'Breakfast', 'Sautéed collard greens', 20, 'leafy-green'],
    ['Bean Soup', 'Supper', 'Hearty bean and vegetable soup', 45, 'pot-of-food'],
    ['Coconut Rice', 'Lunch', 'Rice cooked in coconut milk', 50, 'coconut'],
    ['Roasted Chicken', 'Supper', 'Herb-roasted chicken pieces', 60, 'poultry-leg']
];

$stmtMeal = $pdo->prepare("INSERT IGNORE INTO meals (meal_name, category_id, description, preparation_time, meal_icon) VALUES (?, ?, ?, ?, ?)");
foreach ($meals as $meal) {
    $cat_id = $category_map[$meal[1]] ?? 1;
    $stmtMeal->execute([$meal[0], $cat_id, $meal[2], $meal[3], $meal[4]]);
}

// Create test user for E2E
$testEmail = 'test@user.io';
$testPassword = 'Password123!';
$testName = 'E2E';
$stmtFindUser = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
$stmtFindUser->execute([$testEmail]);
$exists = $stmtFindUser->fetch();
if (!$exists) {
    $hash = password_hash($testPassword, PASSWORD_DEFAULT);
    $stmtUser = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
    $stmtUser->execute(['testuser', $testEmail, $hash, $testName, 'User']);
    echo "Test user created: {$testEmail}\n";
} else {
    echo "Test user already exists: {$testEmail}\n";
}

// Nutrition data (simplified insertion)
$nutrition_data = [
    ['Ugali & Sukuma', 250, 8, 52, 4, 5, 2.5, 150, 'Iron, Vitamin A'],
    ['Mandazi', 320, 6, 45, 14, 3, 1.2, 80, 'Minimal'],
    ['Chapati & Beans', 380, 14, 52, 10, 8, 4, 200, 'Iron, Folate'],
    ['Oatmeal with Fruits', 280, 10, 48, 6, 8, 2.5, 300, 'Vitamin C, Fiber'],
    ['Eggs & Toast', 350, 15, 38, 15, 4, 1.5, 180, 'Vitamin B12, Choline'],
    ['Ugali & Nyama', 520, 28, 55, 18, 6, 3, 180, 'Iron, Zinc'],
    ['Rice & Stew', 480, 12, 68, 12, 5, 1.8, 120, 'Folate, Vitamin A'],
    ['Githeri', 320, 10, 52, 4, 12, 2.8, 140, 'Iron, Folate'],
    ['Samosa & Chai', 280, 6, 35, 12, 4, 1.2, 90, 'Minimal'],
    ['Lentil Soup', 240, 16, 38, 2, 10, 4.5, 220, 'Iron, Folate'],
    ['Nyama Choma', 580, 48, 0, 42, 0, 4.2, 250, 'Zinc, Iron'],
    ['Vegetable Curry', 320, 8, 28, 18, 5, 2.5, 140, 'Vitamin A, Potassium'],
    ['Fish & Chips', 450, 32, 42, 18, 2, 2, 160, 'Omega-3, Vitamin D'],
    ['Ugali & Greens', 280, 6, 48, 3, 8, 2.8, 120, 'Iron, Calcium'],
    ['Pilau', 520, 18, 72, 16, 4, 2.2, 130, 'Iron, B Vitamins'],
    ['Kachumbari Salad', 45, 1, 8, 0.5, 1.5, 0.2, 25, 'Vitamin C'],
    ['Muamba Stew', 340, 8, 32, 18, 6, 3.2, 140, 'Vitamin E, Folate'],
    ['Matoke', 280, 4, 58, 1, 3.5, 0.5, 80, 'Potassium, Vitamin B6'],
    ['Sukuma Wiki Fry', 120, 4, 18, 2, 4, 1.8, 200, 'Calcium, Iron'],
    ['Bean Soup', 220, 12, 38, 1, 12, 3.2, 150, 'Iron, Fiber'],
    ['Coconut Rice', 480, 8, 68, 18, 2, 1.5, 100, 'Manganese'],
    ['Roasted Chicken', 480, 52, 0, 24, 0, 1.2, 180, 'Zinc, Selenium']
];

$stmtFindMeal = $pdo->prepare("SELECT meal_id FROM meals WHERE meal_name = ? LIMIT 1");
$stmtInsNut = $pdo->prepare("INSERT IGNORE INTO nutrition (meal_id, calories, proteins_g, carbs_g, fats_g, fiber_g, iron_mg, calcium_mg, vitamins) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($nutrition_data as $nut) {
    $stmtFindMeal->execute([$nut[0]]);
    $meal_row = $stmtFindMeal->fetch();
    if ($meal_row) {
        $meal_id = (int)$meal_row['meal_id'];
        $stmtInsNut->execute([$meal_id, $nut[1], $nut[2], $nut[3], $nut[4], $nut[5], $nut[6], $nut[7], $nut[8]]);
    }
}

// Meal sources
$meal_sources = [
    ['Ugali & Sukuma', 'https://www.allrecipes.com/recipe/228956/ugali-with-collard-greens/', 'AllRecipes', 'Recipe Blog'],
    ['Mandazi', 'https://www.jamieoliver.com/recipes/bread-recipes/mandazi/', 'Jamie Oliver', 'Chef Website'],
    ['Chapati & Beans', 'https://cafedelites.com/chapati-recipe/', 'Cafe Delites', 'Recipe Blog'],
    ['Oatmeal with Fruits', 'https://www.foodnetwork.com/recipes/oatmeal-with-fruit', 'Food Network', 'TV Network'],
    ['Eggs & Toast', 'https://www.bbcgoodfood.com/recipes/scrambled-eggs-and-toast', 'BBC Good Food', 'News Site']
    // truncated for brevity in seeder; add more if needed
];

$stmtInsSource = $pdo->prepare("INSERT IGNORE INTO meal_sources (meal_id, recipe_url, source_name, source_type) VALUES (?, ?, ?, ?)");
foreach ($meal_sources as $source) {
    $stmtFindMeal->execute([$source[0]]);
    $meal_row = $stmtFindMeal->fetch();
    if ($meal_row) {
        $meal_id = (int)$meal_row['meal_id'];
        $stmtInsSource->execute([$meal_id, $source[1], $source[2], $source[3]]);
    }
}

echo "Database seeded successfully.\n";
echo "Back to Home: ../\n";

?>
