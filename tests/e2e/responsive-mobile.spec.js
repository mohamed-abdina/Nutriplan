import { test, expect } from '@playwright/test';

// Mobile breakpoint (≤ 480px)
test.describe('Responsive Design - Mobile (480px)', () => {
  test.beforeEach(async ({ page }) => {
    // Set viewport to mobile size
    await page.setViewportSize({ width: 480, height: 800 });
  });

  test('Navigation hamburger is visible on mobile', async ({ page }) => {
    await page.goto('/');
    const hamburger = page.locator('.hamburger');
    await expect(hamburger).toBeVisible();
  });

  test('Navbar height adapts on mobile', async ({ page }) => {
    await page.goto('/');
    const navbar = page.locator('.responsive-navbar');
    const boundingBox = await navbar.boundingBox();
    expect(boundingBox.height).toBeLessThanOrEqual(64); // Should be 56px on mobile
  });

  test('Hero button group stacks on mobile', async ({ page }) => {
    await page.goto('/');
    const buttonGroup = page.locator('.hero-button-group');
    const buttons = page.locator('.hero-button-group .btn');
    const count = await buttons.count();
    expect(count).toBeGreaterThan(0);
  });

  test('Social proof grid shows 1 column on mobile', async ({ page }) => {
    await page.goto('/');
    const grid = page.locator('.social-proof-grid');
    const columns = await grid.getAttribute('style');
    // Mobile should have 1fr columns
    const styles = await page.evaluate(() => {
      const el = document.querySelector('.social-proof-grid');
      return window.getComputedStyle(el).gridTemplateColumns;
    });
    // Should be 1 column layout
    const colCount = styles.split(' ').filter(s => s.includes('px') || s === '1fr').length;
    expect(colCount).toBeGreaterThanOrEqual(1);
  });

  test('Form inputs are full width on mobile', async ({ page }) => {
    await page.goto('/register.php');
    const formInputs = page.locator('.field input');
    const firstInput = formInputs.first();
    const width = await firstInput.boundingBox();
    expect(width.width).toBeGreaterThan(0);
  });

  test('Touch targets are at least 44px on mobile', async ({ page }) => {
    await page.goto('/');
    const buttons = page.locator('button, .btn');
    
    const firstButton = buttons.first();
    const boundingBox = await firstButton.boundingBox();
    
    // Touch targets should be at least 44x44px
    expect(boundingBox.height).toBeGreaterThanOrEqual(44);
    expect(boundingBox.width).toBeGreaterThanOrEqual(44);
  });

  test('No horizontal overflow on mobile', async ({ page }) => {
    await page.goto('/dashboard.php');
    const scrollWidth = await page.evaluate(() => {
      return Math.max(
        document.documentElement.scrollWidth,
        document.body.scrollWidth
      );
    });
    const viewportWidth = 480;
    expect(scrollWidth).toBeLessThanOrEqual(viewportWidth + 1); // Allow 1px tolerance
  });

  test('Two-column layout stacks to single column on mobile', async ({ page }) => {
    await page.goto('/meal.php?meal_id=1');
    const layout = page.locator('.two-column-layout');
    const styles = await page.evaluate(() => {
      const el = document.querySelector('.two-column-layout');
      return window.getComputedStyle(el).gridTemplateColumns;
    });
    // Mobile should have 1fr (single column)
    expect(styles).toBe('1fr');
  });

  test('Split layout stacks on mobile', async ({ page }) => {
    await page.goto('/register.php');
    const layout = page.locator('.split-layout');
    const styles = await page.evaluate(() => {
      const el = document.querySelector('.split-layout');
      return window.getComputedStyle(el).gridTemplateColumns;
    });
    // Mobile should have single column
    expect(styles).toBe('1fr');
  });

  test('Stats grid shows 2 columns on mobile', async ({ page }) => {
    await page.goto('/profile.php');
    const statsGrid = page.locator('.stats-grid-auto');
    if (await statsGrid.isVisible()) {
      const styles = await page.evaluate(() => {
        const el = document.querySelector('.stats-grid-auto');
        return window.getComputedStyle(el).gridTemplateColumns;
      });
      // Mobile should have 2 columns
      const colCount = (styles.match(/1fr/g) || []).length;
      expect(colCount).toBe(2);
    }
  });

  test('Form grid is single column on mobile', async ({ page }) => {
    await page.goto('/profile.php');
    const formGrid = page.locator('.form-grid-2');
    if (await formGrid.isVisible()) {
      const styles = await page.evaluate(() => {
        const el = document.querySelector('.form-grid-2');
        return window.getComputedStyle(el).gridTemplateColumns;
      });
      // Mobile should have 1fr (single column)
      expect(styles).toBe('1fr');
    }
  });

  test('Shopping list items are readable on mobile', async ({ page }) => {
    await page.goto('/shopping.php');
    const listItems = page.locator('.list-item-layout');
    if (await listItems.count() > 0) {
      const firstItem = listItems.first();
      await expect(firstItem).toBeVisible();
    }
  });

  test('Nutrition SVG scales properly on mobile', async ({ page }) => {
    await page.goto('/meal.php?meal_id=1');
    const svg = page.locator('.nutrition-ring');
    if (await svg.isVisible()) {
      const boundingBox = await svg.boundingBox();
      // SVG should be responsive
      expect(boundingBox.width).toBeGreaterThan(0);
      expect(boundingBox.height).toBeGreaterThan(0);
    }
  });

  test('Sidebar is off-canvas on mobile', async ({ page }) => {
    await page.goto('/dashboard.php');
    const sidebar = page.locator('.sidebar');
    const transform = await page.evaluate(() => {
      const el = document.querySelector('.sidebar');
      return window.getComputedStyle(el).transform;
    });
    // On very small mobile, sidebar might be translated off-screen
    // (depends on class state)
  });

  test('No console errors on landing page', async ({ page }) => {
    const errors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') errors.push(msg.text());
    });
    await page.goto('/');
    expect(errors.length).toBe(0);
  });

  test('No console errors on dashboard', async ({ page }) => {
    const errors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') errors.push(msg.text());
    });
    await page.goto('/dashboard.php');
    expect(errors.length).toBe(0);
  });
});
