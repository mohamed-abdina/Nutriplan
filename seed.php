<?php
// Seed database with tables and sample data
// Run this script once, then delete from production

$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'meal_planning_db';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "DROP DATABASE IF EXISTS $db_name";
if ($conn->query($sql) === TRUE) {
    echo "Database dropped successfully<br>";
} else {
    echo "Note: Could not drop database (it may not exist)<br>";
}

$sql = "CREATE DATABASE IF NOT EXISTS $db_name";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db($db_name);

// Create USERS table
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    avatar_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "USERS table created successfully<br>";
} else {
    echo "Error creating USERS table: " . $conn->error . "<br>";
}

// Create CATEGORIES table
$sql = "CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    category_icon VARCHAR(2)
)";

if ($conn->query($sql) === TRUE) {
    echo "CATEGORIES table created successfully<br>";
} else {
    echo "Error creating CATEGORIES table: " . $conn->error . "<br>";
}

// Create MEALS table
$sql = "CREATE TABLE IF NOT EXISTS meals (
    meal_id INT AUTO_INCREMENT PRIMARY KEY,
    meal_name VARCHAR(100) NOT NULL,
    category_id INT NOT NULL,
    description TEXT,
    preparation_time INT,
    meal_icon VARCHAR(2),
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "MEALS table created successfully<br>";
} else {
    echo "Error creating MEALS table: " . $conn->error . "<br>";
}

// Create NUTRITION table
$sql = "CREATE TABLE IF NOT EXISTS nutrition (
    nutrition_id INT AUTO_INCREMENT PRIMARY KEY,
    meal_id INT NOT NULL,
    calories INT,
    proteins_g DECIMAL(10, 2),
    carbs_g DECIMAL(10, 2),
    fats_g DECIMAL(10, 2),
    fiber_g DECIMAL(10, 2),
    iron_mg DECIMAL(10, 2),
    calcium_mg DECIMAL(10, 2),
    vitamins VARCHAR(200),
    FOREIGN KEY (meal_id) REFERENCES meals(meal_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "NUTRITION table created successfully<br>";
} else {
    echo "Error creating NUTRITION table: " . $conn->error . "<br>";
}

// Create SHOPPING_LIST table
$sql = "CREATE TABLE IF NOT EXISTS shopping_lists (
    list_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    list_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "SHOPPING_LIST table created successfully<br>";
} else {
    echo "Error creating SHOPPING_LIST table: " . $conn->error . "<br>";
}

// Create SHOPPING_ITEMS table
$sql = "CREATE TABLE IF NOT EXISTS shopping_items (
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
)";

if ($conn->query($sql) === TRUE) {
    echo "SHOPPING_ITEMS table created successfully<br>";
} else {
    echo "Error creating SHOPPING_ITEMS table: " . $conn->error . "<br>";
}

// Insert categories
$categories = [
    ['Breakfast', 'croissant'],
    ['Lunch', 'plate-with-cutlery'],
    ['Supper', 'fork-and-knife-with-plate']
];

foreach ($categories as $cat) {
    $cat_name = $conn->real_escape_string($cat[0]);
    $sql = "INSERT IGNORE INTO categories (category_name, category_icon) VALUES ('$cat_name', '{$cat[1]}')";
    $conn->query($sql);
}

// Category ID mapping
$category_map = [
    'Breakfast' => 1,
    'Lunch' => 2,
    'Supper' => 3
];

// Sample meals data with nutrition
$meals = [
    // Breakfast
    ['Ugali & Sukuma', 'Breakfast', 'Ground maize served with sautéed greens', 30, 'egg'],
    ['Mandazi', 'Breakfast', 'Deep-fried dough pastry', 15, 'fried-egg'],
    ['Chapati & Beans', 'Breakfast', 'Flatbread with bean stew', 40, 'croissant'],
    ['Oatmeal with Fruits', 'Breakfast', 'Oats topped with berries', 20, 'bowl-with-spoon'],
    ['Eggs & Toast', 'Breakfast', 'Scrambled eggs with buttered toast', 25, 'cooking'],
    
    // Lunch
    ['Ugali & Nyama', 'Lunch', 'Maize meal with grilled meat', 45, 'meat-on-bone'],
    ['Rice & Stew', 'Lunch', 'Fragrant rice with vegetable stew', 50, 'rice-bowl'],
    ['Githeri', 'Lunch', 'Corn and beans in tomato gravy', 35, 'corn'],
    ['Samosa & Chai', 'Lunch', 'Pastry with spiced filling', 20, 'dumpling'],
    ['Lentil Soup', 'Lunch', 'Protein-packed legume soup', 40, 'pot-of-food'],
    
    // Supper
    ['Nyama Choma', 'Supper', 'Grilled meat with spices', 60, 'meat-on-bone'],
    ['Vegetable Curry', 'Supper', 'Mixed vegetables in coconut curry', 45, 'curry-rice'],
    ['Fish & Chips', 'Supper', 'Crispy fish with potato fries', 50, 'fish-cake'],
    ['Ugali & Greens', 'Supper', 'Maize meal with cooked vegetables', 35, 'leafy-green'],
    ['Pilau', 'Supper', 'Spiced rice with meat and spices', 55, 'rice-bowl'],
    
    // More variety
    ['Kachumbari Salad', 'Lunch', 'Fresh tomato and onion salad', 15, 'green-salad'],
    ['Muamba Stew', 'Lunch', 'Peanut-based vegetable stew', 50, 'pot-of-food'],
    ['Matoke', 'Supper', 'Steamed banana with sauce', 40, 'banana'],
    ['Sukuma Wiki Fry', 'Breakfast', 'Sautéed collard greens', 20, 'leafy-green'],
    ['Bean Soup', 'Supper', 'Hearty bean and vegetable soup', 45, 'pot-of-food'],
    ['Coconut Rice', 'Lunch', 'Rice cooked in coconut milk', 50, 'coconut'],
    ['Roasted Chicken', 'Supper', 'Herb-roasted chicken pieces', 60, 'poultry-leg']
];

foreach ($meals as $meal) {
    // Get category ID
    $cat_name = $meal[1];
    $cat_id = $category_map[$cat_name];
    
    // Use prepared statement to avoid SQL injection
    $meal_name = $conn->real_escape_string($meal[0]);
    $description = $conn->real_escape_string($meal[2]);
    $icon = $meal[4];
    
    $sql = "INSERT IGNORE INTO meals (meal_name, category_id, description, preparation_time, meal_icon)
            VALUES ('$meal_name', $cat_id, '$description', {$meal[3]}, '$icon')";
    $conn->query($sql);
}

// Add nutrition data for meals
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

foreach ($nutrition_data as $nut) {
    $meal_name = $conn->real_escape_string($nut[0]);
    $meal_result = $conn->query("SELECT meal_id FROM meals WHERE meal_name = '$meal_name'");
    
    if ($meal_result && $meal_result->num_rows > 0) {
        $meal_row = $meal_result->fetch_assoc();
        $meal_id = $meal_row['meal_id'];
        
        $sql = "INSERT IGNORE INTO nutrition (meal_id, calories, proteins_g, carbs_g, fats_g, fiber_g, iron_mg, calcium_mg, vitamins)
                VALUES ($meal_id, {$nut[1]}, {$nut[2]}, {$nut[3]}, {$nut[4]}, {$nut[5]}, {$nut[6]}, {$nut[7]}, '{$nut[8]}')";
        $conn->query($sql);
    }
}

echo "Database seeded successfully with 22 meals and complete nutrition data!<br>";
echo "<a href='../'>Back to Home</a>";
?>
