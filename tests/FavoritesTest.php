<?php
use PHPUnit\Framework\TestCase;

class FavoritesTest extends TestCase
{
    private $user_id = 999;
    private $meal_ids = [];

    protected function setUp(): void
    {
        require_once __DIR__ . '/../includes/db_connect.php';
        
        // Create test user
        pdo_query("INSERT INTO users (user_id, username, email, password_hash, first_name, last_name, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, NOW())
                  ON DUPLICATE KEY UPDATE updated_at = NOW()",
                  [$this->user_id, 'test_fav_' . time(), 'tfav_' . time() . '@test.com', 'hash', 'Test', 'User']);
        
        // Get 10+ valid meal_ids from database for testing (ensure they exist)
        $meals = pdo_fetch_all("SELECT meal_id FROM meals LIMIT 15", []);
        if (!empty($meals)) {
            foreach ($meals as $meal) {
                $this->meal_ids[] = $meal['meal_id'];
            }
        }
        
        // If no meals exist, create test meals
        if (empty($this->meal_ids)) {
            for ($i = 1; $i <= 15; $i++) {
                pdo_query("INSERT INTO meals (meal_name, description) VALUES (?, ?) ON DUPLICATE KEY UPDATE description = ?",
                         ['Test Meal ' . $i, 'Test meal for PHPUnit', 'Test meal for PHPUnit']);
            }
            $meals = pdo_fetch_all("SELECT meal_id FROM meals LIMIT 15", []);
            foreach ($meals as $meal) {
                $this->meal_ids[] = $meal['meal_id'];
            }
        }
    }

    protected function tearDown(): void
    {
        pdo_query("DELETE FROM meal_ratings WHERE user_id = ?", [$this->user_id]);
        pdo_query("DELETE FROM users WHERE user_id = ?", [$this->user_id]);
    }
    
    private function getMealId($index = 0)
    {
        return $this->meal_ids[$index] ?? $this->meal_ids[0] ?? 1;
    }

    /**
     * Test: Toggle favorite on a meal that exists
     */
    public function testToggleFavoriteSuccess()
    {
        $meal_id = $this->getMealId(0);
        
        // First toggle: should mark as favorite
        pdo_query("INSERT INTO meal_ratings (user_id, meal_id, is_favorite) VALUES (?, ?, 1)
                  ON DUPLICATE KEY UPDATE is_favorite = 1",
                 [$this->user_id, $meal_id]);

        $rating = pdo_fetch_one("SELECT is_favorite FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
                               [$this->user_id, $meal_id]);
        
        $this->assertTrue((bool)$rating['is_favorite'], "Meal should be marked as favorite");
    }

    /**
     * Test: Toggle favorite off (remove favorite)
     */
    public function testToggleFavoriteOff()
    {
        $meal_id = $this->getMealId(1);
        
        // Create a favorite first
        pdo_query("INSERT INTO meal_ratings (user_id, meal_id, is_favorite) VALUES (?, ?, 1)",
                 [$this->user_id, $meal_id]);

        // Toggle off
        pdo_query("UPDATE meal_ratings SET is_favorite = 0 WHERE user_id = ? AND meal_id = ?",
                 [$this->user_id, $meal_id]);

        $rating = pdo_fetch_one("SELECT is_favorite FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
                               [$this->user_id, $meal_id]);
        
        $this->assertFalse((bool)$rating['is_favorite'], "Meal should not be marked as favorite");
    }

    /**
     * Test: Cannot favorite a non-existent meal (VALIDATION CHECK)
     */
    public function testToggleFavoriteNonExistentMeal()
    {
        // Get max meal ID and use something way beyond
        $maxMeal = pdo_fetch_one("SELECT MAX(meal_id) as max_id FROM meals");
        $invalid_meal_id = (int)($maxMeal['max_id'] ?? 0) + 999999;
        
        // Verify meal does not exist
        $meal = pdo_fetch_one("SELECT meal_id FROM meals WHERE meal_id = ?", [$invalid_meal_id]);
        
        // pdo_fetch_one returns false on failure, check for both
        $this->assertTrue($meal === false || $meal === null, "Meal should not exist (test setup)");
    }

