<?php

use PHPUnit\Framework\TestCase;

class IntegrationMealPlanningTest extends TestCase
{
    private int $userId = 0;
    private int $mealId = 0;

    protected function setUp(): void
    {
        pdo_query("ALTER TABLE meal_planning ADD COLUMN IF NOT EXISTS notes TEXT");
        pdo_query("ALTER TABLE meal_planning ADD COLUMN IF NOT EXISTS portion_multiplier DECIMAL(4,2) NOT NULL DEFAULT 1.00");
        pdo_query("ALTER TABLE meal_planning ADD COLUMN IF NOT EXISTS reminder_at DATETIME NULL");
        pdo_query("CREATE TABLE IF NOT EXISTS user_nutrition_goals (
            goal_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            daily_calories_target INT NOT NULL DEFAULT 2000,
            daily_protein_target INT NOT NULL DEFAULT 75,
            daily_carbs_target INT NOT NULL DEFAULT 250,
            daily_fats_target INT NOT NULL DEFAULT 70,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )");
        pdo_query("CREATE TABLE IF NOT EXISTS meal_plan_shares (
            share_id INT AUTO_INCREMENT PRIMARY KEY,
            owner_user_id INT NOT NULL,
            target_user_id INT NOT NULL,
            planned_date DATE NOT NULL,
            can_edit TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_share (owner_user_id, target_user_id, planned_date),
            FOREIGN KEY (owner_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (target_user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )");

        $user = pdo_fetch_one("SELECT user_id FROM users ORDER BY user_id ASC LIMIT 1");
        $meal = pdo_fetch_one("SELECT meal_id FROM meals ORDER BY meal_id ASC LIMIT 1");

        $this->assertIsArray($user, 'Expected at least one user in DB');
        $this->assertIsArray($meal, 'Expected at least one meal in DB');

        $this->userId = (int)$user['user_id'];
        $this->mealId = (int)$meal['meal_id'];
    }

    public function testMealPlanningSchemaSupportsCoreFeatures(): void
    {
        $tables = pdo_fetch_all(
            "SELECT table_name FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name IN ('meal_planning', 'user_nutrition_goals', 'meal_plan_shares')"
        );

        $tableNames = array_map(static function ($row) {
            return $row['table_name'];
        }, $tables ?: []);

        $this->assertContains('meal_planning', $tableNames);
        $this->assertContains('user_nutrition_goals', $tableNames);
        $this->assertContains('meal_plan_shares', $tableNames);
    }

    public function testCanPersistNutritionGoals(): void
    {
        $upsert = pdo_query(
            "INSERT INTO user_nutrition_goals (user_id, daily_calories_target, daily_protein_target, daily_carbs_target, daily_fats_target)
             VALUES (?, 2300, 140, 260, 75)
             ON DUPLICATE KEY UPDATE
                daily_calories_target = VALUES(daily_calories_target),
                daily_protein_target = VALUES(daily_protein_target),
                daily_carbs_target = VALUES(daily_carbs_target),
                daily_fats_target = VALUES(daily_fats_target)",
            [$this->userId]
        );

        $this->assertNotFalse($upsert);

        $row = pdo_fetch_one(
            "SELECT daily_calories_target, daily_protein_target, daily_carbs_target, daily_fats_target
             FROM user_nutrition_goals
             WHERE user_id = ?",
            [$this->userId]
        );

        $this->assertIsArray($row);
        $this->assertSame(2300, (int)$row['daily_calories_target']);
        $this->assertSame(140, (int)$row['daily_protein_target']);
        $this->assertSame(260, (int)$row['daily_carbs_target']);
        $this->assertSame(75, (int)$row['daily_fats_target']);
    }

    public function testCanCreateMealPlanEntryWithReminderAndPortion(): void
    {
        $plannedDate = date('Y-m-d', strtotime('+1 day'));
        $reminderAt = date('Y-m-d H:i:s', strtotime('+1 day 08:00:00'));

        $result = pdo_query(
            "INSERT INTO meal_planning (user_id, meal_id, planned_date, meal_type, notes, portion_multiplier, reminder_at)
             VALUES (?, ?, ?, 'lunch', 'Integration test', 1.25, ?)
             ON DUPLICATE KEY UPDATE
                notes = VALUES(notes),
                portion_multiplier = VALUES(portion_multiplier),
                reminder_at = VALUES(reminder_at)",
            [$this->userId, $this->mealId, $plannedDate, $reminderAt]
        );

        $this->assertNotFalse($result);

        $row = pdo_fetch_one(
            "SELECT portion_multiplier, reminder_at
             FROM meal_planning
             WHERE user_id = ? AND meal_id = ? AND planned_date = ? AND meal_type = 'lunch'",
            [$this->userId, $this->mealId, $plannedDate]
        );

        $this->assertIsArray($row);
        $this->assertSame('1.25', (string)$row['portion_multiplier']);
        $this->assertNotEmpty($row['reminder_at']);
    }
}
