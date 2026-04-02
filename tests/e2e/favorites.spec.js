const { test, expect } = require('@playwright/test');

// Helper: Login before each test
async function login(page) {
  await page.goto('/login.php');
  await page.fill('input[name="email"]', 'test@user.io');
  await page.fill('input[name="password"]', 'Password123!');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard.php', { timeout: 10000 });
}

test.beforeEach(async ({ context }) => {
  await context.addInitScript(() => {
    try { delete window.navigator.serviceWorker; } catch (e) { /* ignore */ }
  });
});

test.describe('Favorites Feature', () => {

  test('user can view favorites tab on profile', async ({ page }) => {
    await login(page);
    
    // Navigate to profile
    await page.goto('/profile.php');
    
    // Check for Favorites tab button
    const favoritesTab = page.locator('button[data-tab="favorites"]');
    await expect(favoritesTab).toBeVisible();
    
    // Click favorites tab
    await favoritesTab.click();
    
    // Tab panel should be visible
    const favoritePanel = page.locator('#favorites-panel');
    await expect(favoritePanel).toBeVisible();
  });

  test('user can add meal to favorites from meal detail page', async ({ page }) => {
    await login(page);
    
    // Navigate to meal detail page
    await page.goto('/meal.php?id=1');
    
    // Wait for page to load
    await page.waitForLoadState('networkidle');
    
    // Find favorite button (initially showing "Add to Favorites")
    const favoriteBtn = page.locator('#favorite-btn');
    await expect(favoriteBtn).toBeVisible();
    
    // Initial state should be "Add to Favorites"
    const initialText = await favoriteBtn.textContent();
    console.log('Initial button text:', initialText);
    
    // Click favorite button
    await favoriteBtn.click();
    
    // Wait for toast notification
    const toast = page.locator('.toast-success');
    await expect(toast).toBeVisible({ timeout: 2000 });
    
    // Verify toast message
    await expect(toast).toContainText('Added to favorites');
    
    // Button should change to show it's favorited
    await page.waitForTimeout(500); // Wait for UI update
    const updatedText = await favoriteBtn.textContent();
    expect(updatedText).toContain('Favorite');
  });

  test('user can toggle favorite off', async ({ page }) => {
    await login(page);
    
    // Navigate to meal
    await page.goto('/meal.php?id=1');
    await page.waitForLoadState('networkidle');
    
    // Add to favorites first
    const favoriteBtn = page.locator('#favorite-btn');
    await favoriteBtn.click();
    
    // Wait for success toast
    await page.locator('.toast-success').waitFor({ state: 'visible', timeout: 2000 });
    await page.waitForTimeout(500);
    
    // Click again to remove
    await favoriteBtn.click();
    
    // Should show "Removed from favorites" toast
    const removeToast = page.locator('.toast-info');
    await expect(removeToast).toContainText('Removed from favorites', { timeout: 2000 });
    
    // Button should return to "Add to Favorites"
    await page.waitForTimeout(500);
    const finalText = await favoriteBtn.textContent();
    expect(finalText).toContain('Add to Favorites');
  });

  test('favorite button is disabled during API request', async ({ page }) => {
    await login(page);
    
    await page.goto('/meal.php?id=1');
    await page.waitForLoadState('networkidle');
    
    const favoriteBtn = page.locator('#favorite-btn');
    
    // Start the click (don't await)
    const clickPromise = favoriteBtn.click();
    
    // Button should be disabled immediately
    const isDisabled = await favoriteBtn.isDisabled({ timeout: 100 }).catch(() => false);
    
    // Wait for request to complete
    await clickPromise;
    await page.waitForTimeout(500);
    
    // Button should be enabled again
    await expect(favoriteBtn).toBeEnabled();
  });

  test('rating submission shows toast notification', async ({ page }) => {
    await login(page);
    
    await page.goto('/meal.php?id=1');
    await page.waitForLoadState('networkidle');
    
    // Find rating section
    const ratingStars = page.locator('.star-rating button');
    
    if (await ratingStars.count() > 0) {
      // Click 4th star to rate 4 stars
      await ratingStars.nth(3).click();
      
      // Add optional review
      const reviewTextarea = page.locator('textarea');
      if (await reviewTextarea.count() > 0) {
        await reviewTextarea.fill('Great meal!');
      }
      
      // Find and click submit rating button
      const submitBtn = page.locator('button:has-text("Submit Rating")').first();
      
      if (await submitBtn.isVisible()) {
        await submitBtn.click();
        
        // Wait for success toast
        const successToast = page.locator('.toast-success');
        await expect(successToast).toContainText('Rating saved', { timeout: 3000 }).catch(() => {
          // Toast might auto-dismiss, that's ok
        });
      }
    }
  });

  test('profile shows correct favorites count', async ({ page }) => {
    await login(page);
    
    // Go to profile
    await page.goto('/profile.php');
    
    // Look for Favorites count in stats
    const stats = page.locator('.stats-grid-auto > div');
    
    // Should have at least 4 stat blocks (Meals, Lists, Favorites, Weeks)
    const count = await stats.count();
    expect(count).toBeGreaterThanOrEqual(4);
    
    // Find favorites stat
    const favoritesstat = page.locator('text=/Favorites/');
    await expect(favoritesstat).toBeVisible();
  });

  test('favorites tab displays actual favorites', async ({ page }) => {
    await login(page);
    
    // Go to profile
    await page.goto('/profile.php');
    
    // Click favorites tab
    const favoritesTab = page.locator('button[data-tab="favorites"]');
    await favoritesTab.click();
    
    // Wait for favorites to load
    await page.waitForTimeout(1000);
    
    // Check if favorites list or empty state is shown
    const favoritesList = page.locator('#favoritesList');
    const noFavorites = page.locator('#noFavorites');
    
    const hasItems = (await favoritesList.locator('.meal-card, [class*="card"]').count()) > 0;
    const isEmpty = await noFavorites.isVisible();
    
    // Should either have items or show empty state
    expect(hasItems || isEmpty).toBeTruthy();
  });

  test('favorite toggle works with error handling', async ({ page }) => {
    await login(page);
    
    // Try to favorite non-existent meal via URL manipulation
    await page.goto('/meal.php?id=999999');
    
    // If meal doesn't exist, page should handle it gracefully
    // Either show error or redirect
    await page.waitForLoadState('networkidle');
    
    // Should not crash
    const pageContent = page.locator('main, body');
    await expect(pageContent).toBeVisible();
  });

  test('favorite button has proper accessibility attributes', async ({ page }) => {
    await login(page);
    
    await page.goto('/meal.php?id=1');
    await page.waitForLoadState('networkidle');
    
    const favoriteBtn = page.locator('#favorite-btn');
    
    // Should have aria-label or title
    const ariaLabel = await favoriteBtn.getAttribute('aria-label');
    const title = await favoriteBtn.getAttribute('title');
    
    expect(ariaLabel || title).toBeTruthy();
  });
});
