<?php
// Shopping list action API - DEPRECATED: Use cart_action.php instead
// This file now redirects to cart_action.php for backward compatibility
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';
secure_session_start();

require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
require_once '../includes/rate_limit.php';
require_once '../includes/error_logger.php';
require_once __DIR__ . '/../includes/csrf.php';

// Log deprecation warning
$error_logger->log_warning('DEPRECATION_WARNING', [
    'deprecated_endpoint' => 'api/shopping_action.php',
    'replacement' => 'api/cart_action.php',
    'action' => $_POST['action'] ?? 'unknown'
]);

// Apply rate limiting (10 requests per 60 seconds)
if (!$limiter->check_rate_limit('shopping_action', 10, 60)) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Too many requests. Please try again later.',
        'remaining' => 0
    ]);
    $error_logger->log_security_event('RATE_LIMIT_EXCEEDED', ['endpoint' => 'shopping_action']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? sanitize_input($_POST['action']) : '';

// Log API call
$error_logger->log_api_call('shopping_action', 'POST', ['action' => $action], 200);

// CSRF protection for state-changing requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!validate_csrf($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

// Get or create default cart (backward compatible - still called 'shopping list' in UI)
$row = pdo_fetch_one("SELECT list_id FROM carts WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1", [':user_id' => (int)$user_id]);
if ($row && isset($row['list_id'])) {
    $list_id = $row['list_id'];
} else {
    pdo_query("INSERT INTO carts (user_id, list_name) VALUES (:user_id, :name)", [':user_id' => (int)$user_id, ':name' => 'My Shopping List']);
    global $pdo;
    $list_id = (int)$pdo->lastInsertId();
}

if ($action === 'add') {
    $meal_id = isset($_POST['meal_id']) ? (int)$_POST['meal_id'] : 0;
    
    if ($meal_id > 0) {
        // Get meal details
        $meal = pdo_fetch_one("SELECT meal_name FROM meals WHERE meal_id = ?", [$meal_id]);
        if (!$meal) {
            echo json_encode(['success' => false, 'message' => 'Meal not found']);
            exit;
        }
        
        // Get all ingredients for this meal
        $ingredients = pdo_fetch_all(
            "SELECT ingredient_name, quantity, unit FROM ingredients WHERE meal_id = ? ORDER BY ingredient_id", 
            [$meal_id]
        );
        
        if (!empty($ingredients)) {
            // Add each ingredient as a separate cart item
            $stmtAddItem = $pdo->prepare("INSERT INTO cart_items (list_id, meal_id, item_name, quantity, custom_item) VALUES (?, ?, ?, ?, 0)");
            foreach ($ingredients as $ing) {
                $quantity_display = $ing['quantity'] . ' ' . $ing['unit'];
                $stmtAddItem->execute([$list_id, $meal_id, $ing['ingredient_name'], $quantity_display]);
            }
            echo json_encode(['success' => true, 'message' => "Added {$meal['meal_name']} and " . count($ingredients) . " ingredients to list"]);
        } else {
            // Fallback: just add the meal with a generic entry
            pdo_query("INSERT INTO cart_items (list_id, meal_id, item_name, quantity, custom_item) VALUES (:list_id, :meal_id, :name, :qty, 0)", 
                [':list_id' => $list_id, ':meal_id' => $meal_id, ':name' => $meal['meal_name'], ':qty' => '1 serving']);
            echo json_encode(['success' => true, 'message' => "Added {$meal['meal_name']} to cart"]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid meal']);
    }
} 
elseif ($action === 'toggle') {
    $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    
    if ($item_id > 0) {
        pdo_query("UPDATE cart_items SET purchased = NOT purchased WHERE item_id = :item_id", [':item_id' => $item_id]);
        echo json_encode(['success' => true, 'message' => 'Updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid item']);
    }
}
elseif ($action === 'delete') {
    $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    
    if ($item_id > 0) {
        pdo_query("DELETE FROM cart_items WHERE item_id = :item_id", [':item_id' => $item_id]);
        echo json_encode(['success' => true, 'message' => 'Deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid item']);
    }
}
elseif ($action === 'add_custom') {
    $item_name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
    $quantity = isset($_POST['qty']) ? sanitize_input($_POST['qty']) : '1';
    
    if (!empty($item_name)) {
        pdo_query("INSERT INTO cart_items (list_id, item_name, quantity, custom_item) VALUES (:list_id, :name, :qty, :custom)", [':list_id' => $list_id, ':name' => $item_name, ':qty' => $quantity, ':custom' => 1]);
        echo json_encode(['success' => true, 'message' => 'Item added to cart']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item name required']);
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
