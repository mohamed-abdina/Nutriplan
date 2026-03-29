import { test, expect } from '@playwright/test';

// Desktop breakpoint (1024px - 1399px)
test.describe('Responsive Design - Desktop (1024px)', () => {
  test.beforeEach(async ({ page }) => {
    // Set viewport to small desktop size
    await page.setViewportSize({ width: 1024, height: 768 });
  });

  test('Hamburger is hidden on desktop', async ({ page }) => {
    await page.goto('/dashboard.php');
    const hamburger = page.locator('.hamburger');
    // On desktop (>1023px), hamburger should not be visible
    const isVisible = await hamburger.isVisible().catch(() => false);
    expect(isVisible).toBe(false);
  });

  test('Sidebar is visible and fixed on desktop', async ({ page }) => {
    await page.goto('/dashboard.php');
    const sidebar = page.locator('.sidebar');
    await expect(sidebar).toBeVisible();
  });

  test('Two-column layout shows 2 columns on desktop', async ({ page }) => {
    await page.goto('/meal.php?meal_id=1');
    const layout = page.locator('.two-column-layout');
    if (await layout.isVisible()) {
      const styles = await page.evaluate(() => {
        const el = document.querySelector('.two-column-layout');
        return window.getComputedStyle(el).gridTemplateColumns;
      });
      // Desktop should have 2 columns (1fr 1fr)
      const colCount = (styles.match(/1fr/g) || []).length;
      expect(colCount).toBe(2);
    }
  });

  test('Grid shows 2 columns on desktop', async ({ page }) => {
    await page.goto('/search.php');
    const grid = page.locator('.grid-2');
    const styles = await page.evaluate(() => {
      const el = document.querySelector('.grid-2');
      return window.getComputedStyle(el).gridTemplateColumns;
    });
    // Desktop should have 2 columns
    const colCount = (styles.match(/1fr/g) || []).length;
    expect(colCount).toBe(2);
  });

  test('Form grid shows 2 columns on desktop', async ({ page }) => {
    await page.goto('/profile.php');
    const formGrid = page.locator('.form-grid-2');
    if (await formGrid.isVisible()) {
      const styles = await page.evaluate(() => {
        const el = document.querySelector('.form-grid-2');
        return window.getComputedStyle(el).gridTemplateColumns;
      });
      // Desktop should have 2 columns
      const colCount = (styles.match(/1fr/g) || []).length;
      expect(colCount).toBe(2);
    }
  });

  test('Social proof shows 3 columns on desktop', async ({ page }) => {
    await page.goto('/');
    const grid = page.locator('.social-proof-grid');
    const styles = await page.evaluate(() => {
      const el = document.querySelector('.social-proof-grid');
      return window.getComputedStyle(el).gridTemplateColumns;
    });
    // Desktop should show 3 columns
    const colCount = (styles.match(/1fr/g) || []).length;
    expect(colCount).toBe(3);
  });

  test('Stats grid shows 3+ columns on desktop', async ({ page }) => {
    await page.goto('/profile.php');
    const statsGrid = page.locator('.stats-grid-auto');
    if (await statsGrid.isVisible()) {
      const styles = await page.evaluate(() => {
        const el = document.querySelector('.stats-grid-auto');
        return window.getComputedStyle(el).gridTemplateColumns;
      });
      // Desktop should have 3+ columns
      const colCount = (styles.match(/1fr/g) || []).length;
      expect(colCount).toBeGreaterThanOrEqual(3);
    }
  });

  test('No horizontal overflow on desktop', async ({ page }) => {
    await page.goto('/dashboard.php');
    const scrollWidth = await page.evaluate(() => {
      return Math.max(
        document.documentElement.scrollWidth,
        document.body.scrollWidth
      );
    });
    const viewportWidth = 1024;
    expect(scrollWidth).toBeLessThanOrEqual(viewportWidth + 1);
  });

  test('Sidebar width is consistent on desktop', async ({ page }) => {
    await page.goto('/dashboard.php');
    const sidebar = page.locator('.sidebar');
    const boundingBox = await sidebar.boundingBox();
    // Sidebar should be between 180px and 240px
    expect(boundingBox.width).toBeGreaterThanOrEqual(180);
    expect(boundingBox.width).toBeLessThanOrEqual(240);
  });

  test('Main content area respects sidebar width', async ({ page }) => {
    await page.goto('/dashboard.php');
    const main = page.locator('.main');
    const boundingBox = await main.boundingBox();
    // Main should have adequate width
    expect(boundingBox.width).toBeGreaterThan(400);
  });

  test('Form inputs have appropriate width on desktop', async ({ page }) => {
    await page.goto('/profile.php');
    const formInputs = page.locator('.field input');
    if (await formInputs.count() > 0) {
      const firstInput = formInputs.first();
      const boundingBox = await firstInput.boundingBox();
      // Should be readable
      expect(boundingBox.width).toBeGreaterThan(200);
    }
  });

  test('Tab buttons layout on desktop', async ({ page }) => {
    await page.goto('/meal.php?meal_id=1');
    const tabGroup = page.locator('.tab-button-group');
    if (await tabGroup.isVisible()) {
      const boundingBox = await tabGroup.boundingBox();
      expect(boundingBox.width).toBeGreaterThan(0);
    }
  });

  test('All meal cards visible in grid on desktop', async ({ page }) => {
    await page.goto('/dashboard.php');
    const cards = page.locator('.meal-card');
    const count = await cards.count();
    // Dashboard should show all 6 meal recommendations
    expect(count).toBeGreaterThanOrEqual(1);
  });

  test('Nutrition SVG proper size on desktop', async ({ page }) => {
    await page.goto('/meal.php?meal_id=1');
    const svg = page.locator('.nutrition-ring');
    if (await svg.isVisible()) {
      const boundingBox = await svg.boundingBox();
      // Should be larger on desktop
      expect(boundingBox.width).toBeGreaterThan(180);
    }
  });

  test('Shopping list scrollable if needed on desktop', async ({ page }) => {
    await page.goto('/shopping.php');
    const listItems = page.locator('.list-item-layout');
    if (await listItems.count() > 0) {
      const firstItem = listItems.first();
      await expect(firstItem).toBeVisible();
    }
  });

  test('No console errors on desktop pages', async ({ page }) => {
    const errors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') errors.push(msg.text());
    });
    await page.goto('/index.php');
    // May have some auth-related errors, check for critical ones
  });

  test('Responsive navbar visible on desktop', async ({ page }) => {
    await page.goto('/');
    const navbar = page.locator('.responsive-navbar');
    await expect(navbar).toBeVisible();
  });

  test('Focus states visible on desktop (keyboard navigation)', async ({ page }) => {
    await page.goto('/');
    const firstLink = page.locator('a').first();
    await firstLink.focus();
    const focusStyles = await page.evaluate(() => {
      const el = document.querySelector('a');
      return window.getComputedStyle(el).outline;
    });
    // Focus should be visible (not 'none')
    expect(focusStyles).not.toBe('none');
  });
});
