<?php
require_once __DIR__ . '/includes/session.php';
secure_session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Get or create default shopping list
$list = pdo_fetch_one("SELECT list_id FROM shopping_lists WHERE user_id = ? ORDER BY created_at DESC LIMIT 1", [$user_id]);
if ($list) {
    $list_id = $list['list_id'];
} else {
    pdo_query("INSERT INTO shopping_lists (user_id, list_name) VALUES (?, ?)", [$user_id, 'My Shopping List']);
    // For lastInsertId, use PDO directly
    $list_id = get_db()->lastInsertId();
}

// Get items grouped by category
$sql = "SELECT si.item_id, si.item_name, si.quantity, si.purchased, si.custom_item, 
        c.category_name, c.category_id
        FROM shopping_items si
        LEFT JOIN meals m ON si.meal_id = m.meal_id
        LEFT JOIN categories c ON m.category_id = c.category_id
        WHERE si.list_id = $list_id
        ORDER BY si.purchased ASC, COALESCE(c.category_id, 999), si.item_name";

$fetched = pdo_fetch_all($sql) ?? [];
$items_by_category = [];
$total_items = 0;
$purchased = 0;

foreach ($fetched as $row) {
    $cat = $row['category_name'] ?? 'Other';
    if (!isset($items_by_category[$cat])) {
        $items_by_category[$cat] = [];
    }
    $items_by_category[$cat][] = $row;
    $total_items++;
    if ((int)$row['purchased'] === 1) $purchased++;
}

$progress = $total_items > 0 ? ($purchased / $total_items) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping List - NutriPlan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
    <?php require_once __DIR__ . '/includes/csrf.php'; ?>
    <script>window.CSRF_TOKEN = '<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>';</script>
</head>
<body>
    <div class="app-shell">
        <?php include 'components/sidebar.php'; ?>
        
        <main class="main page-enter">
            <!-- Topbar -->
            <div class="topbar flex-between">
                <h1>🛒 Shopping List</h1>
                <div style="font-size: var(--text-sm); color: var(--text-2);" aria-label="Shopping list progress" role="status">
                    <span class="progress-text"><span aria-hidden="true"><?php echo $purchased; ?> of <?php echo $total_items; ?></span> items purchased</span>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="progress-container">
                <div class="progress-bar-track">
                    <div class="progress-bar" style="width: <?php echo $progress; ?>%;"></div>
                </div>
            </div>
            
            <!-- Shopping Items by Category -->
            <div style="margin-bottom: var(--sp-8);">
                <?php foreach ($items_by_category as $category => $items): ?>
                <div style="margin-bottom: var(--sp-8);">
                    <h3 style="margin-bottom: var(--sp-4); color: var(--text-2);"><?php echo htmlspecialchars($category); ?></h3>
                    <ul style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; list-style: none; padding: 0; margin: 0;" role="list">
                        <?php foreach ($items as $item): ?>
                        <li class="list-item-layout <?php echo $item['purchased'] ? 'list-item-checked' : ''; ?>" data-item-id="<?php echo (int)$item['item_id']; ?>" role="listitem">
                            <label style="display: flex; align-items: center; gap: var(--sp-3); flex: 1; width: 100%; padding: var(--sp-4) var(--sp-3);">
                                <input type="checkbox" class="list-item-checkbox" <?php echo $item['purchased'] ? 'checked' : ''; ?> aria-label="Mark <?php echo htmlspecialchars($item['item_name']); ?> as purchased" onchange="toggleShoppingItem(<?php echo (int)$item['item_id']; ?>)">
                                <span class="list-item-text" style="flex: 1; <?php echo $item['purchased'] ? 'text-decoration: line-through; color: var(--text-3);' : ''; ?>"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                <span class="list-item-quantity" style="font-size: var(--text-sm); color: var(--text-2);"><?php echo htmlspecialchars($item['quantity']); ?></span>
                            </label>
                            <button class="list-item-delete" aria-label="Delete <?php echo htmlspecialchars($item['item_name']); ?>" onclick="deleteShoppingItem(<?php echo (int)$item['item_id']; ?>)" title="Delete item">🗑</button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Add Custom Item -->
            <div style="background: var(--overlay); border: 1px solid var(--border); border-radius: 12px; padding: var(--sp-6);">
                <p style="font-size: var(--text-sm); color: var(--text-2); margin-bottom: var(--sp-4);">Not finding something? Add a custom item.</p>
                <form style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: var(--sp-4);" class="custom-item-form" novalidate>
                    <input type="text" id="custom-item-name" placeholder="Item name" class="form-input" required aria-label="Custom item name">
                    <input type="text" id="custom-item-qty" placeholder="Quantity" class="form-input" aria-label="Quantity">
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
            showLoader(true);
            fetch('/api/shopping_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=add_custom&name=${encodeURIComponent(name)}&qty=${encodeURIComponent(qty)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN || '')}`
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
            })
            .finally(() => { showLoader(false); });
        }
    </script>
</body>
</html>
