<?php
// Meal Card Component
// Usage: include 'components/meal_card.php';
// Params: $meal (array)

// Determine nutrition status badges
$calories = (int)($meal['calories'] ?? 0);
$protein = (int)($meal['proteins_g'] ?? 0);
$nutrition_level = '';
$nutrition_color = 'var(--primary)';

if ($protein > 25) {
    $nutrition_level = 'High Protein';
    $nutrition_color = 'var(--accent)';
} elseif ($calories < 300) {
    $nutrition_level = 'Low Cal';
    $nutrition_color = 'var(--success)';
} elseif ($calories > 700) {
    $nutrition_level = 'Hearty';
    $nutrition_color = 'var(--warning)';
}
?>
<article class="meal-card stagger-item" style="--card-accent: <?php echo htmlspecialchars($nutrition_color); ?>;">
    <div class="card-accent-strip"></div>
    <div class="card-body">
        <div style="display: flex; gap: var(--sp-3); width: 100%;">
            <div class="card-icon"><?php echo htmlspecialchars($meal['meal_icon']); ?></div>
            <div style="flex: 1; min-width: 0;">
                <div class="card-title"><?php echo htmlspecialchars($meal['meal_name']); ?></div>
                <span class="card-category"><?php echo htmlspecialchars($meal['category_name']); ?></span>
                
                <!-- Enhanced Nutrition Display -->
                <div style="display: flex; gap: var(--sp-2); margin-top: var(--sp-2); flex-wrap: wrap;">
                    <div style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; background: rgba(var(--primary-rgb, 59, 130, 246), 0.1); border-radius: 6px; font-size: var(--text-xs); color: var(--primary); font-weight: 500;">
                        🔥 <?php echo htmlspecialchars((string)$calories); ?> cal
                    </div>
                    <div style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; background: rgba(var(--accent-rgb, 168, 85, 247), 0.1); border-radius: 6px; font-size: var(--text-xs); color: var(--accent); font-weight: 500;">
                        💪 <?php echo htmlspecialchars((string)$protein); ?>g protein
                    </div>
                    <?php if ($nutrition_level): ?>
                    <div style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; background: rgba(<?php echo htmlspecialchars($nutrition_color); ?>, 0.1); border-radius: 6px; font-size: var(--text-xs); color: <?php echo htmlspecialchars($nutrition_color); ?>; font-weight: 500;">
                        ✨ <?php echo htmlspecialchars($nutrition_level); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="card-actions">
        <button class="btn-ghost btn-sm" onclick="addToShoppingList(<?php echo (int)$meal['meal_id']; ?>)" aria-label="Add <?php echo htmlspecialchars($meal['meal_name']); ?> to shopping list">+ Add</button>
        <a href="meal.php?id=<?php echo (int)$meal['meal_id']; ?>" class="btn-outline btn-sm">Details →</a>
    </div>
</article>