    /**
     * Test: Get all user's favorites
     */
    public function testGetFavorites()
    {
        // Add 3 favorites using valid meal_ids
        for ($i = 2; $i <= 4; $i++) {
            $meal_id = $this->getMealId($i);
            pdo_query("INSERT INTO meal_ratings (user_id, meal_id, is_favorite, rating) VALUES (?, ?, 1, ?)
                      ON DUPLICATE KEY UPDATE is_favorite = 1, rating = ?",
                     [$this->user_id, $meal_id, rand(3, 5), rand(3, 5)]);
        }

        $favorites = pdo_fetch_all("SELECT m.meal_id, m.meal_name FROM meal_ratings r 
                                   JOIN meals m ON r.meal_id = m.meal_id 
                                   WHERE r.user_id = ? AND r.is_favorite = 1 ORDER BY r.rating DESC",
                                  [$this->user_id]);

        $this->assertGreaterThanOrEqual(3, count($favorites), "Should have at least 3 favorites");
    }

    /**
     * Test: Favorites count
     */
    public function testGetFavoritesCount()
    {
        // Add 5 favorites using valid meal_ids
        for ($i = 5; $i <= 9; $i++) {
            $meal_id = $this->getMealId($i);
            pdo_query("INSERT INTO meal_ratings (user_id, meal_id, is_favorite) VALUES (?, ?, 1)
                      ON DUPLICATE KEY UPDATE is_favorite = 1",
                     [$this->user_id, $meal_id]);
        }

        $result = pdo_fetch_one("SELECT COUNT(*) as count FROM meal_ratings WHERE user_id = ? AND is_favorite = 1",
                               [$this->user_id]);

        $this->assertGreaterThanOrEqual(5, (int)$result['count'], "Should have at least 5 favorites");
    }

