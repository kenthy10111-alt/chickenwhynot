<?php
/**
 * Database Configuration
 * kenth_eggs - CHICKEN WHY NOT? Egg Marketplace
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Change if you have a password set in WAMP
define('DB_NAME', 'kenth_eggs');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Error handling (set to 0 in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

?>
