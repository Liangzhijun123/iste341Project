<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . "/../classes/User.class.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $user = new User();
    $data = $user->login($username, $password);

    echo "<h3>Debug Information</h3>";
    echo "Username typed: " . htmlspecialchars($username) . "<br>";
    echo "Password typed: " . htmlspecialchars($password) . "<br>";
    
    if ($data) {
        echo "<strong style='color:green;'>LOGIN SUCCESS!</strong><br>";
        echo "User ID found: " . $data['Id'] . "<br>";
        echo "Role ID found: " . $data['RoleID'] . "<br>";
        
        // Let's set the session to see if it holds
        $_SESSION["userId"] = $data["Id"];
        $_SESSION["roleId"] = $data["RoleID"];
        $_SESSION["projectId"] = $data["ProjectId"];
        
        echo "<br><a href='../views/dashboard.php'>Click here to manually go to Dashboard</a>";
        exit; // Stops the automatic redirect
    } else {
        echo "<strong style='color:red;'>LOGIN FAILED!</strong><br>";
        exit; // Stops the automatic redirect
    }
}
?>