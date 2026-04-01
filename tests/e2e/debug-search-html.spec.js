const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ context }) => {
  await context.addInitScript(() => {
    try { delete window.navigator.serviceWorker; } catch (e) { /* ignore */ }
  });
});

test('debug: inspect actual rendered meal card HTML', async ({ page }) => {
  page.on('console', msg => {
    console.log(`[${msg.type().toUpperCase()}] ${msg.text()}`);
  });

  page.on('requestfailed', request => {
    console.error(`Request failed: ${request.url()}`);
  });

  // Login
  await page.goto('/login.php');
  await page.fill('input[name="email"]', 'test@user.io');
  await page.fill('input[name="password"]', 'Password123!');
  await Promise.all([
    page.waitForResponse(res => res.url().includes('login.php')),
    page.click('button[type="submit"]')
  ]);
  await page.waitForURL('**/dashboard.php', { timeout: 10000 });

  // Navigate to search
  const searchApiResponsePromise = page
    .waitForResponse(
      res => res.url().includes('/api/search_api.php') && res.request().method() === 'GET',
      { timeout: 15000 }
    )
    .catch(() => null);

  await page.goto('/search.php');
  
  // Wait for generateMealCardHtml to be available in window
  await page.waitForFunction(() => typeof window.generateMealCardHtml === 'function', { timeout: 10000 });
 
  // Wait for search lifecycle to settle: either real cards are rendered OR no-results is shown
  await page.waitForFunction(() => {
    const realCards = document.querySelectorAll('.meal-card:not(.skeleton)');
    const noResults = document.getElementById('no-results');
    const noResultsVisible = noResults && !noResults.classList.contains('hidden');
    return realCards.length > 0 || noResultsVisible;
  }, { timeout: 20000 });

  const searchApiResponse = await searchApiResponsePromise;
  if (searchApiResponse) {
    const status = searchApiResponse.status();
    let body = '';
    try {
      body = await searchApiResponse.text();
    } catch (e) {
      body = '[unavailable response body]';
    }
    console.log(`Search API status: ${status}`);
    console.log(`Search API body preview: ${body.slice(0, 500)}`);
  } else {
    console.warn('No /api/search_api.php response captured within timeout');
  }
  
  // Check if generateMealCardHtml exists
  const funcExists = await page.evaluate(() => typeof window.generateMealCardHtml);
  console.log('generateMealCardHtml function type:', funcExists);

  // Check actual meal count
  const mealCount = await page.locator('.meal-card:not(.skeleton)').count();
  console.log(`✓ Found ${mealCount} actual meal cards`);

  if (mealCount > 0) {
    const firstCardHtml = await page.locator('.meal-card:not(.skeleton)').first().innerHTML();
    console.log('=== FIRST MEAL CARD HTML ===');
    console.log(firstCardHtml);
    console.log('=== END HTML ===');
  } else {
    const noResultsVisible = await page.locator('#no-results').isVisible();
    expect(noResultsVisible).toBeTruthy();
    console.log('No meal cards rendered; #no-results is visible.');
  }
  
  // Check API response
  const searchUrl = await page.evaluate(() => {
    const params = new URLSearchParams({
      q: '',
      cat: '',
      offset: 0,
      sort: 'name',
      min_cal: 0,
      max_cal: 5000,
      min_protein: 0,
      max_protein: 200
    });
    return `/api/search_api.php?${params}`;
  });
  console.log('Expected search URL:', searchUrl);

  await page.waitForTimeout(2000);
});
