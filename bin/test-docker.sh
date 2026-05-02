#!/bin/bash

# Docker-based test runner for NutriPlan
# Runs tests inside container for consistent environment

set -e

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

echo "🐳 Docker Test Runner - NutriPlan"
echo "===================================="

# Check if docker-compose is available
if ! command -v docker-compose &> /dev/null && ! command -v docker compose &> /dev/null; then
  echo "❌ docker-compose not found. Please install Docker Compose."
  exit 1
fi

DOCKER_COMPOSE_CMD="docker-compose"
if ! $DOCKER_COMPOSE_CMD ps > /dev/null 2>&1; then
  DOCKER_COMPOSE_CMD="docker compose"
fi

echo "Starting containers..."
$DOCKER_COMPOSE_CMD up -d

echo ""
echo "🧪 Running tests inside container..."
echo ""

# Wait for container to be ready
sleep 3

# Get container name
CONTAINER_NAME=$($DOCKER_COMPOSE_CMD ps -q app | head -1)

if [ -z "$CONTAINER_NAME" ]; then
  echo "❌ Could not find running container"
  exit 1
fi

echo "Container ID: $CONTAINER_NAME"
echo ""

# Run tests in container
echo "========== PHPUnit Tests =========="
docker exec $CONTAINER_NAME php vendor/bin/phpunit tests/FavoritesTest.php --colors || true
docker exec $CONTAINER_NAME php vendor/bin/phpunit tests/MealRatingsApiTest.php --colors || true
docker exec $CONTAINER_NAME php vendor/bin/phpunit tests/FavoritesRatingsTest.php --colors || true

echo ""
echo "========== Playwright E2E Tests =========="
# E2E tests require the app to be running, so they run from host
if command -v npx &> /dev/null; then
  # Set base URL for E2E tests
  export BASE_URL="http://localhost:8000"
  
  echo "Running auth login tests..."
  npx playwright test tests/e2e/auth-login.spec.js --reporter=list true || true
  
  echo ""
  echo "Running favorites tests..."
  npx playwright test tests/e2e/favorites.spec.js --reporter=list true || true
  
  echo ""
  echo "Running shopping list tests..."
  npx playwright test tests/e2e/shopping-list.spec.js --reporter=list true || true
else
  echo "⚠️  Playwright not installed. Skipping E2E tests."
  echo "Install with: npm install"
fi

echo ""
echo "========================================="
echo "✅ Test execution complete!"
echo "========================================="
echo ""
echo "📊 Check test results above"
echo ""
echo "🛑 To stop containers: docker-compose down"
echo "🔄 To rebuild: docker-compose build"
