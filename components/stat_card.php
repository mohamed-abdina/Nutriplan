<?php
// Stat Card Component
// Usage: include 'components/stat_card.php';
// Params: $label, $value, $color
?>
<div class="stat-card" style="--stat-color: <?php echo htmlspecialchars($color); ?>;">
    <div class="stat-label"><?php echo htmlspecialchars($label); ?></div>
    <div class="stat-value" data-count="<?php echo htmlspecialchars($value); ?>">0</div>
</div>
