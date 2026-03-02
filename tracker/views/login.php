<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (isset($_SESSION['userId'])) {
    header("Location: dashboard.php");
    exit;
}

// Show error if redirected from controller
$error = isset($_GET['error']) ? $_GET['error'] : '';
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