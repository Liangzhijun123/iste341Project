<?php
/**
 * Application Entry Point - Login View
 * * This file serves as the landing page for the Bug Tracker system.
 * It handles initial session checks and provides the interface for 
 * user authentication.
 */

// 1. ERROR REPORTING CONFIGURATION
// Enables full error visibility during development to track session 
// or header issues immediately.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. SESSION MANAGEMENT
// Starts or resumes a session to check for existing authentication.
session_start();

/**
 * 3. AUTHENTICATION REDIRECT
 * If a 'userId' session variable exists, the user is already authenticated.
 * To improve UX and security, we automatically redirect them to the dashboard 
 * to prevent re-login attempts.
 */
if (isset($_SESSION['userId'])) {
    header("Location: dashboard.php");
    exit;
}

/**
 * 4. FEEDBACK MECHANISM
 * Captures error messages passed back from the Login Controller (login.php) 
 * via URL parameters to provide user-facing feedback.
 */
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bug Tracker Login</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="login-container">
    <h2>Login</h2>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form action="../controllers/login.php" method="POST">
        <label>Username:</label>
        <input type="text" name="username" required>
        <label>Password:</label>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>
</div>
<div style="margin-top: 30px; padding: 15px; border: 2px dashed #ccc; background-color: #f8f9fa; max-width: 350px; border-radius: 5px;">
    <h3 style="margin-top: 0; color: #333;">Test Accounts (For Grading)</h3>
    <ul style="list-style-type: none; padding: 0; margin: 0;">
        <li style="margin-bottom: 10px;">
            <strong>Admin (Role 1):</strong><br>
            Username: <code>admin</code><br>
            Password: <code>adminpassword</code>
        </li>
        <li style="margin-bottom: 10px;">
            <strong>Manager (Role 2):</strong><br>
            Username: <code>manager</code><br>
            Password: <code>managerpassword</code>
        </li>
        <li>
            <strong>Regular User (Role 3):</strong><br>
            Username: <code>user1</code><br>
            Password: <code>user1password</code>
        </li>
    </ul>
</div>
</body>
</html>