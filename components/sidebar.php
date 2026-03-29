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

<!-- Hamburger for mobile/tablet -->
<button class="hamburger" aria-label="Open navigation" aria-controls="sidebarNav" aria-expanded="false" tabindex="0" role="button">
    <span class="hamburger-bar"></span>
    <span class="hamburger-bar"></span>
    <span class="hamburger-bar"></span>
</button>
<nav class="sidebar" id="sidebarNav" aria-label="Main navigation" tabindex="-1" role="navigation">
    <div class="sidebar-logo">🍽 NutriPlan</div>
    <div class="sidebar-divider"></div>
    <ul class="nav-list">
        <?php foreach ($nav_items as $page => $label): ?>
        <li class="nav-item <?php echo $current_page === $page ? 'active' : ''; ?>">
            <a href="<?php echo $page; ?>.php"><span class="label"><?php echo $label; ?></span></a>
        </li>
        <?php endforeach; ?>
    </ul>
    <div class="sidebar-footer">
        <button class="btn btn-sm" id="themeToggle">🌙 Dark Mode</button>
        <a href="logout.php" class="btn btn-sm btn-danger-ghost">⎋ Logout</a>
    </div>
</nav>
