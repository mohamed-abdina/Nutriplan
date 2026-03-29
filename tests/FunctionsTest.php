<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

final class FunctionsTest extends TestCase
{
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
}
