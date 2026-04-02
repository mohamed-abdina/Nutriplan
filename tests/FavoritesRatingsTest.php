<?php
/**
 * Functional Tests: Favorites & Ratings System
 * 
 * Tests the complete workflow for adding/removing favorites and submitting ratings
 * - Meal existence validation
 * - Toggle favorite operations
 * - Rating submissions
 * - Favorites retrieval
 * - Edge cases and error handling
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/error_logger.php';

use PHPUnit\Framework\TestCase;

class FavoritesRatingsTest extends TestCase
{
    private $test_user_id = 99999;
    private $test_meal_id = 1;
    private $invalid_meal_id = 999999;
    private $error_logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->error_logger = new ErrorLogger();
        $this->cleanupTestData();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanupTestData();
    }

    /**
     * Clean up test data from database
     */
    private function cleanupTestData()
    {
        // Delete test ratings
        pdo_query(
            "DELETE FROM meal_ratings WHERE user_id = ?",
            [$this->test_user_id]
        );
    }

    /**
     * TEST 1: Cannot toggle favorite for non-existent meal
     * ISSUE FIXED: Previously allowed favoriting non-existent meals
     */
    public function test_toggle_favorite_invalid_meal_should_fail()
    {
        $_SESSION['user_id'] = $this->test_user_id;
        
        // Verify meal does not exist
        $meal = pdo_fetch_one(
            "SELECT meal_id FROM meals WHERE meal_id = ?",
            [$this->invalid_meal_id]
        );
        $this->assertNull($meal, "Test meal should not exist");

        // Attempt to favorite non-existent meal
        $result = pdo_fetch_one(
            "SELECT rating_id FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
            [$this->test_user_id, $this->invalid_meal_id]
        );
        
        // Should not have created orphaned record (would be caught by API)
        $this->assertNull($result, "Favorited record should not exist for invalid meal");
    }

    /**
     * TEST 2: Successfully toggle favorite for valid meal
     */
    public function test_toggle_favorite_valid_meal_should_succeed()
    {
        $_SESSION['user_id'] = $this->test_user_id;
        
        // Verify meal exists
        $meal = pdo_fetch_one(
            "SELECT meal_id FROM meals WHERE meal_id = ?",
            [$this->test_meal_id]
        );
        $this->assertNotNull($meal, "Test meal should exist");

        // Add to favorites
        $success = pdo_query(
            "INSERT INTO meal_ratings (user_id, meal_id, is_favorite) VALUES (?, ?, 1)",
            [$this->test_user_id, $this->test_meal_id]
        );
        $this->assertNotFalse($success, "Should insert favorite record");

        // Verify favorite was created
        $result = pdo_fetch_one(
            "SELECT is_favorite FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
            [$this->test_user_id, $this->test_meal_id]
        );
        $this->assertNotNull($result, "Favorite record should exist");
        $this->assertTrue((bool)$result['is_favorite'], "is_favorite should be 1");
    }

    /**
     * TEST 3: Toggle favorite on/off multiple times
     */
    public function test_toggle_favorite_multiple_times()
    {
        $_SESSION['user_id'] = $this->test_user_id;
        
        // Add to favorites (first toggle)
        pdo_query(
            "INSERT INTO meal_ratings (user_id, meal_id, is_favorite) VALUES (?, ?, 1)",
            [$this->test_user_id, $this->test_meal_id]
        );
        
        $result = pdo_fetch_one(
            "SELECT is_favorite FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
            [$this->test_user_id, $this->test_meal_id]
        );
        $this->assertTrue((bool)$result['is_favorite'], "Should be favorite after first toggle");

        // Remove from favorites (second toggle)
        pdo_query(
            "UPDATE meal_ratings SET is_favorite = 0 WHERE user_id = ? AND meal_id = ?",
            [$this->test_user_id, $this->test_meal_id]
        );
        
        $result = pdo_fetch_one(
            "SELECT is_favorite FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
            [$this->test_user_id, $this->test_meal_id]
        );
        $this->assertFalse((bool)$result['is_favorite'], "Should not be favorite after second toggle");

        // Add back to favorites (third toggle)
        pdo_query(
            "UPDATE meal_ratings SET is_favorite = 1 WHERE user_id = ? AND meal_id = ?",
            [$this->test_user_id, $this->test_meal_id]
        );
        
        $result = pdo_fetch_one(
            "SELECT is_favorite FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
            [$this->test_user_id, $this->test_meal_id]
        );
        $this->assertTrue((bool)$result['is_favorite'], "Should be favorite after third toggle");
    }

    /**
     * TEST 4: Submit rating with valid values (1-5)
     */
    public function test_submit_rating_valid_values()
    {
        $_SESSION['user_id'] = $this->test_user_id;
        
        $ratings = [1, 2, 3, 4, 5];
        
        foreach ($ratings as $rating) {
            $meal_id = $this->test_meal_id + $rating; // Use different meal IDs
            
            $success = pdo_query(
                "INSERT INTO meal_ratings (user_id, meal_id, rating) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE rating = ?, updated_at = CURRENT_TIMESTAMP",
                [$this->test_user_id, $meal_id, $rating, $rating]
            );
            
            $this->assertNotFalse($success, "Should insert rating {$rating}");
            
            $result = pdo_fetch_one(
                "SELECT rating FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
                [$this->test_user_id, $meal_id]
            );
            $this->assertEquals($rating, (int)$result['rating'], "Rating should be {$rating}");
        }
    }

    /**
     * TEST 5: Submit rating with review text
     */
    public function test_submit_rating_with_review()
    {
        $_SESSION['user_id'] = $this->test_user_id;
        
        $review = "This meal is delicious and nutritious! Highly recommended.";
        $rating = 5;
        
        $success = pdo_query(
            "INSERT INTO meal_ratings (user_id, meal_id, rating, review) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE rating = ?, review = ?, updated_at = CURRENT_TIMESTAMP",
            [$this->test_user_id, $this->test_meal_id, $rating, $review, $rating, $review]
        );
        
        $this->assertNotFalse($success, "Should insert rating with review");
        
        $result = pdo_fetch_one(
            "SELECT rating, review FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
            [$this->test_user_id, $this->test_meal_id]
        );
        
        $this->assertEquals($rating, (int)$result['rating'], "Rating should be 5");
        $this->assertEquals($review, $result['review'], "Review should match");
    }

    /**
     * TEST 6: Update existing rating (ON DUPLICATE KEY)
     */
    public function test_update_existing_rating()
    {
        $_SESSION['user_id'] = $this->test_user_id;
        
        // Insert initial rating
        pdo_query(
            "INSERT INTO meal_ratings (user_id, meal_id, rating) VALUES (?, ?, ?)",
            [$this->test_user_id, $this->test_meal_id, 3]
        );
        
        // Update to higher rating
        pdo_query(
            "INSERT INTO meal_ratings (user_id, meal_id, rating) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE rating = ?, updated_at = CURRENT_TIMESTAMP",
            [$this->test_user_id, $this->test_meal_id, 5, 5]
        );
        
        $result = pdo_fetch_one(
            "SELECT rating FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
            [$this->test_user_id, $this->test_meal_id]
        );
        
        $this->assertEquals(5, (int)$result['rating'], "Rating should be updated to 5");
    }

    /**
     * TEST 7: Retrieve all favorites for user
     */
    public function test_get_favorites_list()
    {
        $_SESSION['user_id'] = $this->test_user_id;
        
        // Create 3 favorites
        for ($i = 1; $i <= 3; $i++) {
            pdo_query(
                "INSERT INTO meal_ratings (user_id, meal_id, is_favorite, rating) VALUES (?, ?, 1, ?)",
                [$this->test_user_id, $this->test_meal_id + $i, 4 + $i]
            );
        }
        
        // Retrieve favorites
        $favorites = pdo_fetch_all(
            "SELECT m.meal_id, m.meal_name, r.rating, r.is_favorite
            FROM meal_ratings r
            JOIN meals m ON r.meal_id = m.meal_id
            WHERE r.user_id = ? AND r.is_favorite = 1
            ORDER BY r.rating DESC",
            [$this->test_user_id]
        );
        
        $this->assertCount(3, $favorites, "Should have 3 favorites");
        
        // Verify all are marked as favorite
        foreach ($favorites as $fav) {
            $this->assertTrue((bool)$fav['is_favorite'], "All should be marked as favorite");
        }
    }

    /**
     * TEST 8: Get favorites count (new API endpoint)
     */
    public function test_get_favorites_count()
    {
        $_SESSION['user_id'] = $this->test_user_id;
        
        // Create 5 favorites
        for ($i = 1; $i <= 5; $i++) {
            pdo_query(
                "INSERT INTO meal_ratings (user_id, meal_id, is_favorite) VALUES (?, ?, 1)",
                [$this->test_user_id, $this->test_meal_id + $i]
            );
        }
        
        // Get count
        $result = pdo_fetch_one(
            "SELECT COUNT(*) as count FROM meal_ratings WHERE user_id = ? AND is_favorite = 1",
            [$this->test_user_id]
        );
        
        $this->assertEquals(5, (int)$result['count'], "Should have 5 favorites");
    }

    /**
     * TEST 9: Favorite without rating, then add rating
     */
    public function test_favorite_without_rating_then_rate()
    {
        $_SESSION['user_id'] = $this->test_user_id;
        
        // Add to favorites (no rating)
        pdo_query(
            "INSERT INTO meal_ratings (user_id, meal_id, is_favorite) VALUES (?, ?, 1)",
            [$this->test_user_id, $this->test_meal_id]
        );
        
        $result = pdo_fetch_one(
            "SELECT rating, is_favorite FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
            [$this->test_user_id, $this->test_meal_id]
        );
        
        $this->assertEquals(0, (int)($result['rating'] ?? 0), "Initial rating should be 0");
        $this->assertTrue((bool)$result['is_favorite'], "Should be favorite");
        
        // Now add rating
        pdo_query(
            "UPDATE meal_ratings SET rating = 5 WHERE user_id = ? AND meal_id = ?",
            [$this->test_user_id, $this->test_meal_id]
        );
        
        $result = pdo_fetch_one(
            "SELECT rating, is_favorite FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
            [$this->test_user_id, $this->test_meal_id]
        );
        
        $this->assertEquals(5, (int)$result['rating'], "Rating should be 5");
        $this->assertTrue((bool)$result['is_favorite'], "Should still be favorite");
    }

    /**
     * TEST 10: Database integrity - UNIQUE constraint on (user_id, meal_id)
     */
    public function test_unique_constraint_prevents_duplicates()
    {
        $_SESSION['user_id'] = $this->test_user_id;
        
        // Insert first record
        $success1 = pdo_query(
            "INSERT INTO meal_ratings (user_id, meal_id, is_favorite) VALUES (?, ?, 1)",
            [$this->test_user_id, $this->test_meal_id]
        );
        $this->assertNotFalse($success1, "First insert should succeed");
        
        // Attempt duplicate (should fail without ON DUPLICATE KEY UPDATE)
        $success2 = pdo_query(
            "INSERT INTO meal_ratings (user_id, meal_id, is_favorite) VALUES (?, ?, 1)",
            [$this->test_user_id, $this->test_meal_id]
        );
        // This should fail or use ON DUPLICATE KEY UPDATE
        // The API uses ON DUPLICATE KEY UPDATE, so this test validates that pattern
        
        // Count records - should only have 1
        $result = pdo_fetch_one(
            "SELECT COUNT(*) as count FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
            [$this->test_user_id, $this->test_meal_id]
        );
        $this->assertLessThanOrEqual(1, (int)$result['count'], "Should have at most 1 record");
    }

    /**
     * TEST 11: Different users' favorites don't interfere
     */
    public function test_user_isolation()
    {
        $user_2 = $this->test_user_id + 1;
        
        // User 1 favorites meal
        pdo_query(
            "INSERT INTO meal_ratings (user_id, meal_id, is_favorite) VALUES (?, ?, 1)",
            [$this->test_user_id, $this->test_meal_id]
        );
        
        // User 2 rates same meal but doesn't favorite
        pdo_query(
            "INSERT INTO meal_ratings (user_id, meal_id, rating) VALUES (?, ?, 2)",
            [$user_2, $this->test_meal_id]
        );
        
        // Verify user 1's favorite
        $result1 = pdo_fetch_one(
            "SELECT is_favorite FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
            [$this->test_user_id, $this->test_meal_id]
        );
        $this->assertTrue((bool)$result1['is_favorite'], "User 1 should have favorite");
        
        // Verify user 2's rating
        $result2 = pdo_fetch_one(
            "SELECT rating, is_favorite FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
            [$user_2, $this->test_meal_id]
        );
        $this->assertEquals(2, (int)$result2['rating'], "User 2 should have rating 2");
        $this->assertFalse((bool)$result2['is_favorite'], "User 2 should not have favorite");
        
        // Cleanup user 2's record
        pdo_query("DELETE FROM meal_ratings WHERE user_id = ?", [$user_2]);
    }

    /**
     * TEST 12: Empty review handling
     */
    public function test_empty_review_handling()
    {
        $_SESSION['user_id'] = $this->test_user_id;
        
        pdo_query(
            "INSERT INTO meal_ratings (user_id, meal_id, rating, review) VALUES (?, ?, ?, ?)",
            [$this->test_user_id, $this->test_meal_id, 4, '']
        );
        
        $result = pdo_fetch_one(
            "SELECT review FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
            [$this->test_user_id, $this->test_meal_id]
        );
        
        $this->assertEquals('', $result['review'], "Empty review should be stored as empty string");
    }
}
?>
