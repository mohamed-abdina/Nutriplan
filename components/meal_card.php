<?php
// Meal Card Component
// Usage: include 'components/meal_card.php';
// Params: $meal (array)
?>
<article class="meal-card stagger-item" style="--card-accent: var(--primary);">
    <div class="card-accent-strip"></div>
    <div class="card-body">
        <div class="card-icon"><?php echo htmlspecialchars($meal['meal_icon']); ?></div>
        <div style="flex: 1;">
            <div class="card-title"><?php echo htmlspecialchars($meal['meal_name']); ?></div>
            <span class="card-category"><?php echo htmlspecialchars($meal['category_name']); ?></span>
            <p class="card-nutrients">Cal: <?php echo htmlspecialchars($meal['calories']); ?> · Protein: <?php echo htmlspecialchars($meal['proteins_g']); ?>g</p>
        </div>
    </div>
    <div class="card-actions">
        <button class="btn-ghost btn-sm" onclick="addToShoppingList(<?php echo (int)$meal['meal_id']; ?>)">+ Add</button>
        <a href="meal.php?id=<?php echo (int)$meal['meal_id']; ?>" class="btn-outline btn-sm">Details →</a>
    </div>
</article>
