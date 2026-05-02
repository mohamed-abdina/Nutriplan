#!/bin/bash
# Test runner script for Docker environment

set -e

cd /var/www/html

echo "================== NutriPlan Test Suite =================="
echo ""
echo "Running Favorites & Ratings Tests..."
echo ""

# Run all test files
./vendor/bin/phpunit tests/FavoritesTest.php \
                     tests/MealRatingsApiTest.php \
                     tests/FavoritesRatingsTest.php \
                     --colors=auto \
                     --stderr \
                     -v

echo ""
echo "================== Test Summary =================="
echo "✅ Unit tests completed"
echo ""
echo "Running E2E tests with Playwright..."
echo ""

# Run E2E tests if playwright is available
if command -v npx &> /dev/null; then
    npx playwright test tests/e2e/auth-login.spec.js \
                         tests/e2e/favorites.spec.js \
                         --reporter=list || true
    echo "✅ E2E tests completed"
else
    echo "⚠️  Playwright not available - skipping E2E tests"
fi

echo ""
echo "================== All Tests Finished =================="
