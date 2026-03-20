<?php
// Sidebar component
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$nav_items = [
    'dashboard' => '🏠 Dashboard',
    'search' => '🔍 Search Meals',
    'shopping' => '🛒 Shopping List',
    'profile' => '👤 Profile'
];
?>
<nav class="sidebar">
    <div class="sidebar-logo">🍽 NutriPlan</div>
    <div class="sidebar-divider"></div>
    <ul class="nav-list">
        <?php foreach ($nav_items as $page => $label): ?>
        <li class="nav-item <?php echo $current_page === $page ? 'active' : ''; ?>">
            <a href="<?php echo $page; ?>.php"><?php echo $label; ?></a>
        </li>
        <?php endforeach; ?>
    </ul>
    <div class="sidebar-footer">
        <button class="btn btn-sm" id="themeToggle" style="width: 100%; justify-content: center;">🌙 Dark Mode</button>
        <a href="logout.php" class="btn btn-sm btn-danger-ghost" style="width: 100%; justify-content: center;">⎋ Logout</a>
    </div>
</nav>
