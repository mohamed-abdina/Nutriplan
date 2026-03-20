<?php
// API endpoint for searching meals
header('Content-Type: application/json');
session_start();

require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$query = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';
$category = isset($_GET['cat']) ? sanitize_input($_GET['cat']) : '';
$nutrition = isset($_GET['nut']) ? sanitize_input($_GET['nut']) : '';

$sql = "SELECT m.meal_id, m.meal_name, m.meal_icon, c.category_name, c.category_id,
        n.calories, n.proteins_g, n.carbs_g, n.fats_g, n.fiber_
FROM meals m
JOIN categories c ON m.category_id = c.category_id
JOIN nutrition n ON m.meal_id = n.meal_id
WHERE 1=1";

if (!empty($query)) {
    $sql .= " AND (m.meal_name LIKE '%$query%' OR m.description LIKE '%$query%')";
}

if (!empty($category)) {
    $sql .= " AND c.category_id = '$category'";
}

$sql .= " ORDER BY m.meal_name ASC LIMIT 50";

$result = $conn->query($sql);
$meals = [];

while ($row = $result->fetch_assoc()) {
    $meals[] = $row;
}

echo json_encode([
    'success' => true,
    'count' => count($meals),
    'meals' => $meals
]);
?>
