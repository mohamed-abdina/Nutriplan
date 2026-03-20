<?php
// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'meal_planning_db';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Function to sanitize input
function sanitize_input($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

// Function to execute query
function execute_query($query) {
    global $conn;
    $result = $conn->query($query);
    if (!$result) {
        error_log("Query Error: " . $conn->error);
        return false;
    }
    return $result;
}
?>
