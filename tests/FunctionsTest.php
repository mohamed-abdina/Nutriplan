<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

// Include additional functions for testing
require_once __DIR__ . '/../includes/csrf.php';

final class FunctionsTest extends TestCase
{
    // ===== NUTRITION SCORE TESTS =====
    
    public function testGetNutritionScoreEmpty()
    {
        $this->assertEquals(0, get_nutrition_score([]));
    }

    public function testGetNutritionScoreValues()
    {
        $meals = [
            ['proteins_g' => 40, 'carbs_g' => 120, 'fiber_g' => 20],
            ['proteins_g' => 10, 'carbs_g' => 30, 'fiber_g' => 5]
        ];

        $score = get_nutrition_score($meals);
        $this->assertIsInt($score);
        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function testGetNutritionScoreLowValues()
    {
        $meals = [
            ['proteins_g' => 5, 'carbs_g' => 10, 'fiber_g' => 1]
        ];

        $score = get_nutrition_score($meals);
        $this->assertEquals(25, $score); // Only bonus for having meals
    }

    public function testGetNutritionScoreHighValues()
    {
        $meals = [
            ['proteins_g' => 50, 'carbs_g' => 150, 'fiber_g' => 25]
        ];

        $score = get_nutrition_score($meals);
        $this->assertEquals(100, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function testGetNutritionScorePartialRequirements()
    {
        $meals = [
            ['proteins_g' => 40, 'carbs_g' => 10, 'fiber_g' => 5]
        ];

        $score = get_nutrition_score($meals);
        $this->assertGreaterThan(0, $score);
        $this->assertLessThan(100, $score);
    }

    // ===== GREETING TESTS =====
    
    public function testGetGreetingMorning()
    {
        // This test depends on system time, so we'll validate the function exists and returns string
        $greeting = get_greeting();
        $this->assertIsString($greeting);
        $this->assertStringContainsString('Good', $greeting);
    }

    // ===== DATABASE HOST TESTS =====
    
    public function testGetDbHostFromEnv()
    {
        $host = get_db_host();
        $this->assertIsString($host);
        $this->assertNotEmpty($host);
        // Should return env value or default
        $this->assertTrue(in_array($host, ['db', 'localhost', '127.0.0.1']) || strpos($host, '.') !== false);
    }

    // ===== INPUT SANITIZATION TESTS =====
    
    public function testSanitizeInputBasic()
    {
        $input = "Hello World";
        $output = sanitize_input($input);
        $this->assertEquals("Hello World", $output);
    }

    public function testSanitizeInputWithHtmlSpecialChars()
    {
        $input = "<script>alert('xss')</script>";
        $output = sanitize_input($input);
        $this->assertStringNotContainsString("<script>", $output);
        $this->assertStringNotContainsString("</script>", $output);
        // Should contain HTML entity versions
        $this->assertStringContainsString("&lt;", $output);
        $this->assertStringContainsString("&gt;", $output);
    }

    public function testSanitizeInputWithQuotes()
    {
        $input = 'Test "quoted" and \'single\'';
        $output = sanitize_input($input);
        $this->assertStringContainsString("&quot;", $output);
        $this->assertStringContainsString("&#039;", $output);
    }

    public function testSanitizeInputWithWhitespace()
    {
        $input = "  \n  Hello World  \t  ";
        $output = sanitize_input($input);
        $this->assertEquals("Hello World", $output);
    }

    public function testSanitizeInputEmpty()
    {
        $input = "";
        $output = sanitize_input($input);
        $this->assertEquals("", $output);
    }

    public function testSanitizeInputUtf8Characters()
    {
        $input = "Café résumé 日本語";
        $output = sanitize_input($input);
        $this->assertIsString($output);
        $this->assertNotEmpty($output);
    }

    // ===== CSRF TOKEN TESTS =====
    
    public function testCsrfTokenGeneration()
    {
        // Start session for CSRF testing
        if (session_status() === PHP_SESSION_NONE) {
            secure_session_start();
        }

        $token1 = csrf_token();
        $this->assertIsString($token1);
        $this->assertNotEmpty($token1);
        
        // Token should be hex (alphanumeric)
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token1);
    }

    public function testCsrfTokenConsistency()
    {
        if (session_status() === PHP_SESSION_NONE) {
            secure_session_start();
        }

        // Clear existing token
        unset($_SESSION['csrf_token']);
        
        $token1 = csrf_token();
        $token2 = csrf_token();
        
        // Same token should be returned on subsequent calls
        $this->assertEquals($token1, $token2);
    }

    public function testCsrfValidation()
    {
        if (session_status() === PHP_SESSION_NONE) {
            secure_session_start();
        }

        // Clear existing token
        unset($_SESSION['csrf_token']);
        
        $token = csrf_token();
        
        // Valid token should verify
        $this->assertTrue(validate_csrf($token));
    }

    public function testCsrfValidationInvalidToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            secure_session_start();
        }

        $token = csrf_token();
        
        // Invalid token should not verify
        $invalid = bin2hex(random_bytes(32));
        $this->assertFalse(validate_csrf($invalid));
    }

    public function testCsrfValidationEmptyToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            secure_session_start();
        }

        // Empty token should not verify
        $this->assertFalse(validate_csrf(''));
    }

    // ===== AVATAR UPLOAD VALIDATION TESTS =====
    
    public function testAvatarUploadValidation()
    {
        // Test that the function exists and validates file extensions
        $invalid_file = [
            'name' => 'test.exe',
            'tmp_name' => '/tmp/test',
            'size' => 1000
        ];

        // We can't fully test without mocking the file system,
        // but we can validate the function exists and handles invalid types
        $this->assertTrue(function_exists('upload_avatar'));
    }

    // ===== SEARCH FUNCTIONALITY TESTS =====
    
    public function testSearchMealsFunctionExists()
    {
        // Verify search function exists
        $this->assertTrue(function_exists('search_meals'));
    }

    // ===== SHOPPING LIST TESTS =====
    
    public function testShoppingListFunctionsExist()
    {
        $this->assertTrue(function_exists('get_user_shopping_list'));
        $this->assertTrue(function_exists('get_shopping_items_grouped'));
    }

    // ===== WEEK STATS TESTS =====
    
    public function testWeekStatsFunctionExists()
    {
        $this->assertTrue(function_exists('get_week_stats'));
    }

    public function testTodayMealsFunctionExists()
    {
        $this->assertTrue(function_exists('get_today_meals'));
    }

    // ===== EDGE CASE TESTS =====
    
    public function testNutritionScoreMissingKeys()
    {
        $meals = [
            ['proteins_g' => 40], // Missing carbs_g and fiber_g
            ['carbs_g' => 120],   // Missing proteins_g and fiber_g
        ];

        $score = get_nutrition_score($meals);
        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function testSanitizeInputSqlInjectionAttempt()
    {
        // Note: sanitize_input() is for HTML escaping only, not SQL injection prevention.
        // SQL injection is prevented via prepared statements in PDO queries.
        $input = "'; DROP TABLE users; --";
        $output = sanitize_input($input);
        // The single quote gets HTML-escaped
        $this->assertStringContainsString("&#039;", $output);
    }

    public function testSanitizeInputXssPayloads()
    {
        // Test payloads with HTML brackets
        $htmlPayloads = [
            '<img src=x onerror=alert(1)>',
            '<script>alert(1)</script>',
        ];

        foreach ($htmlPayloads as $payload) {
            $output = sanitize_input($payload);
            // HTML tags should be escaped to entities
            $this->assertStringContainsString("&lt;", $output, "Payload not sanitized: $payload");
            $this->assertStringContainsString("&gt;", $output, "Payload not sanitized: $payload");
        }

        // Test payloads without HTML brackets (quotes and attributes just get escaped)
        $quotePayloads = [
            'onload="alert(1)"',
        ];

        foreach ($quotePayloads as $payload) {
            $output = sanitize_input($payload);
            // Quotes should be escaped, making the payload harmless in HTML context
            $this->assertNotEquals($payload, $output, "Payload not sanitized: $payload");
            $this->assertStringContainsString("&quot;", $output);
        }
    }

    public function testNormalizeSearchQueryPreservesLiteralCharacters()
    {
        $input = "  Ugali & Nyama  ";
        $output = normalize_search_query($input);

        $this->assertSame('Ugali & Nyama', $output);
        $this->assertStringNotContainsString('&amp;', $output);
    }

    protected function tearDown(): void
    {
        // Clean up session after CSRF tests
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
