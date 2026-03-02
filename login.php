<?php
/**
 * Dashboard Entry Controller
 * * This script manages the initial state of the dashboard view. 
 * It acts as a "Reverse Auth Guard"—ensuring that already-authenticated 
 * users are funneled directly into the application hub rather than 
 * being presented with redundant login or landing page content.
 */

// 1. SESSION INITIALIZATION
// Resumes the current session to access role and identity tokens.
session_start();

/**
 * 2. AUTHENTICATION REDIRECT (UX Optimization)
 * * If the 'userId' key is present in the $_SESSION superglobal, 
 * the user has already successfully cleared the login controller. 
 * To provide a seamless experience, we perform an immediate server-side 
 * redirect to the protected dashboard area.
 */
if (isset($_SESSION['userId'])) {
    // Redirecting to the primary view within the Presentation Tier
    header("Location: dashboard.php");
    exit;
}

/**
 * DESIGN NOTE:
 * This pattern prevents "Session Looping" and ensures that the 
 * browser's "Back" button behavior is handled gracefully within 
 * the application lifecycle.
 */
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
</body>
</html>