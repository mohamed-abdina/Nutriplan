<?php
// Meal Card Component
// Usage: include 'components/meal_card.php';
// Params: $meal (array)

// Determine nutrition status badges
$calories = (int)($meal['calories'] ?? 0);
$protein = (int)($meal['proteins_g'] ?? 0);
$nutrition_level = '';
$nutrition_color = 'var(--primary)';
$meal_icon_raw = trim((string)($meal['meal_icon'] ?? ''));
$meal_icon_display = $meal_icon_raw;

if ($meal_icon_raw === '' || preg_match('/^[a-z0-9\-\_\s]+$/i', $meal_icon_raw)) {
    $category_name = strtolower(trim((string)($meal['category_name'] ?? '')));
    if (str_contains($category_name, 'breakfast')) {
        $meal_icon_display = '🍳';
    } elseif (str_contains($category_name, 'lunch')) {
        $meal_icon_display = '🥗';
    } elseif (str_contains($category_name, 'dinner') || str_contains($category_name, 'supper')) {
        $meal_icon_display = '🍽️';
    } elseif (str_contains($category_name, 'snack')) {
        $meal_icon_display = '🥜';
    } else {
        $meal_icon_display = '🍽️';
    }
}

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
            <div class="card-icon" aria-hidden="true"><?php echo htmlspecialchars($meal_icon_display); ?></div>
            <div style="flex: 1; min-width: 0;">
                <div class="card-title"><?php echo htmlspecialchars($meal['meal_name']); ?></div>
                <span class="card-category"><?php echo htmlspecialchars($meal['category_name']); ?></span>
                
                <!-- Enhanced Nutrition Display - RESPONSIVE BADGES -->
                <div class="card-badges">
                    <div class="nutrition-badge badge-primary" title="<?php echo (int)$calories; ?> calories">
                        🔥 <?php echo htmlspecialchars((string)$calories); ?> cal
                    </div>
                    <div class="nutrition-badge badge-accent" title="<?php echo (int)$protein; ?>g protein">
                        💪 <?php echo htmlspecialchars((string)$protein); ?>g
                    </div>
                    <?php if ($nutrition_level): ?>
                    <div class="nutrition-badge badge-level" style="--badge-color: <?php echo htmlspecialchars($nutrition_color); ?>;" title="<?php echo htmlspecialchars($nutrition_level); ?>">
                        ✨ <?php echo htmlspecialchars($nutrition_level); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="card-actions">
        <button class="btn btn-ghost btn-sm" onclick="addToShoppingList(<?php echo (int)$meal['meal_id']; ?>)" aria-label="Add <?php echo htmlspecialchars($meal['meal_name']); ?> to shopping list">+ Add</button>
        <a href="meal.php?meal_id=<?php echo (int)$meal['meal_id']; ?>" class="btn btn-outline btn-sm">Details →</a>
    </div>
</article>
