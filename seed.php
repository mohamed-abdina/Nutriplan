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
    // Create database if it doesn't exist (don't drop, to avoid errors with non-empty directories)
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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

execSql($pdo, "CREATE TABLE IF NOT EXISTS ingredients (
    ingredient_id INT AUTO_INCREMENT PRIMARY KEY,
    meal_id INT NOT NULL,
    ingredient_name VARCHAR(100) NOT NULL,
    quantity VARCHAR(50),
    unit VARCHAR(30),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meal_id) REFERENCES meals(meal_id) ON DELETE CASCADE
)", "INGREDIENTS table created or exists");

execSql($pdo, "CREATE TABLE IF NOT EXISTS meal_preparation_steps (
    step_id INT AUTO_INCREMENT PRIMARY KEY,
    meal_id INT NOT NULL,
    step_number INT,
    step_description TEXT,
    duration_minutes INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meal_id) REFERENCES meals(meal_id) ON DELETE CASCADE
)", "MEAL_PREPARATION_STEPS table created or exists");

execSql($pdo, "CREATE TABLE IF NOT EXISTS meal_planning (
    plan_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    meal_id INT NOT NULL,
    planned_date DATE NOT NULL,
    meal_type VARCHAR(30),
    notes TEXT,
    portion_multiplier DECIMAL(4,2) NOT NULL DEFAULT 1.00,
    reminder_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_meal_date (user_id, meal_id, planned_date, meal_type),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (meal_id) REFERENCES meals(meal_id) ON DELETE CASCADE
)", "MEAL_PLANNING table created or exists");

execSql($pdo, "CREATE TABLE IF NOT EXISTS carts (
    list_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    list_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)", "CARTS table created or exists");

execSql($pdo, "CREATE TABLE IF NOT EXISTS cart_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    list_id INT NOT NULL,
    meal_id INT,
    item_name VARCHAR(100),
    quantity VARCHAR(50),
    purchased BOOLEAN DEFAULT FALSE,
    custom_item BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (list_id) REFERENCES carts(list_id) ON DELETE CASCADE,
    FOREIGN KEY (meal_id) REFERENCES meals(meal_id)
)", "CART_ITEMS table created or exists");

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

execSql($pdo, "CREATE TABLE IF NOT EXISTS user_nutrition_goals (
    goal_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    daily_calories_target INT NOT NULL DEFAULT 2000,
    daily_protein_target INT NOT NULL DEFAULT 75,
    daily_carbs_target INT NOT NULL DEFAULT 250,
    daily_fats_target INT NOT NULL DEFAULT 70,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)", "USER_NUTRITION_GOALS table created or exists");