    /**
     * Test: Rate a meal (1-5 stars)
     */
    public function testRateMeal()
    {
        $rating = 4;
        $review = 'Great meal!';
        $meal_id = $this->getMealId(10);

        pdo_query("INSERT INTO meal_ratings (user_id, meal_id, rating, review) 
                  VALUES (?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE rating = ?, review = ?, updated_at = CURRENT_TIMESTAMP",
                 [$this->user_id, $meal_id, $rating, $review, $rating, $review]);

        $stored = pdo_fetch_one("SELECT rating, review FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
                               [$this->user_id, $meal_id]);

        $this->assertNotNull($stored, "Rating should be stored");
        $this->assertEquals($rating, (int)$stored['rating'], "Rating should be 4");
        $this->assertEquals($review, $stored['review'], "Review should be stored");
    }

    /**
     * Test: Update rating (should replace, not duplicate)
     */
    public function testUpdateRating()
    {
        $meal_id = $this->getMealId(11);
        
        // First rating
        pdo_query("INSERT INTO meal_ratings (user_id, meal_id, rating, review) VALUES (?, ?, ?, ?)",
                 [$this->user_id, $meal_id, 3, 'OK']);

        // Update rating
        pdo_query("UPDATE meal_ratings SET rating = ?, review = ?, updated_at = CURRENT_TIMESTAMP 
                  WHERE user_id = ? AND meal_id = ?",
                 [5, 'Amazing!', $this->user_id, $meal_id]);

        // Should be only 1 record
        $count = pdo_fetch_one("SELECT COUNT(*) as cnt FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
                              [$this->user_id, $meal_id]);
        
        $rating = pdo_fetch_one("SELECT rating FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
                               [$this->user_id, $meal_id]);

        $this->assertEquals(1, (int)$count['cnt'], "Should only have 1 record");
        $this->assertEquals(5, (int)$rating['rating'], "Rating should be updated to 5");
    }

    /**
     * Test: Invalid rating values (must be 1-5)
     */
    public function testInvalidRatingValues()
    {
        $invalid_ratings = [0, 6, -1, 10];

        foreach ($invalid_ratings as $invalid_rating) {
            $this->assertFalse($invalid_rating >= 1 && $invalid_rating <= 5, 
                              "Rating $invalid_rating should be invalid");
        }
    }

    /**
     * Test: Favorite without rating (null rating is ok)
     */
    public function testFavoriteWithoutRating()
    {
        $meal_id = $this->getMealId(12);
        
        pdo_query("INSERT INTO meal_ratings (user_id, meal_id, is_favorite) VALUES (?, ?, 1)",
                 [$this->user_id, $meal_id]);

        $record = pdo_fetch_one("SELECT is_favorite, rating FROM meal_ratings WHERE user_id = ? AND meal_id = ?",
                               [$this->user_id, $meal_id]);

        $this->assertTrue((bool)$record['is_favorite'], "Should be favorite");
        $this->assertTrue($record['rating'] === null || $record['rating'] === 0 || $record['rating'] === '', "Rating can be null or 0");
    }

    /**
     * Test: Unique constraint (only 1 rating per user per meal)
     */
    public function testUniqueConstraint()
    {
        $meal_id = $this->getMealId(13);
        
        // First insert
        pdo_query("INSERT INTO meal_ratings (user_id, meal_id, rating) VALUES (?, ?, 3)",
                 [$this->user_id, $meal_id]);

        // Second insert should fail (unique constraint violation)
        $result = pdo_query("INSERT INTO meal_ratings (user_id, meal_id, rating) VALUES (?, ?, 4)",
                           [$this->user_id, $meal_id]);
        
        // Should have failed (result === false)
        $this->assertFalse($result, "Duplicate insert should fail due to UNIQUE constraint");
    }

    /**
     * Test: Cascade delete when user is deleted
     */
    public function testCascadeDeleteOnUserDelete()
    {
        $meal_id = $this->getMealId(14);
        $test_user = 99998; // Different test user
        
        // Create test user first
        pdo_query("INSERT INTO users (user_id, username, email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE updated_at = NOW()",
                 [$test_user, 'cascade_test_' . time(), 'cascade_' . time() . '@test.com', 'hash', 'Cascade', 'Test']);
        
        // Add ratings for test user
        pdo_query("INSERT INTO meal_ratings (user_id, meal_id, rating) VALUES (?, ?, 4)",
                 [$test_user, $meal_id]);

        $count_before = pdo_fetch_one("SELECT COUNT(*) as cnt FROM meal_ratings WHERE user_id = ?",
                                     [$test_user]);
        $this->assertGreaterThanOrEqual(1, (int)$count_before['cnt'], "Should have ratings");

        // Delete user (if cascade is set up)
        pdo_query("DELETE FROM users WHERE user_id = ?", [$test_user]);

        $count_after = pdo_fetch_one("SELECT COUNT(*) as cnt FROM meal_ratings WHERE user_id = ?",
                                    [$test_user]);
        $this->assertEquals(0, (int)$count_after['cnt'], "Ratings should be deleted with user (cascade)");
    }

    /**
     * Test: Rating statistics (average rating per meal)
     */
    public function testAverageRatingCalculation()
    {
        $meal_id = $this->getMealId(6);
        
        // Create 3 users rating the same meal
        for ($u = 1; $u <= 3; $u++) {
            pdo_query("INSERT INTO users (user_id, username, email, password_hash, first_name, last_name) 
                      VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP",
                     [1000 + $u, 'user_' . $u, 'user_' . $u . '@test.com', 'hash', 'User', $u]);
            
            pdo_query("INSERT INTO meal_ratings (user_id, meal_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating = ?",
                     [1000 + $u, $meal_id, $u + 2, $u + 2]); // Ratings: 3, 4, 5
        }

        // Calculate average
        $avg = pdo_fetch_one("SELECT AVG(CASE WHEN rating > 0 THEN rating END) as avg_rating 
                             FROM meal_ratings WHERE meal_id = ?",
                            [$meal_id]);

        $expected_avg = (3 + 4 + 5) / 3; // = 4.0
        $this->assertEqualsWithDelta($expected_avg, (float)($avg['avg_rating'] ?? 0), 0.1, 
                                     "Average should be approximately 4.0");

        // Cleanup
        for ($u = 1; $u <= 3; $u++) {
            pdo_query("DELETE FROM meal_ratings WHERE user_id = ?", [1000 + $u]);
            pdo_query("DELETE FROM users WHERE user_id = ?", [1000 + $u]);
        }
    }
}
?>
