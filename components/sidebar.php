<?php
// Sidebar component
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$nav_items = [
    'dashboard' => ['icon' => 'home', 'label' => 'Dashboard'],
    'search' => ['icon' => 'search', 'label' => 'Search Meals'],
    'shopping' => ['icon' => 'shopping', 'label' => 'Shopping List'],
    'profile' => ['icon' => 'user', 'label' => 'Profile']
];
?>

<!-- Sidebar overlay backdrop (mobile) -->
<div class="sidebar-overlay" role="presentation"></div>

<!-- Hamburger for mobile/tablet -->
<button class="hamburger" aria-label="Open navigation" aria-controls="sidebarNav" aria-expanded="false" tabindex="0" role="button">
    <span class="hamburger-bar"></span>
    <span class="hamburger-bar"></span>
    <span class="hamburger-bar"></span>
</button>
<nav class="sidebar" id="sidebarNav" aria-label="Main navigation" tabindex="-1" role="navigation">
    <div class="sidebar-logo">
        <img src="assets/icons/svg/meal.svg" alt="NutriPlan Logo" style="width: 2em; height: 2em; vertical-align: middle; margin-right: 0.3em;">
        NutriPlan
    </div>
    <div class="sidebar-divider"></div>
    <ul class="nav-list">
        <?php foreach ($nav_items as $page => $item): ?>
        <li class="nav-item <?php echo $current_page === $page ? 'active' : ''; ?>">
            <a href="<?php echo $page; ?>.php">
                <img src="assets/icons/svg/<?php echo $item['icon']; ?>.svg" alt="<?php echo $item['label']; ?> icon" style="width: 1.3em; height: 1.3em; vertical-align: middle; margin-right: 0.5em;">
                <span class="label"><?php echo $item['label']; ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <div class="sidebar-footer">
        <button class="btn btn-sm" id="themeToggle">🌙 Dark Mode</button>
        <a href="logout.php" class="btn btn-sm btn-danger-ghost">⎋ Logout</a>
    </div>
</nav>
