#!/bin/bash

# NutriPlan Test Runner (Docker-based)
# Runs all test suites: unit tests, API tests, and E2E tests

set -e

echo "🧪 NutriPlan Test Suite Runner"
echo "================================"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get project root
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_ROOT"

echo "📁 Project Root: $PROJECT_ROOT"

# Check if Docker is running
if ! docker ps > /dev/null 2>&1; then
  echo -e "${RED}❌ Docker is not running. Please start Docker first.${NC}"
  exit 1
fi

# Check if containers are running
if ! docker-compose ps | grep -q "Up"; then
  echo -e "${YELLOW}⚠️  Containers not running. Starting docker-compose...${NC}"
  docker-compose up -d
  sleep 5
fi

echo -e "${GREEN}✓ Docker containers are running${NC}"

# Run unit tests (PHPUnit)
echo ""
echo "========================================="
echo "🔬 Running Unit Tests (PHPUnit)"
echo "========================================="

if command -v ./vendor/bin/phpunit &> /dev/null; then
  echo "Running Favorites unit tests..."
  ./vendor/bin/phpunit tests/FavoritesTest.php --colors || {
    echo -e "${RED}❌ Favorites tests failed${NC}"
  }
  
  echo ""
  echo "Running API integration tests..."
  ./vendor/bin/phpunit tests/MealRatingsApiTest.php --colors || {
    echo -e "${RED}❌ API tests failed${NC}"
  }
  
  echo ""
  echo "Running Favorites/Ratings workflow tests..."
  ./vendor/bin/phpunit tests/FavoritesRatingsTest.php --colors || {
    echo -e "${RED}❌ Workflow tests failed${NC}"
  }
else
  echo -e "${YELLOW}⚠️  PHPUnit not found, skipping PHP unit tests${NC}"
fi

# Run E2E tests (Playwright)
echo ""
echo "========================================="
echo "🎭 Running E2E Tests (Playwright)"
echo "========================================="

if command -v npx &> /dev/null; then
  echo "Running authentication tests..."
  npx playwright test tests/e2e/auth-login.spec.js --reporter=list || {
    echo -e "${RED}⚠️  Auth tests had issues${NC}"
  }
  
  echo ""
  echo "Running favorites feature tests..."
  npx playwright test tests/e2e/favorites.spec.js --reporter=list || {
    echo -e "${RED}⚠️  Favorites E2E tests had issues${NC}"
  }
  
  echo ""
  echo "Running shopping list feature tests..."
  npx playwright test tests/e2e/shopping-list.spec.js --reporter=list || {
    echo -e "${RED}⚠️  Shopping list E2E tests had issues${NC}"
  }
  
  echo ""
  echo "Running login test..."
  npx playwright test tests/e2e/login.spec.js --reporter=list || {
    echo -e "${RED}⚠️  Login test had issues${NC}"
  }
else
  echo -e "${YELLOW}⚠️  Playwright not found. Install with: npm install${NC}"
fi

# Summary
echo ""
echo "========================================="
echo "✅ Test Suite Complete!"
echo "========================================="
echo ""
echo "📊 Test Summary:"
echo "  - Unit Tests: PHPUnit (FavoritesTest, MealRatingsApiTest, FavoritesRatingsTest)"
echo "  - E2E Tests: Playwright (auth, favorites, shopping-list)"
echo ""
echo "📝 To run specific test:"
echo "  ./vendor/bin/phpunit tests/FavoritesTest.php"
echo "  npx playwright test tests/e2e/favorites.spec.js"
echo ""
echo "🔍 To watch tests (Docker):"
echo "  docker exec nutriplan-app npx playwright test tests/e2e/ --watch"
echo ""
