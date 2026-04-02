<?php
// Test bootstrap: load composer autoload if available, then include needed files
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Include database connection (must be before functions)
require_once __DIR__ . '/../includes/db_connect.php';

// include application helpers
require_once __DIR__ . '/../includes/functions.php';
