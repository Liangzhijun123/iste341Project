<?php
/**
 * Database Configuration Utility
 * * This script centralizes the application's connection parameters. 
 * By using $_SERVER environment variables, the system follows the "12-Factor App" 
 * methodology, allowing for seamless transitions between development, 
 * testing, and production environments without changing code.
 */

// --- Environment Variable Initialization ---
// These checks ensure the application has fallback values for local development
// while allowing the server's environment to override them for security.

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
    // Note: In a production environment, this should never be hardcoded.
    $_SERVER['DB_PASSWORD'] = 'YOUR_PASSWORD';
}

// --- Global Constants ---
// Providing constants offers a secondary, immutable way for legacy 
// scripts or external libraries to access connection details.

/** @var string The hostname of the database server */
define('DB_HOST', $_SERVER['DB_SERVER']);

/** @var string The name of the MySQL database schema */
define('DB_NAME', $_SERVER['DB']);

/** @var string The database user with restricted permissions */
define('DB_USER', $_SERVER['DB_USER']);

/** @var string The secure password for the database user */
define('DB_PASSWORD', $_SERVER['DB_PASSWORD']);

/** @var string The character set used for the connection to ensure emoji/special character support */
define('DB_CHARSET', 'utf8mb4');