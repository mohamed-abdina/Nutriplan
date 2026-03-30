<?php
// Stat Card Component
// Usage: include 'components/stat_card.php';
// Params: $label, $value, $color, $icon (optional), $trend (optional)
?>
<div class="stat-card" style="--stat-color: <?php echo htmlspecialchars($color); ?>;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--sp-3);">
        <div class="stat-label"><?php echo htmlspecialchars($label); ?></div>
        <?php if (isset($icon)): ?>
        <div style="font-size: 1.5rem;"><?php echo htmlspecialchars($icon); ?></div>
        <?php endif; ?>
    </div>
    <div style="display: flex; align-items: baseline; gap: var(--sp-2);">
        <div class="stat-value" data-count="<?php echo htmlspecialchars((string)$value); ?>" style="font-size: var(--text-3xl); font-weight: 700;">0</div>
        <?php if (isset($trend) && !empty($trend)): ?>
        <div style="font-size: 1.25rem; color: var(--stat-color); opacity: 0.7;"><?php echo htmlspecialchars($trend); ?></div>
        <?php endif; ?>
    </div>
</div>
