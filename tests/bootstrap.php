<?php
// Test bootstrap: load composer autoload if available, then include needed files
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// include application helpers
require_once __DIR__ . '/../includes/functions.php';
