<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';
secure_session_start();

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../includes/error_logger.php';
require_once __DIR__ . '/../includes/csrf.php';

if (!$limiter->check_rate_limit('meal_planning', 20, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = '';

if ($method === 'POST') {
    $action = isset($_POST['action']) ? sanitize_input($_POST['action']) : '';
} else {
    $action = isset($_GET['action']) ? sanitize_input($_GET['action']) : '';
}

$mutating_actions = ['add_plan', 'remove_plan', 'update_goals', 'share_plan'];
if ($method === 'POST' && in_array($action, $mutating_actions, true)) {
    $csrf_token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!validate_csrf($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

$error_logger->log_api_call('meal_planning', $method, ['action' => $action], 200);

function ensure_meal_planning_schema() {
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
        can_edit TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_share (owner_user_id, target_user_id, planned_date),
        FOREIGN KEY (owner_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (target_user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
}

function validate_date_string($value) {
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}

function get_week_dates($start_date) {
    $dates = [];
    for ($i = 0; $i < 7; $i++) {
        $dates[] = date('Y-m-d', strtotime($start_date . " +{$i} day"));
    }
    return $dates;
}

ensure_meal_planning_schema();

if ($action === 'get_week') {
    $start_date = isset($_GET['start_date']) ? trim((string)$_GET['start_date']) : '';
    if ($start_date === '' || !validate_date_string($start_date)) {
        $start_date = date('Y-m-d', strtotime('monday this week'));
    }
    $end_date = date('Y-m-d', strtotime($start_date . ' +6 day'));

    $plans = pdo_fetch_all(
        "SELECT mp.plan_id, mp.user_id, u.username AS owner_username, mp.meal_id, mp.planned_date,
                mp.meal_type, mp.notes, mp.portion_multiplier, mp.reminder_at,
                m.meal_name, m.meal_icon, c.category_name,
                n.calories, n.proteins_g, n.carbs_g, n.fats_g
         FROM meal_planning mp
         JOIN meals m ON mp.meal_id = m.meal_id
         JOIN categories c ON m.category_id = c.category_id
         JOIN nutrition n ON m.meal_id = n.meal_id
         JOIN users u ON mp.user_id = u.user_id
         WHERE (
                mp.user_id = :user_id
                OR EXISTS (
                    SELECT 1 FROM meal_plan_shares s
                    WHERE s.owner_user_id = mp.user_id
                      AND s.target_user_id = :user_id_share
                      AND s.planned_date = mp.planned_date
                )
         )
         AND mp.planned_date BETWEEN :start_date AND :end_date
         ORDER BY mp.planned_date ASC, FIELD(mp.meal_type, 'breakfast', 'lunch', 'snack', 'dinner') ASC",
        [
            ':user_id' => $user_id,
            ':user_id_share' => $user_id,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
        ]
    );

    echo json_encode([
        'success' => true,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'week_dates' => get_week_dates($start_date),
        'plans' => $plans ?: [],
    ]);
    exit;
}

if ($action === 'add_plan') {
    $meal_id = isset($_POST['meal_id']) ? (int)$_POST['meal_id'] : 0;
    $planned_date = isset($_POST['planned_date']) ? trim((string)$_POST['planned_date']) : '';
    $meal_type = isset($_POST['meal_type']) ? strtolower(trim((string)$_POST['meal_type'])) : 'lunch';
    $notes = isset($_POST['notes']) ? trim((string)$_POST['notes']) : '';
    $portion_multiplier = isset($_POST['portion_multiplier']) ? (float)$_POST['portion_multiplier'] : 1.0;
    $reminder_at = isset($_POST['reminder_at']) ? trim((string)$_POST['reminder_at']) : '';

    if ($meal_id <= 0 || !validate_date_string($planned_date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid meal or date']);
        exit;
    }

    $valid_meal_types = ['breakfast', 'lunch', 'snack', 'dinner'];
    if (!in_array($meal_type, $valid_meal_types, true)) {
        $meal_type = 'lunch';
    }

    if ($portion_multiplier < 0.5 || $portion_multiplier > 3.0) {
        $portion_multiplier = 1.0;
    }

    $meal_exists = pdo_fetch_one("SELECT meal_id FROM meals WHERE meal_id = :meal_id", [':meal_id' => $meal_id]);
    if (!$meal_exists) {
        echo json_encode(['success' => false, 'message' => 'Meal not found']);
        exit;
    }

    $normalized_reminder = null;
    if ($reminder_at !== '') {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $reminder_at);
        if ($dt !== false) {
            $normalized_reminder = $dt->format('Y-m-d H:i:s');
        }
    }

    $result = pdo_query(
        "INSERT INTO meal_planning (user_id, meal_id, planned_date, meal_type, notes, portion_multiplier, reminder_at)
         VALUES (:user_id, :meal_id, :planned_date, :meal_type, :notes, :portion_multiplier, :reminder_at)
         ON DUPLICATE KEY UPDATE
            notes = VALUES(notes),
            portion_multiplier = VALUES(portion_multiplier),
            reminder_at = VALUES(reminder_at)",
        [
            ':user_id' => $user_id,
            ':meal_id' => $meal_id,
            ':planned_date' => $planned_date,
            ':meal_type' => $meal_type,
            ':notes' => $notes,
            ':portion_multiplier' => $portion_multiplier,
            ':reminder_at' => $normalized_reminder,
        ]
    );

    echo json_encode([
        'success' => $result !== false,
        'message' => $result !== false ? 'Meal scheduled successfully' : 'Failed to schedule meal'
    ]);
    exit;
}

if ($action === 'remove_plan') {
    $plan_id = isset($_POST['plan_id']) ? (int)$_POST['plan_id'] : 0;
    if ($plan_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid plan']);
        exit;
    }

    $result = pdo_query(
        "DELETE FROM meal_planning WHERE plan_id = :plan_id AND user_id = :user_id",
        [':plan_id' => $plan_id, ':user_id' => $user_id]
    );

    echo json_encode([
        'success' => $result !== false,
        'message' => $result !== false ? 'Scheduled meal removed' : 'Unable to remove scheduled meal'
    ]);
    exit;
}

if ($action === 'get_goals') {
    $goals = pdo_fetch_one(
        "SELECT daily_calories_target, daily_protein_target, daily_carbs_target, daily_fats_target
         FROM user_nutrition_goals WHERE user_id = :user_id",
        [':user_id' => $user_id]
    );

    if (!$goals) {
        $goals = [
            'daily_calories_target' => 2000,
            'daily_protein_target' => 75,
            'daily_carbs_target' => 250,
            'daily_fats_target' => 70,
        ];
    }

    echo json_encode(['success' => true, 'goals' => $goals]);
    exit;
}

if ($action === 'update_goals') {
    $calories = isset($_POST['daily_calories_target']) ? max(800, min(6000, (int)$_POST['daily_calories_target'])) : 2000;
    $protein = isset($_POST['daily_protein_target']) ? max(20, min(400, (int)$_POST['daily_protein_target'])) : 75;
    $carbs = isset($_POST['daily_carbs_target']) ? max(20, min(800, (int)$_POST['daily_carbs_target'])) : 250;
    $fats = isset($_POST['daily_fats_target']) ? max(10, min(300, (int)$_POST['daily_fats_target'])) : 70;

    $result = pdo_query(
        "INSERT INTO user_nutrition_goals (user_id, daily_calories_target, daily_protein_target, daily_carbs_target, daily_fats_target)
         VALUES (:user_id, :cal, :protein, :carbs, :fats)
         ON DUPLICATE KEY UPDATE
            daily_calories_target = VALUES(daily_calories_target),
            daily_protein_target = VALUES(daily_protein_target),
            daily_carbs_target = VALUES(daily_carbs_target),
            daily_fats_target = VALUES(daily_fats_target)",
        [
            ':user_id' => $user_id,
            ':cal' => $calories,
            ':protein' => $protein,
            ':carbs' => $carbs,
            ':fats' => $fats,
        ]
    );

    echo json_encode([
        'success' => $result !== false,
        'message' => $result !== false ? 'Nutrition goals saved' : 'Failed to save goals'
    ]);
    exit;
}

if ($action === 'share_plan') {
    $target_username = isset($_POST['target_username']) ? trim((string)$_POST['target_username']) : '';
    $planned_date = isset($_POST['planned_date']) ? trim((string)$_POST['planned_date']) : '';
    $can_edit = isset($_POST['can_edit']) ? (int)$_POST['can_edit'] : 0;

    if ($target_username === '' || !validate_date_string($planned_date)) {
        echo json_encode(['success' => false, 'message' => 'Username and valid date are required']);
        exit;
    }

    $target_user = pdo_fetch_one(
        "SELECT user_id FROM users WHERE username = :username",
        [':username' => $target_username]
    );

    if (!$target_user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    if ((int)$target_user['user_id'] === $user_id) {
        echo json_encode(['success' => false, 'message' => 'Cannot share with yourself']);
        exit;
    }

    $result = pdo_query(
        "INSERT INTO meal_plan_shares (owner_user_id, target_user_id, planned_date, can_edit)
         VALUES (:owner, :target, :planned_date, :can_edit)
         ON DUPLICATE KEY UPDATE can_edit = VALUES(can_edit)",
        [
            ':owner' => $user_id,
            ':target' => (int)$target_user['user_id'],
            ':planned_date' => $planned_date,
            ':can_edit' => $can_edit > 0 ? 1 : 0,
        ]
    );

    echo json_encode([
        'success' => $result !== false,
        'message' => $result !== false ? 'Meal plan shared' : 'Failed to share meal plan'
    ]);
    exit;
}

if ($action === 'get_reminders') {
    $rows = pdo_fetch_all(
        "SELECT mp.plan_id, mp.reminder_at, mp.planned_date, mp.meal_type,
                m.meal_name, m.meal_icon
         FROM meal_planning mp
         JOIN meals m ON mp.meal_id = m.meal_id
         WHERE mp.user_id = :user_id
           AND mp.reminder_at IS NOT NULL
           AND mp.reminder_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
         ORDER BY mp.reminder_at ASC",
        [':user_id' => $user_id]
    );

    echo json_encode(['success' => true, 'reminders' => $rows ?: []]);
    exit;
}

if ($action === 'shared_with_me') {
    $rows = pdo_fetch_all(
        "SELECT s.share_id, s.planned_date, s.can_edit, u.username AS owner_username
         FROM meal_plan_shares s
         JOIN users u ON s.owner_user_id = u.user_id
         WHERE s.target_user_id = :user_id
         ORDER BY s.planned_date ASC, u.username ASC",
        [':user_id' => $user_id]
    );

    echo json_encode(['success' => true, 'shared' => $rows ?: []]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
