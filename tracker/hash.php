<?php
/**
 * Security Utility: Password Hash Generator
 * * This script is used during the development phase to generate BCRYPT hashes
 * for default system accounts (Admin, Manager, User).
 * * Requirement: All passwords must be stored as hashes using the password_hash function.
 */

// 1. ERROR REPORTING
// Essential for verifying that the PHP environment supports the PASSWORD_DEFAULT algorithm.
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Security Utility: Password Hashes</h2>";
echo "<p>Copy these values into your MySQL 'user_details' table 'Password' column.</p>";

/**
 * 2. ADMIN CREDENTIALS
 * Default: adminpassword
 */
echo "<strong>adminpassword</strong> hash:<br>";
// Uses password_hash() with the current industry-standard algorithm (BCRYPT).
echo password_hash("adminpassword", PASSWORD_DEFAULT);

echo "<br><br>";

/**
 * 3. MANAGER CREDENTIALS
 * Default: managerpassword
 */
echo "<strong>managerpassword</strong> hash:<br>";
echo password_hash("managerpassword", PASSWORD_DEFAULT);

echo "<br><br>";

/**
 * 4. REGULAR USER CREDENTIALS
 * Default: user1password
 */
echo "<strong>user1password</strong> hash:<br>";
echo password_hash("user1password", PASSWORD_DEFAULT);

/**
 * DESIGN NOTE:
 * These hashes are one-way. During login, the system will use password_verify() 
 * to compare these hashes against user input without ever storing 
 * the plain-text password in the database.
 */
?>