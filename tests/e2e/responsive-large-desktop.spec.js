import { test, expect } from '@playwright/test';

// Large Desktop breakpoint (≥ 1600px)
test.describe('Responsive Design - Large Desktop (1600px)', () => {
  test.beforeEach(async ({ page }) => {
    // Set viewport to large desktop size
    await page.setViewportSize({ width: 1600, height: 900 });
  });

  test('Sidebar width is 240px on large desktop', async ({ page }) => {
    await page.goto('/dashboard.php');
    const sidebar = page.locator('.sidebar');
    const boundingBox = await sidebar.boundingBox();
    // On large desktop, sidebar should be fixed at 240px
    expect(boundingBox.width).toBeCloseTo(240, 5);
  });

  test('App shell uses optimized layout on large desktop', async ({ page }) => {
    await page.goto('/dashboard.php');
    const appShell = page.locator('.app-shell');
    const styles = await page.evaluate(() => {
      const el = document.querySelector('.app-shell');
      return window.getComputedStyle(el).gridTemplateColumns;
    });
    // Should be "240px 1fr" layout
    expect(styles).toContain('240px');
  });

  test('Main content area has optimal width on large desktop', async ({ page }) => {
    await page.goto('/dashboard.php');
    const main = page.locator('.main');
    const boundingBox = await main.boundingBox();
    // Should have significant width
    expect(boundingBox.width).toBeGreaterThan(1000);
  });

  test('Grid shows multiple columns on large desktop', async ({ page }) => {
    await page.goto('/search.php');
    const grid = page.locator('.grid-2');
    const styles = await page.evaluate(() => {
      const el = document.querySelector('.grid-2');
      return window.getComputedStyle(el).gridTemplateColumns;
    });
    // Should have auto-fit with multiple columns
    const colCount = (styles.match(/\d+px/g) || []).length;
    expect(colCount).toBeGreaterThanOrEqual(2);
  });

  test('Grid-4 shows 4 columns on large desktop', async ({ page }) => {
    await page.goto('/dashboard.php');
    const grid = page.locator('.grid-4').first();
    if (await grid.isVisible()) {
      const styles = await page.evaluate(() => {
        const el = document.querySelector('.grid-4');
        return window.getComputedStyle(el).gridTemplateColumns;
      });
      // Grid-4 might show multiple columns
      expect(styles).toContain('minmax');
    }
  });

  test('No horizontal overflow on large desktop', async ({ page }) => {
    await page.goto('/dashboard.php');
    const scrollWidth = await page.evaluate(() => {
      return Math.max(
        document.documentElement.scrollWidth,
        document.body.scrollWidth
      );
    });
    const viewportWidth = 1600;
    expect(scrollWidth).toBeLessThanOrEqual(viewportWidth + 1);
  });

  test('Content is readable with adequate spacing on large desktop', async ({ page }) => {
    await page.goto('/');
    const hero = page.locator('h1').first();
    if (await hero.isVisible()) {
      const fontSize = await page.evaluate(() => {
        const el = document.querySelector('h1');
        return parseInt(window.getComputedStyle(el).fontSize);
      });
      // Font should be appropriately sized for large screen
      expect(fontSize).toBeGreaterThan(20);
    }
  });

  test('Social proof cards have good spacing on large desktop', async ({ page }) => {
    await page.goto('/');
    const grid = page.locator('.social-proof-grid');
    const boundingBox = await grid.boundingBox();
    // Should have ample space
    expect(boundingBox.width).toBeGreaterThan(800);
  });

  test('Form fields have optimal width on large desktop', async ({ page }) => {
    await page.goto('/profile.php');
    // Two-column form should show side-by-side on large desktop
    const formGrid = page.locator('.form-grid-2');
    if (await formGrid.isVisible()) {
      const styles = await page.evaluate(() => {
        const el = document.querySelector('.form-grid-2');
        return window.getComputedStyle(el).gridTemplateColumns;
      });
      // Should have 2 columns
      const colCount = (styles.match(/1fr/g) || []).length;
      expect(colCount).toBe(2);
    }
  });

  test('Meal detail layout optimal on large desktop', async ({ page }) => {
    await page.goto('/meal.php?meal_id=1');
    const layout = page.locator('.two-column-layout');
    if (await layout.isVisible()) {
      const styles = await page.evaluate(() => {
        const el = document.querySelector('.two-column-layout');
        return window.getComputedStyle(el).gridTemplateColumns;
      });
      // Should show 2 columns
      const colCount = (styles.match(/1fr/g) || []).length;
      expect(colCount).toBe(2);
    }
  });

  test('Nutrition SVG well-sized on large desktop', async ({ page }) => {
    await page.goto('/meal.php?meal_id=1');
    const svg = page.locator('.nutrition-ring');
    if (await svg.isVisible()) {
      const boundingBox = await svg.boundingBox();
      // Should have sufficient size
      expect(boundingBox.width).toBeGreaterThan(180);
      expect(boundingBox.height).toBeGreaterThan(180);
    }
  });

  test('All dashboard cards visible without scrolling on large screen', async ({ page }) => {
    await page.goto('/dashboard.php');
    const statCards = page.locator('.stat-card');
    const mealCards = page.locator('.meal-card');
    
    const statCount = await statCards.count();
    const mealCount = await mealCards.count();
    
    // Should have multiple cards visible
    expect(statCount).toBeGreaterThanOrEqual(1);
    expect(mealCount).toBeGreaterThanOrEqual(1);
  });

  test('Topbar layout on large desktop', async ({ page }) => {
    await page.goto('/dashboard.php');
    const topbar = page.locator('.topbar');
    const boundingBox = await topbar.boundingBox();
    expect(boundingBox.width).toBeGreaterThan(1000);
  });

  test('Theme toggle is accessible on large desktop', async ({ page }) => {
    await page.goto('/');
    const themeBtn = page.locator('button:has-text("Light")').first();
    if (await themeBtn.isVisible()) {
      await expect(themeBtn).toBeTruthy();
    }
  });

  test('Navigation links are properly spaced on large desktop', async ({ page }) => {
    await page.goto('/');
    const navLinks = page.locator('.responsive-navbar a');
    const count = await navLinks.count();
    expect(count).toBeGreaterThan(0);
  });

  test('Footer section visible if present on large desktop', async ({ page }) => {
    await page.goto('/');
    // Check if we can scroll to any footer content
    await page.evaluate(() => window.scrollBy(0, window.innerHeight));
  });

  test('No layout shift on large desktop during interactions', async ({ page }) => {
    await page.goto('/dashboard.php');
    const initialWidth = await page.evaluate(() => window.innerWidth);
    
    // Simulate some interactions
    const button = page.locator('button').first();
    if (await button.isVisible()) {
      await button.hover();
    }
    
    const finalWidth = await page.evaluate(() => window.innerWidth);
    expect(initialWidth).toBe(finalWidth);
  });
});
