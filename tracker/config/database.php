<?php
/**
 * Database Configuration
 * 
 * This file contains database connection credentials.
 * The Database class reads these values from environment variables.
 * 
 * For production, set these as actual environment variables.
 * For development, this file sets them if not already defined.
 */

// Set database configuration as environment variables if not already set
if (!isset($_SERVER['DB_SERVER'])) {
    $_SERVER['DB_SERVER'] = 'localhost';
}

if (!isset($_SERVER['DB'])) {
    $_SERVER['DB'] = 'zl5660';
}

if (!isset($_SERVER['DB_USER'])) {
    $_SERVER['DB_USER'] = 'zl5660';
}

if (!isset($_SERVER['DB_PASSWORD'])) {
    $_SERVER['DB_PASSWORD'] = 'YOUR_PASSWORD';
}

// Database configuration constants (alternative approach)
define('DB_HOST', $_SERVER['DB_SERVER']);
define('DB_NAME', $_SERVER['DB']);
define('DB_USER', $_SERVER['DB_USER']);
define('DB_PASSWORD', $_SERVER['DB_PASSWORD']);
define('DB_CHARSET', 'utf8mb4');
