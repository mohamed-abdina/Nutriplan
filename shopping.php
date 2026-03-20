<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];

// Get or create default shopping list
$list_result = $conn->query("SELECT list_id FROM shopping_lists WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 1");
if ($list_result && $list_result->num_rows > 0) {
    $list = $list_result->fetch_assoc();
    $list_id = $list['list_id'];
} else {
    $conn->query("INSERT INTO shopping_lists (user_id, list_name) VALUES ($user_id, 'My Shopping List')");
    $list_id = $conn->insert_id;
}

// Get items grouped by category
$sql = "SELECT si.item_id, si.item_name, si.quantity, si.purchased, si.custom_item, 
        c.category_name, c.category_id
        FROM shopping_items si
        LEFT JOIN meals m ON si.meal_id = m.meal_id
        LEFT JOIN categories c ON m.category_id = c.category_id
        WHERE si.list_id = $list_id
        ORDER BY si.purchased ASC, COALESCE(c.category_id, 999), si.item_name";

$result = $conn->query($sql);
$items_by_category = [];
$total_items = 0;
$purchased = 0;

while ($row = $result->fetch_assoc()) {
    $cat = $row['category_name'] ?? 'Other';
    if (!isset($items_by_category[$cat])) {
        $items_by_category[$cat] = [];
    }
    $items_by_category[$cat][] = $row;
    $total_items++;
    if ($row['purchased']) $purchased++;
}

$progress = $total_items > 0 ? ($purchased / $total_items) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping List - NutriPlan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
</head>
<body>
    <div class="app-shell">
        <?php include 'components/sidebar.php'; ?>
        
        <main class="main page-enter">
            <!-- Topbar -->
            <div class="topbar flex-between">
                <h1>🛒 Shopping List</h1>
                <div style="font-size: var(--text-sm); color: var(--text-2);">
                    <span class="progress-text"><?php echo $purchased; ?> of <?php echo $total_items; ?></span>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div style="margin-bottom: var(--sp-8);">
                <div style="height: 8px; background: var(--border); border-radius: 4px; overflow: hidden;">
                    <div class="progress-bar" style="height: 100%; background: var(--grad-success); width: <?php echo $progress; ?>%; transition: width 300ms var(--ease-smooth);"></div>
                </div>
            </div>
            
            <!-- Shopping Items by Category -->
            <div style="margin-bottom: var(--sp-8);">
                <?php foreach ($items_by_category as $category => $items): ?>
                <div style="margin-bottom: var(--sp-8);">
                    <h3 style="margin-bottom: var(--sp-4); color: var(--text-2);"><?php echo $category; ?></h3>
                    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden;">
                        <?php foreach ($items as $item): ?>
                        <div class="list-item <?php echo $item['purchased'] ? 'checked' : ''; ?>" data-item-id="<?php echo $item['item_id']; ?>" style="display: flex; align-items: center; padding: var(--sp-4); border-bottom: 1px solid var(--border); transition: opacity var(--dur-normal); <?php echo $item['purchased'] ? 'opacity: 0.5;' : ''; ?>">
                            <input type="checkbox" class="list-checkbox" <?php echo $item['purchased'] ? 'checked' : ''; ?> onchange="toggleShoppingItem(<?php echo $item['item_id']; ?>)" style="width: 20px; height: 20px; margin-right: var(--sp-4); cursor: pointer;">
                            <div style="flex: 1;">
                                <div style="color: var(--text-1); font-weight: 500; <?php echo $item['purchased'] ? 'text-decoration: line-through;' : ''; ?>"><?php echo $item['item_name']; ?></div>
                                <div style="color: var(--text-2); font-size: var(--text-sm); margin-top: var(--sp-1);"><?php echo $item['quantity']; ?></div>
                            </div>
                            <button class="btn-ghost" onclick="deleteShoppingItem(<?php echo $item['item_id']; ?>)" style="border: none; background: none; color: var(--danger); cursor: pointer; padding: var(--sp-2);">🗑</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Add Custom Item -->
            <div style="background: var(--overlay); border: 1px solid var(--border); border-radius: 12px; padding: var(--sp-6);">
                <p style="font-size: var(--text-sm); color: var(--text-2); margin-bottom: var(--sp-4);">Not finding something? Add a custom item.</p>
                <form style="display: grid; grid-template-columns: 1fr 1fr auto; gap: var(--sp-4);">
                    <input type="text" id="custom-item-name" placeholder="Item name" style="padding: var(--sp-3); background: var(--inset); border: 1px solid var(--border); border-radius: 8px; color: var(--text-1);">
                    <input type="text" id="custom-item-qty" placeholder="Quantity" style="padding: var(--sp-3); background: var(--inset); border: 1px solid var(--border); border-radius: 8px; color: var(--text-1);">
                    <button type="button" class="btn btn-primary btn-sm" onclick="addCustomItem()">Add</button>
                </form>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js" defer></script>
    <script>
        function addCustomItem() {
            const name = document.getElementById('custom-item-name').value;
            const qty = document.getElementById('custom-item-qty').value || '1';
            
            if (!name) {
                showToast('Please enter item name', 'warning');
                return;
            }
            
            fetch('/api/shopping_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=add_custom&name=${encodeURIComponent(name)}&qty=${encodeURIComponent(qty)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('custom-item-name').value = '';
                    document.getElementById('custom-item-qty').value = '';
                    showToast('Item added!', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.message, 'error');
                }
            });
        }
    </script>
</body>
</html>
