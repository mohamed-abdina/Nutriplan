const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ context }) => {
  await context.addInitScript(() => {
    try { delete window.navigator.serviceWorker; } catch (e) { /* ignore */ }
  });
});

test.describe('Authentication & User Flow', () => {
  
  test('logs in with test credentials and reaches dashboard', async ({ page }) => {
    // Log console messages
    page.on('console', msg => console.log(`BROWSER: ${msg.text()}`));
    page.on('response', res => {
      if (res.url().includes('login.php') && res.request().method() === 'POST') {
        console.log(`LOGIN POST: ${res.status()} - ${res.headers()['content-type']}`);
      }
    });

    await page.goto('/login.php');

    // Fill form
    await page.fill('input[name="email"]', 'test@user.io');
    await page.fill('input[name="password"]', 'Password123!');

    // Submit and wait for navigation
    const [response] = await Promise.all([
      page.waitForResponse(res => res.url().includes('login.php') && res.request().method() === 'POST'),
      page.click('button[type="submit"]')
    ]);

    console.log(`DEBUG: Status: ${response.status()}`);
    console.log(`DEBUG: Content-Type: ${response.headers()['content-type']}`);

    // Wait for redirect to dashboard (with longer timeout and allow soft navigation)
    try {
      await page.waitForURL('**/dashboard.php', { timeout: 15000 });
    } catch (e) {
      console.log(`DEBUG: Final URL: ${page.url()}`);
      throw e;
    }

    // Verify dashboard loaded
    await expect(page).toHaveTitle(/Dashboard/);
    
    // Check for dashboard section headers
    const sectionHeaders = page.locator('h3');
    expect(await sectionHeaders.count()).toBeGreaterThan(0);
  });

  test('user remains logged in across page navigation', async ({ page }) => {
    // Login
    await page.goto('/login.php');
    await page.fill('input[name="email"]', 'test@user.io');
    await page.fill('input[name="password"]', 'Password123!');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard.php');

    // Navigate to profile
    await page.goto('/profile.php');
    
    // Should still be logged in (no redirect to login)
    await expect(page).toHaveURL(/profile\.php/);
    
    // Navigate to shopping list
    await page.goto('/shopping.php');
    await expect(page).toHaveURL(/shopping\.php/);
  });

  test('session persists in localStorage/cookies', async ({ page }) => {
    await page.goto('/login.php');
    await page.fill('input[name="email"]', 'test@user.io');
    await page.fill('input[name="password"]', 'Password123!');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard.php');

    // Check for session cookie
    const cookies = await page.context().cookies();
    const sessionCookie = cookies.find(c => c.name === 'PHPSESSID' || c.name.includes('session'));
    expect(sessionCookie).toBeTruthy();
  });
});
