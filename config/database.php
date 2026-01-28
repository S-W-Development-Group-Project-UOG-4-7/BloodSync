<?php
// PostgreSQL Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'bloodsync');
define('DB_USER', 'postgres');
define('DB_PASS', '8818');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
