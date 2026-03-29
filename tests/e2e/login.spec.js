const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ context }) => {
  await context.addInitScript(() => {
    // Prevent the service worker from being available during tests
    // which can interfere with navigation and caching behavior.
    try { delete window.navigator.serviceWorker; } catch (e) { /* ignore */ }
  });
});

test.describe('Login flow', () => {
  test('logs in with test credentials and reaches dashboard', async ({ page }) => {
    await page.goto('/login.php');

    // Fill form
    await page.fill('input[name="email"]', 'test@user.io');
    await page.fill('input[name="password"]', 'Password123!');

    // Debug: Listen for the response to see why it's not redirecting
    const [response] = await Promise.all([
      page.waitForResponse(res => res.url().includes('login.php') && res.request().method() === 'POST'),
      page.click('button[type="submit"]')
    ]);

    console.log(`DEBUG: Status: ${response.status()} | Body: ${await response.text()}`);

    // If we're still here, it means the redirect didn't happen yet.
    // Now we wait for the dashboard.
    await page.waitForURL('**/dashboard.php', { timeout: 10000 });

    // Assertions
    await expect(page).toHaveTitle(/Dashboard/);
    await expect(page.locator('h3').first()).toContainText('E2E');
  });
});