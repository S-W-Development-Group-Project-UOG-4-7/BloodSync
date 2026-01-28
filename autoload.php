<?php
// autoload.php - Simple autoloader for BloodSync

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path
define('BASE_PATH', __DIR__);

// Load configuration
require_once BASE_PATH . '/config/database.php';

// Simple autoload function
spl_autoload_register(function ($class_name) {
    // Try to find the class in different directories
    $directories = [
        BASE_PATH . '/core/',
        BASE_PATH . '/classes/',
        BASE_PATH . '/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load the Auth class directly (since it's in the root)
if (file_exists(BASE_PATH . '/Auth.php')) {
    require_once BASE_PATH . '/Auth.php';
}

// Load other core files
if (file_exists(BASE_PATH . '/config/api_keys.php')) {
    require_once BASE_PATH . '/config/api_keys.php';
}
?>