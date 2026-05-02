<?php
use PHPUnit\Framework\TestCase;

/**
 * Smoke tests to verify:
 * 1. All expected API endpoint files exist at their declared paths.
 * 2. Frontend pages reference those endpoints via the apiUrl() helper
 *    rather than hardcoded bare relative paths that would break on
 *    case-sensitive file systems.
 */
final class ApiPathTest extends TestCase
{
    /** Root directory of the project */
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__);
    }

    // ===== API FILE EXISTENCE =====

    public function testSearchApiFileExists(): void
    {
        $this->assertFileExists(
            $this->root . '/api/search_api.php',
            'api/search_api.php must exist so the Search page can reach it'
        );
    }

    public function testShoppingActionApiFileExists(): void
    {
        $this->assertFileExists(
            $this->root . '/api/shopping_action.php',
            'api/shopping_action.php must exist for shopping-list actions'
        );
    }

    public function testMealRatingsApiFileExists(): void
    {
        $this->assertFileExists(
            $this->root . '/api/meal_ratings.php',
            'api/meal_ratings.php must exist for meal rating actions'
        );
    }

    public function testAnalyticsApiFileExists(): void
    {
        $this->assertFileExists(
            $this->root . '/api/analytics.php',
            'api/analytics.php must exist for profile analytics'
        );
    }

    public function testUserPreferencesApiFileExists(): void
    {
        $this->assertFileExists(
            $this->root . '/api/user_preferences.php',
            'api/user_preferences.php must exist for saving preferences'
        );
    }

    public function testUploadAvatarApiFileExists(): void
    {
        $this->assertFileExists(
            $this->root . '/api/upload_avatar.php',
            'api/upload_avatar.php must exist for avatar uploads'
        );
    }

    public function testCheckUsernameApiFileExists(): void
    {
        $this->assertFileExists(
            $this->root . '/api/check_username.php',
            'api/check_username.php must exist for username availability checks'
        );
    }

    // ===== FRONTEND USES apiUrl() HELPER =====

    /**
     * search.php must not hardcode a bare relative 'api/search_api.php'
     * path in its fetch() call – it should call apiUrl() instead.
     */
    public function testSearchPhpUsesApiUrlHelper(): void
    {
        $content = file_get_contents($this->root . '/search.php');

        // Must contain apiUrl( call for the search endpoint
        $this->assertStringContainsString(
            "apiUrl('api/search_api.php')",
            $content,
            'search.php must call apiUrl(\'api/search_api.php\') to build the fetch URL'
        );

        // Must NOT use old bare root-relative literal without the helper
        $this->assertStringNotContainsString(
            '`/api/search_api.php?',
            $content,
            'search.php must not hardcode /api/search_api.php without apiUrl()'
        );
    }

    /**
     * assets/js/main.js must expose the apiUrl() function and use it for
     * all internal fetch calls.
     */
    public function testMainJsDefinesApiUrlHelper(): void
    {
        $content = file_get_contents($this->root . '/assets/js/main.js');

        $this->assertStringContainsString(
            'function apiUrl(',
            $content,
            'main.js must define the apiUrl() helper function'
        );
    }

    public function testMainJsUsesApiUrlForShoppingAction(): void
    {
        $content = file_get_contents($this->root . '/assets/js/main.js');

        $this->assertStringContainsString(
            "apiUrl('api/shopping_action.php')",
            $content,
            'main.js must use apiUrl() for shopping_action.php calls'
        );

        $this->assertStringNotContainsString(
            "fetch('/api/shopping_action.php'",
            $content,
            'main.js must not hardcode /api/shopping_action.php without apiUrl()'
        );
    }

    public function testMainJsUsesApiUrlForUploadAvatar(): void
    {
        $content = file_get_contents($this->root . '/assets/js/main.js');

        $this->assertStringContainsString(
            "apiUrl('api/upload_avatar.php')",
            $content,
            'main.js must use apiUrl() for upload_avatar.php calls'
        );
    }

    public function testMainJsUsesApiUrlForCheckUsername(): void
    {
        $content = file_get_contents($this->root . '/assets/js/main.js');

        $this->assertStringContainsString(
            "apiUrl('api/check_username.php')",
            $content,
            'main.js must use apiUrl() for check_username.php calls'
        );
    }
}
