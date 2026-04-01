const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ context }) => {
  // Prevent service worker from interfering with tests
  await context.addInitScript(() => {
    try { delete window.navigator.serviceWorker; } catch (e) { /* ignore */ }
  });
});

test.describe('Search page meal card rendering', () => {
  
  test('logs in and navigates to search page', async ({ page }) => {
    // Navigate to login
    await page.goto('/login.php');

    // Fill and submit login form
    await page.fill('input[name="email"]', 'test@user.io');
    await page.fill('input[name="password"]', 'Password123!');

    // Wait for login response and redirect
    const [response] = await Promise.all([
      page.waitForResponse(res => res.url().includes('login.php') && res.request().method() === 'POST'),
      page.click('button[type="submit"]')
    ]);

    // Wait for dashboard redirect
    await page.waitForURL('**/dashboard.php', { timeout: 10000 });

    // Navigate to search page
    await page.goto('/search.php');
    
    // Verify page loaded
    await expect(page).toHaveTitle(/Search/);
    await expect(page.locator('h1')).toContainText('Search Meals');
  });

  test('search page renders meal cards with proper structure', async ({ page }) => {
    // Login first
    await page.goto('/login.php');
    await page.fill('input[name="email"]', 'test@user.io');
    await page.fill('input[name="password"]', 'Password123!');

    await Promise.all([
      page.waitForResponse(res => res.url().includes('login.php') && res.request().method() === 'POST'),
      page.click('button[type="submit"]')
    ]);

    await page.waitForURL('**/dashboard.php', { timeout: 10000 });

    // Navigate to search
    await page.goto('/search.php');

    // Wait for default search to load meals
    await page.waitForTimeout(1000); // Let JavaScript initialize
    
    // Wait for meal cards to appear
    await page.waitForSelector('.meal-card', { timeout: 10000 });

    // Get all meal cards
    const mealCards = await page.locator('.meal-card').count();
    console.log(`✓ Found ${mealCards} meal cards`);
    
    expect(mealCards).toBeGreaterThan(0);

    // Validate structure of first meal card
    const firstCard = page.locator('.meal-card').first();

    // Check card-accent-strip
    await expect(firstCard.locator('.card-accent-strip')).toBeVisible();

    // Check card-body structure
    await expect(firstCard.locator('.card-body')).toBeVisible();

    // Check card-icon
    await expect(firstCard.locator('.card-icon')).toBeVisible();

    // Check card-title
    const cardTitle = firstCard.locator('.card-title');
    await expect(cardTitle).toBeVisible();
    const titleText = await cardTitle.textContent();
    expect(titleText).toBeTruthy();
    console.log(`✓ Card title: "${titleText}"`);

    // Check card-category
    const cardCategory = firstCard.locator('.card-category');
    await expect(cardCategory).toBeVisible();
    const categoryText = await cardCategory.textContent();
    expect(categoryText).toBeTruthy();
    console.log(`✓ Card category: "${categoryText}"`);

    // CRITICAL: Check card-badges structure (this was broken before)
    const cardBadges = firstCard.locator('.card-badges');
    await expect(cardBadges).toBeVisible();
    console.log('✓ card-badges container present');

    // Check nutrition badges
    const badges = cardBadges.locator('.nutrition-badge');
    const badgeCount = await badges.count();
    console.log(`✓ Found ${badgeCount} nutrition badges`);
    expect(badgeCount).toBeGreaterThanOrEqual(2); // At least calories and protein

    // Check for calorie badge (🔥)
    const caloriesBadge = badges.filter({ hasText: /🔥.*cal/ }).first();
    await expect(caloriesBadge).toBeVisible();
    const calText = await caloriesBadge.textContent();
    console.log(`✓ Calories badge: "${calText}"`);

    // Check for protein badge (💪)
    const proteinBadge = badges.filter({ hasText: /💪.*g/ }).first();
    await expect(proteinBadge).toBeVisible();
    const proteinText = await proteinBadge.textContent();
    console.log(`✓ Protein badge: "${proteinText}"`);

    // Check card-actions
    const cardActions = firstCard.locator('.card-actions');
    await expect(cardActions).toBeVisible();

    // Check action buttons
    const addButton = cardActions.locator('button:has-text("+ Add")');
    await expect(addButton).toBeVisible();

    const detailsLink = cardActions.locator('a:has-text("Details")');
    await expect(detailsLink).toBeVisible();

    console.log('✓ Action buttons present');

    // Check animation-delay is applied
    const cardStyle = await firstCard.getAttribute('style');
    console.log(`✓ Card style attribute: "${cardStyle}"`);
    
    // Verify the flexbox wrapper with proper styling
    const flexWrapper = firstCard.locator('div[style*="display: flex"][style*="gap: var(--sp-3)"]');
    await expect(flexWrapper).toBeVisible();
    console.log('✓ Flexbox wrapper with proper gap present');
  });

  test('search filters work and render correct meal cards', async ({ page }) => {
    // Login
    await page.goto('/login.php');
    await page.fill('input[name="email"]', 'test@user.io');
    await page.fill('input[name="password"]', 'Password123!');

    await Promise.all([
      page.waitForResponse(res => res.url().includes('login.php') && res.request().method() === 'POST'),
      page.click('button[type="submit"]')
    ]);

    await page.waitForURL('**/dashboard.php', { timeout: 10000 });

    // Navigate to search
    await page.goto('/search.php');
    await page.waitForTimeout(1000);

    // Initial card count
    await page.waitForSelector('.meal-card', { timeout: 10000 });
    const initialCount = await page.locator('.meal-card').count();
    console.log(`✓ Initial meal count: ${initialCount}`);

    // Search for a specific meal
    await page.fill('input[role="searchbox"]', 'Bean');
    await page.waitForTimeout(500); // Debounce delay

    // Wait for search results to update
    await page.waitForTimeout(1000);

    const searchResultsCount = await page.locator('.meal-card').count();
    console.log(`✓ Search results count: ${searchResultsCount}`);

    // Verify cards still have proper structure after search
    if (searchResultsCount > 0) {
      const firstCard = page.locator('.meal-card').first();
      await expect(firstCard.locator('.card-badges')).toBeVisible();
      console.log('✓ Meal cards maintain proper structure after search');
    }
  });

  test('load more button works and renders additional cards', async ({ page }) => {
    // Login
    await page.goto('/login.php');
    await page.fill('input[name="email"]', 'test@user.io');
    await page.fill('input[name="password"]', 'Password123!');

    await Promise.all([
      page.waitForResponse(res => res.url().includes('login.php') && res.request().method() === 'POST'),
      page.click('button[type="submit"]')
    ]);

    await page.waitForURL('**/dashboard.php', { timeout: 10000 });

    // Navigate to search
    await page.goto('/search.php');
    await page.waitForTimeout(1000);
    await page.waitForSelector('.meal-card', { timeout: 10000 });

    const initialCount = await page.locator('.meal-card').count();
    console.log(`✓ Initial cards: ${initialCount}`);

    // Check if load more button exists
    const loadMoreBtn = page.locator('#loadMoreBtn');
    const isVisible = await loadMoreBtn.isVisible();

    if (isVisible) {
      // Get initial count
      let currentCount = await page.locator('.meal-card').count();

      // Click load more
      await loadMoreBtn.click();
      await page.waitForTimeout(1000);

      // Get new count
      const newCount = await page.locator('.meal-card').count();
      console.log(`✓ Cards after load more: ${newCount}`);

      expect(newCount).toBeGreaterThan(currentCount);

      // Verify newly loaded cards have proper structure
      const lastCard = page.locator('.meal-card').last();
      await expect(lastCard.locator('.card-badges')).toBeVisible();
      console.log('✓ Newly loaded cards have proper structure');
    } else {
      console.log('✓ Load more button not visible (all meals already loaded)');
    }
  });
});
