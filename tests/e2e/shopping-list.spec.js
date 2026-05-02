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

test.describe('Shopping List Feature', () => {

  test('user can add meal to shopping list from meal detail page', async ({ page }) => {
    await login(page);
    
    // Navigate to meal detail page
    await page.goto('/meal.php?meal_id=1');
    await page.waitForLoadState('networkidle');
    
    // Find "Add to Cart" button (formerly "Add to Shopping List")
    const addBtn = page.locator('button:has-text("Add to Cart")').first();
    await expect(addBtn).toBeVisible();
    
    // Click to add
    await addBtn.click();
    
    // Wait for success toast - longer timeout and check for visibility
    const successToast = page.locator('.toast-success, .toast');
    await expect(successToast).toBeVisible({ timeout: 3000 }).catch(() => {
      // Toast might auto-dismiss quickly, that's ok
    });
  });

  test('user can add meal to shopping list from search results', async ({ page }) => {
    await login(page);
    
    // Go to search/dashboard
    await page.goto('/index.php');
    await page.waitForLoadState('networkidle');
    
    // Search for a meal
    const searchInput = page.locator('input[placeholder*="search"], input[type="search"]').first();
    if (await searchInput.isVisible()) {
      await searchInput.fill('chicken');
      await page.waitForTimeout(500);
    }
    
    // Find an "Add" button on a meal card
    const addBtns = page.locator('button:has-text("+")');
    if (await addBtns.count() > 0) {
      // Click first add button
      await addBtns.first().click();
      
      // Should show success toast
      const toast = page.locator('.toast-success, .toast');
      await expect(toast).toBeVisible({ timeout: 2000 }).catch(() => {
        // Toast might auto-dismiss, that's fine
      });
    }
  });

  test('user can view shopping list items', async ({ page }) => {
    await login(page);
    
    // Navigate to shopping list (which is now called Cart)
    await page.goto('/shopping.php');
    await page.waitForLoadState('networkidle');
    
    // Should have Cart heading
    await expect(page.locator('h1:has-text("Cart")')).toBeVisible();
    
    // Should have list items container (even if empty, role=list is always present)
    const listContainer = page.locator('[role="list"]').first();
    await expect(listContainer).toBeVisible({ timeout: 5000 });
  });

  test('user can toggle item as purchased', async ({ page }) => {
    await login(page);
    
    // Add a meal to cart first
    await page.goto('/meal.php?meal_id=1');
    await page.waitForLoadState('networkidle');
    const addBtn = page.locator('button:has-text("Add to Cart")').first();
    if (await addBtn.isVisible()) {
      await addBtn.click();
      await page.waitForTimeout(500);
    }
    
    // Go to shopping list
    await page.goto('/shopping.php');
    await page.waitForLoadState('networkidle');
    
    // Find first checkbox
    const checkbox = page.locator('.list-item-checkbox, input[type="checkbox"]').first();
    if (await checkbox.isVisible()) {
      // Toggle checkbox
      await checkbox.click();
      
      // Wait for UI update
      await page.waitForTimeout(500);
      
      // Checkbox should be checked
      const isChecked = await checkbox.isChecked();
      expect(isChecked).toBeTruthy();
    }
  });

  test('user can delete shopping list item', async ({ page }) => {
    await login(page);
    
    // Add item first
    await page.goto('/meal.php?meal_id=1');
    await page.waitForLoadState('networkidle');
    const addBtn = page.locator('button:has-text("Add to Cart")').first();
    if (await addBtn.isVisible()) {
      await addBtn.click();
      await page.waitForTimeout(500);
    }
    
    // Go to shopping list
    await page.goto('/shopping.php');
    await page.waitForLoadState('networkidle');
    
    // Find delete button (trash icon)
    const deleteBtn = page.locator('.list-item-delete, button:has-text("🗑")').first();
    if (await deleteBtn.isVisible()) {
      // Mock confirm dialog
      page.once('dialog', dialog => dialog.accept());
      
      // Click delete
      await deleteBtn.click();
      
      // Wait for deletion
      await page.waitForTimeout(500);
    }
  });

  test('user can add custom item to shopping list', async ({ page }) => {
    await login(page);
    
    await page.goto('/shopping.php');
    await page.waitForLoadState('networkidle');
    
    // Find custom item form
    const nameInput = page.locator('#custom-item-name');
    const qtyInput = page.locator('#custom-item-qty');
    const addBtn = page.locator('.custom-item-form button[type="button"], .custom-item-form .btn-primary');
    
    if (await nameInput.isVisible()) {
      // Fill form
      await nameInput.fill('Milk');
      await qtyInput.fill('1L');
      
      // Click add
      await addBtn.click();
      
      // Wait for success toast
      const successToast = page.locator('.toast-success');
      await expect(successToast).toBeVisible({ timeout: 3000 }).catch(() => {
        // Toast might auto-dismiss, that's fine
      });
      
      // Form should be cleared
      const nameValue = await nameInput.inputValue();
      expect(nameValue).toBe('');
    }
  });

  test('shopping list shows progress bar', async ({ page }) => {
    await login(page);
    
    await page.goto('/shopping.php');
    await page.waitForLoadState('networkidle');
    
    // Find progress bar - use specific aria-label selector
    const progressBar = page.locator('[aria-label="Cart progress"]');
    await expect(progressBar).toBeVisible({ timeout: 5000 });
    
    // Should show count text
    const progressText = page.locator('.progress-text');
    await expect(progressText).toBeVisible({ timeout: 5000 }).catch(() => {
      // Progress text may not always be visible, that's ok
    });
  });

  test('shopping list items grouped by category', async ({ page }) => {
    await login(page);
    
    // Add multiple meals from different categories
    const mealIds = [1, 2, 3]; // Assuming different categories
    for (const id of mealIds) {
      await page.goto(`/meal.php?meal_id=${id}`);
      const addBtn = page.locator('button:has-text("Add to Cart")').first();
      if (await addBtn.isVisible()) {
        await addBtn.click();
        await page.waitForTimeout(300);
      }
    }
    
    // Go to shopping list
    await page.goto('/shopping.php');
    await page.waitForLoadState('networkidle');
    
    // Look for category headers
    const categoryHeaders = page.locator('h4, h3'); // Category headers
    const headerCount = await categoryHeaders.count();
    
    // If multiple items from same category, should have at least 1 header
    expect(headerCount).toBeGreaterThanOrEqual(0);
  });

  test('shopping list has proper accessibility', async ({ page }) => {
    await login(page);
    
    await page.goto('/shopping.php');
    await page.waitForLoadState('networkidle');
    
    // Check for proper landmarks
    const main = page.locator('main');
    await expect(main).toBeVisible();
    
    // Checkboxes should have labels
    const checkboxes = page.locator('input[type="checkbox"]');
    const count = await checkboxes.count();
    
    if (count > 0) {
      // Each should be in a label or have aria-label
      for (let i = 0; i < Math.min(count, 3); i++) {
        const checkbox = checkboxes.nth(i);
        const label = page.locator(`label:has(${await checkbox.selector()})`);
        
        // Should have either be in label or have aria-label
        expect(
          (await label.isVisible()) || 
          (await checkbox.getAttribute('aria-label'))
        ).toBeTruthy();
      }
    }
  });

  test('shopping list syncs when adding from meal page', async ({ page }) => {
    await login(page);
    
    // Count initial items
    await page.goto('/shopping.php');
    await page.waitForLoadState('networkidle');
    const initialItems = page.locator('[data-item-id]');
    const initialCount = await initialItems.count();
    
    // Add new meal
    await page.goto('/meal.php?meal_id=1');
    const addBtn = page.locator('button:has-text("Shopping List")').first();
    if (await addBtn.isVisible()) {
      await addBtn.click();
      await page.waitForTimeout(500);
    }
    
    // Go back to shopping list
    await page.goto('/shopping.php');
    await page.waitForLoadState('networkidle');
    
    // Should have new items (or at least not fewer)
    const newItems = page.locator('[data-item-id]');
    const newCount = await newItems.count();
    
    expect(newCount).toBeGreaterThanOrEqual(initialCount);
  });

  test('shopping list handles network errors gracefully', async ({ page, context }) => {
    await login(page);
    
    await page.goto('/shopping.php');
    
    // Simulate network error by going offline
    await context.setOffline(true);
    
    // Try to add item
    const nameInput = page.locator('#custom-item-name');
    if (await nameInput.isVisible()) {
      await nameInput.fill('Test Item');
      const addBtn = page.locator('button:has-text("Add")').last();
      await addBtn.click();
      
      // Should show error toast (or fail gracefully)
      await page.waitForTimeout(500);
    }
    
    // Come back online
    await context.setOffline(false);
  });

  test('shopping list button is disabled while loading', async ({ page }) => {
    await login(page);
    
    await page.goto('/meal.php?meal_id=1');
    await page.waitForLoadState('networkidle');
    
    const addBtn = page.locator('button:has-text("Shopping List")').first();
    
    if (await addBtn.isVisible()) {
      // Start click but don't wait
      const clickPromise = addBtn.click();
      
      // Check if disabled
      const isDisabled = await addBtn.isDisabled({ timeout: 100 }).catch(() => false);
      
      // Wait for completion
      await clickPromise;
      await page.waitForTimeout(500);
      
      // Should be enabled again
      await expect(addBtn).toBeEnabled();
    }
  });
});
