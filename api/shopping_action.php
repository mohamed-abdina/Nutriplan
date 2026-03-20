<?php
// Shopping list action API
header('Content-Type: application/json');
session_start();

require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? sanitize_input($_POST['action']) : '';

// Get or create default shopping list
$list_result = $conn->query("SELECT list_id FROM shopping_lists WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 1");
if ($list_result && $list_result->num_rows > 0) {
    $list = $list_result->fetch_assoc();
    $list_id = $list['list_id'];
} else {
    $conn->query("INSERT INTO shopping_lists (user_id, list_name) VALUES ($user_id, 'My Shopping List')");
    $list_id = $conn->insert_id;
}

if ($action === 'add') {
    $meal_id = isset($_POST['meal_id']) ? (int)$_POST['meal_id'] : 0;
    
    if ($meal_id > 0) {
        $conn->query("INSERT INTO shopping_items (list_id, meal_id, quantity) 
                     VALUES ($list_id, $meal_id, '1 serving')");
        echo json_encode(['success' => true, 'message' => 'Added to list']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid meal']);
    }
} 
elseif ($action === 'toggle') {
    $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    
    if ($item_id > 0) {
        $conn->query("UPDATE shopping_items SET purchased = NOT purchased WHERE item_id = $item_id");
        echo json_encode(['success' => true, 'message' => 'Updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid item']);
    }
}
elseif ($action === 'delete') {
    $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    
    if ($item_id > 0) {
        $conn->query("DELETE FROM shopping_items WHERE item_id = $item_id");
        echo json_encode(['success' => true, 'message' => 'Deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid item']);
    }
}
elseif ($action === 'add_custom') {
    $item_name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
    $quantity = isset($_POST['qty']) ? sanitize_input($_POST['qty']) : '1';
    
    if (!empty($item_name)) {
        $conn->query("INSERT INTO shopping_items (list_id, item_name, quantity, custom_item) 
                     VALUES ($list_id, '$item_name', '$quantity', TRUE)");
        echo json_encode(['success' => true, 'message' => 'Custom item added']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item name required']);
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
