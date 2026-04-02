const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ context }) => {
  await context.addInitScript(() => {
    try { delete window.navigator.serviceWorker; } catch (e) { /* ignore */ }
  });
});

test('validate: global functions available (escapeHtml, showToast, generateMealCardHtml)', async ({ page }) => {
  page.on('console', msg => {
    console.log(`[${msg.type().toUpperCase()}] ${msg.text()}`);
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
  await page.goto('/search.php');
  
  // Wait for main.js to load completely
  await page.waitForFunction(() => typeof window.generateMealCardHtml === 'function', { timeout: 10000 });
  
  // VALIDATION 1: escapeHtml available globally
  const escapeHtmlExists = await page.evaluate(() => typeof window.escapeHtml);
  console.log('✓ escapeHtml function type:', escapeHtmlExists);
  expect(escapeHtmlExists).toBe('function');

  // Test escapeHtml functionality
  const escaped = await page.evaluate(() => window.escapeHtml('<script>alert("xss")</script>'));
  console.log('✓ escapeHtml test result:', escaped);
  expect(escaped).toContain('&lt;script&gt;');
  expect(escaped).not.toContain('<script>');

  // VALIDATION 2: showToast available globally
  const showToastExists = await page.evaluate(() => typeof window.showToast);
  console.log('✓ showToast function type:', showToastExists);
  expect(showToastExists).toBe('function');

  // Test showToast functionality
  await page.evaluate(() => {
    window.showToast('Test message', 'success');
  });
  const toastVisible = await page.locator('.toast').isVisible({ timeout: 2000 }).catch(() => false);
  console.log('✓ Toast created:', toastVisible);

  // VALIDATION 3: generateMealCardHtml available globally
  const generateMealCardHtmlExists = await page.evaluate(() => typeof window.generateMealCardHtml);
  console.log('✓ generateMealCardHtml function type:', generateMealCardHtmlExists);
  expect(generateMealCardHtmlExists).toBe('function');

  // VALIDATION 4: Test generateMealCardHtml with sample meal data
  const testMealHtml = await page.evaluate(() => {
    const testMeal = {
      meal_id: 1,
      meal_name: 'Test Breakfast',
      meal_icon: 'breakfast',
      category_name: 'Breakfast',
      calories: 250,
      proteins_g: 15
    };
    return window.generateMealCardHtml(testMeal, { animation_delay: 0 });
  });
  console.log('✓ Generated meal card HTML length:', testMealHtml.length);
  expect(testMealHtml).toContain('Test Breakfast');
  expect(testMealHtml).toContain('250');
  expect(testMealHtml).toContain('15');

  // VALIDATION 5: Check for searchTimeout global variable (no double declaration)
  const searchTimeoutValue = await page.evaluate(() => typeof window.searchTimeout);
  console.log('✓ window.searchTimeout exists:', searchTimeoutValue);
  expect(['null', 'object', 'number']).toContain(searchTimeoutValue);
});

test('debug: inspect actual rendered meal card HTML', async ({ page }) => {
  const consoleErrors = [];
  const consoleWarnings = [];
  let captureStarted = false;

  page.on('console', msg => {
    const text = msg.text();
    
    // Only capture errors AFTER we navigate to search.php
    if (!captureStarted) return;
    
    console.log(`[${msg.type().toUpperCase()}] ${text}`);
    
    if (msg.type() === 'error') {
      // Filter out non-search-related errors (favorite meals, dashboard, profile, etc.)
      if (!text.includes('favorite meals') && !text.includes('meal_ratings')) {
        consoleErrors.push(text);
      }
    }
    if (msg.type() === 'warning') {
      consoleWarnings.push(text);
    }

    // Flag syntax errors and duplicate declarations
    if (text.includes('SyntaxError') || text.includes('already been declared')) {
      consoleErrors.push(`CRITICAL: ${text}`);
    }
  });

  page.on('requestfailed', request => {
    if (captureStarted) {
      console.error(`Request failed: ${request.url()}`);
    }
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
  
  // NOW start capturing errors (from search page only)
  captureStarted = true;
  
  // Wait for generateMealCardHtml to be available in window
  await page.waitForFunction(() => typeof window.generateMealCardHtml === 'function', { timeout: 10000 });
 
  // Wait for search lifecycle to settle: either real cards are rendered OR no-results is shown
  await page.waitForFunction(() => {
    const realCards = document.querySelectorAll('.meal-card:not(.skeleton)');
    const noResults = document.getElementById('no-results');
    const noResultsVisible = noResults && !noResults.classList.contains('hidden');
    return realCards.length > 0 || noResultsVisible;
  }, { timeout: 20000 });

  // CRITICAL CHECK: Filter only search-related console errors
  const criticalErrors = consoleErrors.filter(e => {
    const isCritical = 
      e.includes('SyntaxError') ||
      e.includes('already been declared') ||
      (e.includes('escapeHtml') && e.includes('not defined')) ||
      (e.includes('showToast') && e.includes('not defined')) ||
      (e.includes('generateMealCardHtml') && e.includes('not defined')) ||
      e.includes('ReferenceError: searchTimeout');
    return isCritical;
  });

  console.log(`\n=== CRITICAL ERRORS (from search): ${criticalErrors.length} ===`);
  criticalErrors.forEach(e => console.log(`  ✗ ${e}`));
  expect(criticalErrors.length).toBe(0);

  const searchApiResponse = await searchApiResponsePromise;
  if (searchApiResponse) {
    const status = searchApiResponse.status();
    let body = '';
    try {
      body = await searchApiResponse.text();
    } catch (e) {
      body = '[unavailable response body]';
    }
    console.log(`\n✓ Search API status: ${status}`);
    expect(status).toBe(200);
    console.log(`✓ Search API body preview: ${body.slice(0, 200)}`);
  } else {
    console.warn('No /api/search_api.php response captured within timeout');
  }
  
  // CHECK 2: Functions exist
  const funcExists = await page.evaluate(() => typeof window.generateMealCardHtml);
  console.log(`✓ generateMealCardHtml function type: ${funcExists}`);
  expect(funcExists).toBe('function');

  // CHECK 3: Meal cards rendered
  const mealCount = await page.locator('.meal-card:not(.skeleton)').count();
  console.log(`✓ Found ${mealCount} actual meal cards`);

  if (mealCount > 0) {
    expect(mealCount).toBeGreaterThan(0);
    
    const firstCardElement = page.locator('.meal-card:not(.skeleton)').first();
    const firstCardHtml = await firstCardElement.evaluate(el => el.outerHTML);
    console.log('=== FIRST MEAL CARD HTML ===');
    console.log(firstCardHtml.slice(0, 400));
    console.log('=== END HTML ===');

    // Validate first card structure
    expect(firstCardHtml).toContain('class="meal-card');
    expect(firstCardHtml).toContain('card-body');
    expect(firstCardHtml).toContain('card-icon');
    expect(firstCardHtml).toContain('card-title');
    expect(firstCardHtml).toContain('card-category');
    expect(firstCardHtml).toContain('card-badges');

    // Check that content is properly escaped (no raw HTML injection)
    // Note: onclick= is a legitimate inline event handler and is safe when meal data is escaped
    const hasScriptInjection = firstCardHtml.includes('<script') || firstCardHtml.includes('javascript:');
    console.log(`✓ XSS protection verified (no script injection): ${!hasScriptInjection}`);
    expect(hasScriptInjection).toBe(false);

    // Validate card buttons exist
    const buttonsCount = await page.locator('.meal-card:not(.skeleton) .btn').count();
    console.log(`✓ Found ${buttonsCount} action buttons in first card`);
    expect(buttonsCount).toBeGreaterThan(0);

  } else {
    const noResultsVisible = await page.locator('#no-results').isVisible();
    expect(noResultsVisible).toBeTruthy();
    console.log('✓ No meal cards rendered; #no-results is visible.');
  }
  
  // CHECK 4: No duplicate searchTimeout declaration errors
  const hasSearchTimeoutError = criticalErrors.some(e => e.includes('searchTimeout') && e.includes('already been declared'));
  console.log(`✓ searchTimeout double declaration error: ${hasSearchTimeoutError ? 'FAILED' : 'PASSED'}`);
  expect(hasSearchTimeoutError).toBe(false);

  // CHECK 5: No escapeHtml undefined errors
  const hasEscapeHtmlError = criticalErrors.some(e => e.includes('escapeHtml') && e.includes('not defined'));
  console.log(`✓ escapeHtml undefined error: ${hasEscapeHtmlError ? 'FAILED' : 'PASSED'}`);
  expect(hasEscapeHtmlError).toBe(false);

  // CHECK 6: No showToast undefined errors
  const hasShowToastError = criticalErrors.some(e => e.includes('showToast') && e.includes('not defined'));
  console.log(`✓ showToast undefined error: ${hasShowToastError ? 'FAILED' : 'PASSED'}`);
  expect(hasShowToastError).toBe(false);

  await page.waitForTimeout(1000);
});
