<?php
session_start();
// If the user is already logged in, send them straight to the dashboard
if (isset($_SESSION['userId'])) {
    header("Location: views/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bug Tracker - Login</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body style="display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 100vh; background-color: var(--bg-color);">

    <div class="login-container">
        <h2>Bug Tracker Login</h2>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="error">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']); // Clear the error after showing it
                ?>
            </div>
        <?php endif; ?>

        <form action="controllers/login.php" method="POST">
            <div>
                <label style="font-weight: 500; font-size: 0.9rem; color: var(--text-muted);">Username</label>
                <input type="text" name="username" required autocomplete="off">
            </div>
            
            <div>
                <label style="font-weight: 500; font-size: 0.9rem; color: var(--text-muted);">Password</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit" class="btn" style="width: 100%; margin-top: 10px; font-size: 1rem;">Log In</button>
        </form>
    </div>

    <div style="margin-top: 20px; padding: 15px; border: 2px dashed #cbd5e1; background-color: #f8fafc; width: 100%; max-width: 380px; border-radius: 8px; box-sizing: border-box;">
        <h3 style="margin-top: 0; color: #334155; font-size: 1rem; text-align: center;">Test Accounts (For Grading)</h3>
        <ul style="list-style-type: none; padding: 0; margin: 0; font-size: 0.9rem; color: #475569;">
            <li style="margin-bottom: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                <span class="role-badge role-admin" style="margin-left: 0; float: right;">Role 1</span>
                <strong>Admin:</strong><br>
                User: <code>admin</code> | Pass: <code>adminpassword</code>
            </li>
            <li style="margin-bottom: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                <span class="role-badge role-manager" style="margin-left: 0; float: right;">Role 2</span>
                <strong>Manager:</strong><br>
                User: <code>manager</code> | Pass: <code>managerpassword</code>
            </li>
            <li>
                <span class="role-badge role-user" style="margin-left: 0; float: right;">Role 3</span>
                <strong>Regular User:</strong><br>
                User: <code>user1</code> | Pass: <code>user1password</code>
            </li>
        </ul>
    </div>

</body>
</html>