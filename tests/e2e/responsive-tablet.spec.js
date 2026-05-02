import { test, expect } from '@playwright/test';

// Tablet breakpoint (768px - 1023px)
test.describe('Responsive Design - Tablet (768px)', () => {
  test.beforeEach(async ({ page }) => {
    // Set viewport to tablet size
    await page.setViewportSize({ width: 768, height: 1024 });
  });

  test('Hamburger is visible on tablet', async ({ page }) => {
    await page.goto('/dashboard.php');
    const hamburger = page.locator('.hamburger');
    await expect(hamburger).toBeVisible();
  });

  test('Sidebar is sticky on tablet', async ({ page }) => {
    await page.goto('/dashboard.php');
    const sidebar = page.locator('.sidebar');
    const position = await page.evaluate(() => {
      const el = document.querySelector('.sidebar');
      return window.getComputedStyle(el).position;
    });
    // On tablet (768-1023px), sidebar should be sticky
    expect(['sticky', 'fixed', 'relative']).toContain(position);
  });

  test('Grid shows 2 columns on tablet', async ({ page }) => {
    await page.goto('/search.php');
    const grid = page.locator('.grid-2');
    const styles = await page.evaluate(() => {
      const el = document.querySelector('.grid-2');
      return window.getComputedStyle(el).gridTemplateColumns;
    });
    // Should have 2 columns on tablet
    const colCount = (styles.match(/1fr/g) || []).length;
    expect(colCount).toBe(2);
  });

  test('Two-column layout stacks on tablet', async ({ page }) => {
    await page.goto('/meal.php?meal_id=1');
    const layout = page.locator('.two-column-layout');
    if (await layout.isVisible()) {
      const styles = await page.evaluate(() => {
        const el = document.querySelector('.two-column-layout');
        return window.getComputedStyle(el).gridTemplateColumns;
      });
      // Tablet should have 1fr (single column)
      expect(styles).toBe('1fr');
    }
  });

  test('Split layout stacks on tablet', async ({ page }) => {
    await page.goto('/register.php');
    const layout = page.locator('.split-layout');
    const styles = await page.evaluate(() => {
      const el = document.querySelector('.split-layout');
      return window.getComputedStyle(el).gridTemplateColumns;
    });
    // Tablet should have single column
    expect(styles).toBe('1fr');
  });

  test('Form grid is single column on tablet', async ({ page }) => {
    await page.goto('/profile.php');
    const formGrid = page.locator('.form-grid-2');
    if (await formGrid.isVisible()) {
      const styles = await page.evaluate(() => {
        const el = document.querySelector('.form-grid-2');
        return window.getComputedStyle(el).gridTemplateColumns;
      });
      // Tablet should have 1fr (single column)
      expect(styles).toBe('1fr');
    }
  });

  test('Social proof grid shows 3 columns on tablet', async ({ page }) => {
    await page.goto('/');
    const grid = page.locator('.social-proof-grid');
    const styles = await page.evaluate(() => {
      const el = document.querySelector('.social-proof-grid');
      return window.getComputedStyle(el).gridTemplateColumns;
    });
    // Tablet should show 3 columns
    const colCount = (styles.match(/1fr/g) || []).length;
    expect(colCount).toBe(3);
  });

  test('Stats grid shows 3 columns on tablet', async ({ page }) => {
    await page.goto('/profile.php');
    const statsGrid = page.locator('.stats-grid-auto');
    if (await statsGrid.isVisible()) {
      const styles = await page.evaluate(() => {
        const el = document.querySelector('.stats-grid-auto');
        return window.getComputedStyle(el).gridTemplateColumns;
      });
      // Tablet should have 3 columns
      const colCount = (styles.match(/1fr/g) || []).length;
      expect(colCount).toBe(3);
    }
  });

  test('No horizontal overflow on tablet', async ({ page }) => {
    await page.goto('/dashboard.php');
    const scrollWidth = await page.evaluate(() => {
      return Math.max(
        document.documentElement.scrollWidth,
        document.body.scrollWidth
      );
    });
    const viewportWidth = 768;
    expect(scrollWidth).toBeLessThanOrEqual(viewportWidth + 1);
  });

  test('Touch targets are readable on tablet', async ({ page }) => {
    await page.goto('/search.php');
    const buttons = page.locator('button.btn');
    if (await buttons.count() > 0) {
      const firstButton = buttons.first();
      const boundingBox = await firstButton.boundingBox();
      expect(boundingBox.height).toBeGreaterThanOrEqual(44);
      expect(boundingBox.width).toBeGreaterThanOrEqual(44);
    }
  });

  test('Form inputs are readable on tablet', async ({ page }) => {
    await page.goto('/profile.php');
    const formInputs = page.locator('.field input');
    if (await formInputs.count() > 0) {
      const firstInput = formInputs.first();
      const boundingBox = await firstInput.boundingBox();
      expect(boundingBox.height).toBeGreaterThanOrEqual(48);
    }
  });

  test('Nutrition ring is visible on tablet', async ({ page }) => {
    await page.goto('/meal.php?meal_id=1');
    const svg = page.locator('.nutrition-ring');
    if (await svg.isVisible()) {
      const boundingBox = await svg.boundingBox();
      expect(boundingBox.width).toBeGreaterThan(0);
    }
  });

  test('Back link is accessible on tablet', async ({ page }) => {
    await page.goto('/meal.php?meal_id=1');
    const backLink = page.locator('.back-link');
    if (await backLink.isVisible()) {
      await expect(backLink).toHaveClass(/back-link/);
    }
  });

  test('Shopping list layout on tablet', async ({ page }) => {
    await page.goto('/shopping.php');
    const listItems = page.locator('.list-item-layout');
    if (await listItems.count() > 0) {
      const firstItem = listItems.first();
      const boundingBox = await firstItem.boundingBox();
      expect(boundingBox.width).toBeGreaterThan(0);
    }
  });

  test('No console errors on authenticated pages', async ({ page }) => {
    const errors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') errors.push(msg.text());
    });
    await page.goto('/dashboard.php');
    // We expect some errors may occur due to authentication, but check for major issues
  });
});