execSql($pdo, "CREATE TABLE IF NOT EXISTS meal_plan_shares (
    share_id INT AUTO_INCREMENT PRIMARY KEY,
    owner_user_id INT NOT NULL,
    target_user_id INT NOT NULL,
    planned_date DATE NOT NULL,
    can_edit BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_share (owner_user_id, target_user_id, planned_date),
    FOREIGN KEY (owner_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (target_user_id) REFERENCES users(user_id) ON DELETE CASCADE
)", "MEAL_PLAN_SHARES table created or exists");

// Create performance indexes for search filters
execSql($pdo, "ALTER TABLE nutrition ADD INDEX IF NOT EXISTS idx_calories (calories)", "Index on nutrition.calories created");
execSql($pdo, "ALTER TABLE nutrition ADD INDEX IF NOT EXISTS idx_proteins_g (proteins_g)", "Index on nutrition.proteins_g created");
execSql($pdo, "ALTER TABLE meals ADD INDEX IF NOT EXISTS idx_meal_name (meal_name)", "Index on meals.meal_name created");

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

// Ingredients data - real, specific ingredients with quantities
$ingredients_data = [
    ['Ugali & Sukuma', [
        ['Maize flour', '2', 'cups'],
        ['Water', '4', 'cups'],
        ['Salt', '1', 'tsp'],
        ['Collard greens', '200', 'g'],
        ['Onion', '1', 'medium'],
        ['Garlic', '2', 'cloves'],
        ['Cooking oil', '2', 'tbsp'],
        ['Tomato', '1', 'medium']
    ]],
    ['Mandazi', [
        ['All-purpose flour', '2', 'cups'],
        ['Sugar', '1/4', 'cup'],
        ['Salt', '1', 'tsp'],
        ['Baking powder', '2', 'tsp'],
        ['Eggs', '2', 'large'],
        ['Milk', '1/2', 'cup'],
        ['Cardamom', '1/2', 'tsp'],
        ['Oil for frying', '2', 'cups']
    ]],
    ['Chapati & Beans', [
        ['All-purpose flour', '2', 'cups'],
        ['Water', '3/4', 'cup'],
        ['Salt', '1', 'tsp'],
        ['Ghee or oil', '3', 'tbsp'],
        ['Dried beans', '1', 'cup'],
        ['Onion', '1', 'medium'],
        ['Garlic', '2', 'cloves'],
        ['Tomato', '1', 'large']
    ]],
    ['Oatmeal with Fruits', [
        ['Rolled oats', '1', 'cup'],
        ['Water or milk', '2', 'cups'],
        ['Honey', '2', 'tbsp'],
        ['Blueberries', '1/2', 'cup'],
        ['Strawberries', '1/2', 'cup'],
        ['Banana', '1', 'whole'],
        ['Almonds', '1/4', 'cup'],
        ['Cinnamon', '1/4', 'tsp']
    ]],
    ['Eggs & Toast', [
        ['Eggs', '3', 'large'],
        ['Butter', '2', 'tbsp'],
        ['Salt and pepper', 'to', 'taste'],
        ['Bread slices', '2', 'slices'],
        ['Milk', '2', 'tbsp'],
        ['Fresh parsley', '1', 'tbsp']
    ]],
    ['Ugali & Nyama', [
        ['Maize flour', '3', 'cups'],
        ['Water', '6', 'cups'],
        ['Beef', '500', 'g'],
        ['Onion', '2', 'medium'],
        ['Garlic', '3', 'cloves'],
        ['Tomato', '2', 'large'],
        ['Cooking oil', '3', 'tbsp'],
        ['Salt and spices', 'to', 'taste']
    ]],
    ['Rice & Stew', [
        ['Rice', '2', 'cups'],
        ['Water', '3', 'cups'],
        ['Mixed vegetables', '300', 'g'],
        ['Onion', '1', 'large'],
        ['Garlic', '3', 'cloves'],
        ['Tomato', '2', 'large'],
        ['Cooking oil', '2', 'tbsp'],
        ['Salt and cumin', 'to', 'taste']
    ]],
    ['Githeri', [
        ['Corn kernels', '2', 'cups'],
        ['Cooked beans', '1', 'cup'],
        ['Onion', '1', 'medium'],
        ['Garlic', '2', 'cloves'],
        ['Tomatoes', '2', 'large'],
        ['Cooking oil', '2', 'tbsp'],
        ['Cilantro', '1', 'tbsp'],
        ['Salt and peppers', 'to', 'taste']
    ]],
    ['Samosa & Chai', [
        ['All-purpose flour', '1', 'cup'],
        ['Potatoes', '200', 'g'],
        ['Peas', '1/2', 'cup'],
        ['Onion', '1', 'medium'],
        ['Ginger-garlic paste', '1', 'tbsp'],
        ['Spices (cumin, garam masala)', '1', 'tsp'],
        ['Oil for frying', '1', 'cup'],
        ['Tea leaves', '2', 'tsp']
    ]],
    ['Lentil Soup', [
        ['Red lentils', '1', 'cup'],
        ['Water or vegetable broth', '4', 'cups'],
        ['Onion', '1', 'large'],
        ['Garlic', '3', 'cloves'],
        ['Carrots', '2', 'medium'],
        ['Celery', '1', 'stalk'],
        ['Olive oil', '2', 'tbsp'],
        ['Cumin and coriander', '1', 'tsp']
    ]],
    ['Nyama Choma', [
        ['Beef chunks', '800', 'g'],
        ['Salt', '1', 'tbsp'],
        ['Black pepper', '1', 'tsp'],
        ['Paprika', '1', 'tsp'],
        ['Garlic powder', '1', 'tsp'],
        ['Lemon juice', '3', 'tbsp'],
        ['Charcoal', 'as needed', 'for grilling']
    ]],
    ['Vegetable Curry', [
        ['Mixed vegetables', '400', 'g'],
        ['Coconut milk', '1', 'can'],
        ['Onion', '1', 'large'],
        ['Garlic', '3', 'cloves'],
        ['Ginger', '1', 'tbsp'],
        ['Curry powder', '2', 'tsp'],
        ['Turmeric', '1/2', 'tsp'],
        ['Oil', '2', 'tbsp']
    ]],
    ['Fish & Chips', [
        ['White fish fillets', '600', 'g'],
        ['Potatoes', '400', 'g'],
        ['All-purpose flour', '1', 'cup'],
        ['Egg', '1', 'large'],
        ['Breadcrumbs', '1/2', 'cup'],
        ['Lemon', '1', 'whole'],
        ['Oil for frying', '2', 'cups'],
        ['Salt and paprika', 'to', 'taste']
    ]],
    ['Ugali & Greens', [
        ['Maize flour', '2', 'cups'],
        ['Water', '4', 'cups'],
        ['Leafy greens (kale/spinach)', '200', 'g'],
        ['Onion', '1', 'medium'],
        ['Garlic', '2', 'cloves'],
        ['Cooking oil', '2', 'tbsp'],
        ['Salt', '1', 'tsp']
    ]],
    ['Pilau', [
        ['Rice', '2', 'cups'],
        ['Water', '3', 'cups'],
        ['Beef', '300', 'g'],
        ['Onion', '2', 'medium'],
        ['Cumin seeds', '1', 'tbsp'],
        ['Cinnamon stick', '1', 'whole'],
        ['Cloves', '4', 'whole'],
        ['Oil', '3', 'tbsp']
    ]],
    ['Kachumbari Salad', [
        ['Tomatoes', '3', 'large'],
        ['Red onion', '1', 'medium'],
        ['Fresh cilantro', '2', 'tbsp'],
        ['Lime juice', '2', 'tbsp'],
        ['Salt and pepper', 'to', 'taste'],
        ['Green chili', '1', 'whole']
    ]],
    ['Muamba Stew', [
        ['Peanut butter', '1/2', 'cup'],
        ['Vegetables', '400', 'g'],
        ['Onion', '1', 'large'],
        ['Garlic', '3', 'cloves'],
        ['Tomatoes', '2', 'large'],
        ['Water', '2', 'cups'],
        ['Oil', '2', 'tbsp'],
        ['Paprika and cayenne', '1', 'tsp']
    ]],
    ['Matoke', [
        ['Green plantains', '4', 'medium'],
        ['Tomato sauce', '1', 'cup'],
        ['Onion', '1', 'medium'],
        ['Garlic', '2', 'cloves'],
        ['Oil', '2', 'tbsp'],
        ['Salt and pepper', 'to', 'taste'],
        ['Fresh cilantro', '1', 'tbsp']
    ]],
    ['Sukuma Wiki Fry', [
        ['Collard greens', '300', 'g'],
        ['Onion', '1', 'medium'],
        ['Garlic', '2', 'cloves'],
        ['Tomato', '1', 'large'],
        ['Cooking oil', '2', 'tbsp'],
        ['Salt', '1/2', 'tsp'],
        ['Red pepper flakes', '1/4', 'tsp']
    ]],
    ['Bean Soup', [
        ['Beans (mixed)', '1', 'cup'],
        ['Vegetable broth', '4', 'cups'],
        ['Carrots', '2', 'medium'],
        ['Celery', '1', 'stalk'],
        ['Onion', '1', 'large'],
        ['Garlic', '3', 'cloves'],
        ['Olive oil', '2', 'tbsp'],
        ['Thyme', '1', 'tsp']
    ]],
    ['Coconut Rice', [
        ['Rice', '2', 'cups'],
        ['Coconut milk', '1', 'can'],
        ['Water', '1', 'cup'],
        ['Onion', '1', 'medium'],
        ['Garlic cloves', '2', 'whole'],
        ['Salt', '1', 'tsp'],
        ['Butter', '1', 'tbsp']
    ]],
    ['Roasted Chicken', [
        ['Whole chicken', '1.5', 'kg'],
        ['Olive oil', '3', 'tbsp'],
        ['Lemon', '2', 'whole'],
        ['Fresh herbs', '3', 'tbsp'],
        ['Garlic cloves', '6', 'whole'],
        ['Salt and pepper', 'to', 'taste'],
        ['Paprika', '1', 'tsp']
    ]]
];

$stmtInsIng = $pdo->prepare("INSERT IGNORE INTO ingredients (meal_id, ingredient_name, quantity, unit) VALUES (?, ?, ?, ?)");
foreach ($ingredients_data as $meal_ing) {
    $stmtFindMeal->execute([$meal_ing[0]]);
    $meal_row = $stmtFindMeal->fetch();
    if ($meal_row) {
        $meal_id = (int)$meal_row['meal_id'];
        foreach ($meal_ing[1] as $ing) {
            $stmtInsIng->execute([$meal_id, $ing[0], $ing[1], $ing[2]]);
        }
    }
}

// Preparation steps data - detailed, actionable steps
$preparation_data = [
    ['Ugali & Sukuma', [
        [1, 'Boil water in a large pot and add salt', 5],
        [2, 'When water is boiling, gradually add maize flour while stirring to avoid lumps', 3],
        [3, 'Keep stirring constantly for 10-15 minutes until ugali is thick and cooked through', 12],
        [4, 'In a separate pan, heat oil and sauté onions and garlic until fragrant', 3],
        [5, 'Add chopped collard greens and tomatoes, cook until wilted', 8],
        [6, 'Season with salt and serve hot alongside the ugali', 0]
    ]],
    ['Mandazi', [
        [1, 'Mix flour, sugar, salt, and baking powder in a bowl', 5],
        [2, 'Beat eggs and mix with milk, then add to dry ingredients', 3],
        [3, 'Knead dough until smooth, cover and let rest for 30 minutes', 30],
        [4, 'Heat oil in a deep pan to 175°C (350°F)', 5],
        [5, 'Shape dough into balls and flatten slightly', 5],
        [6, 'Deep fry until golden brown on both sides, about 2-3 minutes per side', 5],
        [7, 'Drain on paper towels and serve hot', 0]
    ]],
    ['Chapati & Beans', [
        [1, 'Prepare beans by soaking overnight, then boiling with salt until tender', 0],
        [2, 'Mix flour, salt and knead with water to make soft dough', 5],
        [3, 'Divide dough into 8 balls and rest for 10 minutes', 10],
        [4, 'Roll each ball thin, brush with ghee, roll again and flatten', 8],
        [5, 'Cook on a hot griddle until light brown and puffed', 3],
        [6, 'Heat oil and sauté onions, tomatoes and cooked beans for the stew', 10],
        [7, 'Serve hot chapati with bean stew', 0]
    ]],
    ['Oatmeal with Fruits', [
        [1, 'Boil water or milk in a pot, add salt', 3],
        [2, 'Add rolled oats and stir continuously', 1],
        [3, 'Reduce heat and simmer for 5 minutes until creamy', 5],
        [4, 'Remove from heat and add honey and cinnamon', 1],
        [5, 'Pour into a bowl and top with fresh berries and banana slices', 2],
        [6, 'Garnish with chopped almonds and serve warm', 0]
    ]],
    ['Eggs & Toast', [
        [1, 'Heat butter in a non-stick pan over medium heat', 2],
        [2, 'Beat eggs with milk, salt and pepper in a bowl', 2],
        [3, 'Pour beaten eggs into the pan and scramble gently', 4],
        [4, 'Toast bread slices until golden brown', 3],
        [5, 'Butter the toast and place on a plate', 1],
        [6, 'Top with scrambled eggs and garnish with fresh parsley', 1]
    ]],
    ['Ugali & Nyama', [
        [1, 'Cut beef into medium chunks and season with salt and pepper', 5],
        [2, 'Heat oil in a large pot and brown the beef on all sides', 10],
        [3, 'Remove meat and sauté onions and garlic until fragrant', 3],
        [4, 'Add tomatoes and return meat to the pot', 2],
        [5, 'Add water and simmer covered for 1.5 hours until meat is tender', 90],
        [6, 'Meanwhile, prepare ugali as in the Ugali & Sukuma recipe', 20],
        [7, 'Serve hot meat stew with ugali', 0]
    ]],
    ['Rice & Stew', [
        [1, 'Rinse rice under cold water until water runs clear', 2],
        [2, 'Heat oil in a pot and sauté onions and garlic', 3],
        [3, 'Add rice and stir to coat with oil, toast for 2 minutes', 2],
        [4, 'Pour in water and bring to a boil', 3],
        [5, 'Reduce heat, cover and simmer for 15 minutes until rice is cooked', 15],
        [6, 'In another pot, prepare vegetable stew with tomatoes and vegetables', 15],
        [7, 'Fluff rice with a fork and serve topped with stew', 2]
    ]],
    ['Githeri', [
        [1, 'If using dried beans, soak overnight and boil until tender', 0],
        [2, 'Heat oil and sauté onions and garlic until fragrant', 3],
        [3, 'Add corn kernels and cooked beans, stir well', 2],
        [4, 'Add chopped tomatoes and their juice', 2],
        [5, 'Simmer uncovered for 10 minutes until flavors blend', 10],
        [6, 'Season with salt, pepper and cilantro', 1],
        [7, 'Serve hot as a main dish or side', 0]
    ]],
    ['Samosa & Chai', [
        [1, 'Prepare tea by boiling water and steeping tea leaves', 5],
        [2, 'For samosas, prepare pastry dough and let rest', 20],
        [3, 'Cook and mash potatoes, mix with spices and peas', 10],
        [4, 'Roll out dough thin, cut into triangles', 5],
        [5, 'Fill each triangle with potato mixture and fold into samosa shape', 10],
        [6, 'Heat oil for frying to 175°C (350°F)', 3],
        [7, 'Deep fry samosas until golden brown, about 3-4 minutes', 5],
        [8, 'Drain and serve hot with chai or chutney', 0]
    ]],
    ['Lentil Soup', [
        [1, 'Rinse lentils and pick out any stones', 2],
        [2, 'Heat olive oil and sauté onion, garlic, carrots and celery', 5],
        [3, 'Add rinsed lentils and stir well', 1],
        [4, 'Pour in broth and bring to a boil', 3],
        [5, 'Reduce heat and simmer uncovered for 20-25 minutes', 25],
        [6, 'Add cumin and coriander, season with salt and pepper', 1],
        [7, 'Serve hot, optionally with a dollop of yogurt', 0]
    ]],
    ['Nyama Choma', [
        [1, 'Cut beef into chunks and season generously with salt, pepper and spices', 5],
        [2, 'Rub meat with lemon juice and let marinate for at least 30 minutes', 30],
        [3, 'Prepare charcoal grill and heat until hot', 15],
        [4, 'Place meat on grill grates and cook, turning occasionally', 15],
        [5, 'Continue cooking until meat is charred outside and cooked to desired doneness', 10],
        [6, 'Remove from grill and let rest for 5 minutes', 5],
        [7, 'Serve hot with grilled vegetables or salad', 0]
    ]],
    ['Vegetable Curry', [
        [1, 'Heat oil in a large pot and sauté onion until softened', 5],
        [2, 'Add garlic and ginger, cook for 1 minute until aromatic', 1],
        [3, 'Add curry powder and turmeric, stirring constantly for 1 minute', 1],
        [4, 'Add chopped vegetables and stir to coat with spices', 2],
        [5, 'Pour in coconut milk and bring to a simmer', 2],
        [6, 'Simmer uncovered for 15-20 minutes until vegetables are tender', 18],
        [7, 'Adjust seasonings and serve hot', 0]
    ]],
    ['Fish & Chips', [
        [1, 'Cut potatoes into chips and soak in cold water for 30 minutes', 30],
        [2, 'Pat fish dry and cut into portions', 2],
        [3, 'Prepare three bowls: one with flour, one with beaten egg, one with breadcrumbs', 3],
        [4, 'Coat fish with flour, then egg, then breadcrumbs', 5],
        [5, 'Heat oil to 175°C (350°F) and fry chips until golden, drain on paper', 10],
        [6, 'Fry fish pieces until golden brown, about 4-5 minutes', 5],
        [7, 'Serve hot with lemon wedges and chips', 0]
    ]],
    ['Ugali & Greens', [
        [1, 'Prepare ugali as in the basic recipe', 15],
        [2, 'While ugali cooks, wash and chop leafy greens', 5],
        [3, 'Heat oil and sauté onions and garlic until fragrant', 3],
        [4, 'Add chopped greens and cook until wilted', 5],
        [5, 'Season with salt and serve hot with ugali', 0]
    ]],
    ['Pilau', [
        [1, 'Cut beef into chunks and season with salt and pepper', 3],
        [2, 'Heat oil and brown beef on all sides, remove and set aside', 8],
        [3, 'In same pot, sauté onions until golden', 5],
        [4, 'Add whole cumin seeds, cinnamon and cloves, toast for 1 minute', 1],
        [5, 'Rinse rice and add to the pot, stir to coat', 2],
        [6, 'Return meat to pot, add water and bring to a boil', 3],
        [7, 'Reduce heat, cover and simmer for 20 minutes until rice is cooked', 20],
        [8, 'Remove from heat, let stand 5 minutes, then fluff and serve', 0]
    ]],
    ['Kachumbari Salad', [
        [1, 'Wash tomatoes and red onion', 2],
        [2, 'Dice tomatoes and finely chop red onion', 5],
        [3, 'Chop fresh cilantro and slice green chili', 2],
        [4, 'Combine all ingredients in a bowl', 1],
        [5, 'Squeeze lime juice over the salad', 1],
        [6, 'Add salt and pepper to taste and toss gently', 1],
        [7, 'Serve immediately as a fresh accompaniment to meals', 0]
    ]],
    ['Muamba Stew', [
        [1, 'Heat oil in a large pot and sauté onions and garlic', 3],
        [2, 'Add chopped vegetables and cook for 5 minutes', 5],
        [3, 'Stir in peanut butter until well combined', 2],
        [4, 'Add tomato sauce and water', 1],
        [5, 'Bring to a boil, then reduce heat and simmer for 20 minutes', 20],
        [6, 'Add paprika and cayenne pepper for heat', 1],
        [7, 'Adjust seasonings and serve hot', 0]
    ]],
    ['Matoke', [
        [1, 'Peel plantains and cut into chunks', 5],
        [2, 'Boil water in a pot and add salt', 2],
        [3, 'Add plantain chunks and boil until very tender', 15],
        [4, 'In another pan, heat oil and sauté onions and garlic', 3],
        [5, 'Add tomato sauce and simmer for 5 minutes', 5],
        [6, 'Drain plantains and arrange in a serving dish', 2],
        [7, 'Pour tomato sauce over plantains, garnish with cilantro and serve', 0]
    ]],
    ['Sukuma Wiki Fry', [
        [1, 'Wash collard greens thoroughly and remove tough stems', 3],
        [2, 'Chop greens roughly', 2],
        [3, 'Heat oil in a large pan and sauté onion and garlic', 3],
        [4, 'Add chopped collard greens and stir well', 2],
        [5, 'Add chopped tomato and cook until greens are tender', 8],
        [6, 'Season with salt and red pepper flakes', 1],
        [7, 'Serve hot as a side dish or with ugali', 0]
    ]],
    ['Bean Soup', [
        [1, 'If using dried beans, soak overnight and boil until tender', 0],
        [2, 'Heat olive oil and sauté onion, garlic, carrots and celery', 5],
        [3, 'Pour in vegetable broth and add cooked beans', 2],
        [4, 'Bring to a boil, then reduce heat and simmer for 15 minutes', 15],
        [5, 'Add thyme and simmer for another 5 minutes', 5],
        [6, 'Season with salt and pepper to taste', 1],
        [7, 'Serve hot, optionally blend for a creamier texture', 0]
    ]],
    ['Coconut Rice', [
        [1, 'Rinse rice under cold water until water runs clear', 2],
        [2, 'Heat butter in a pot and sauté onion and garlic', 3],
        [3, 'Add rice and stir to coat with butter, toast for 2 minutes', 2],
        [4, 'Add coconut milk and water, bring to a boil', 3],
        [5, 'Reduce heat, cover and simmer for 15 minutes', 15],
        [6, 'Let steam covered for 5 minutes without heat', 5],
        [7, 'Fluff with a fork and serve hot', 0]
    ]],
    ['Roasted Chicken', [
        [1, 'Remove chicken from refrigerator 30 minutes before cooking', 30],
        [2, 'Pat dry thoroughly with paper towels', 2],
        [3, 'Mix olive oil with herbs, garlic powder, paprika, salt and pepper', 2],
        [4, 'Rub herb mixture all over the chicken', 3],
        [5, 'Place lemon halves inside the cavity', 1],
        [6, 'Roast in a preheated oven at 190°C (375°F) for 1.5 hours', 90],
        [7, 'Let rest for 10 minutes before carving and serving', 10]
    ]]
];

$stmtInsStep = $pdo->prepare("INSERT IGNORE INTO meal_preparation_steps (meal_id, step_number, step_description, duration_minutes) VALUES (?, ?, ?, ?)");
foreach ($preparation_data as $meal_prep) {
    $stmtFindMeal->execute([$meal_prep[0]]);
    $meal_row = $stmtFindMeal->fetch();
    if ($meal_row) {
        $meal_id = (int)$meal_row['meal_id'];
        foreach ($meal_prep[1] as $step) {
            $stmtInsStep->execute([$meal_id, $step[0], $step[1], $step[2]]);
        }
    }
}

echo "Database seeded successfully.\n";
echo "Back to Home: ../\n";

?>
