<?php
/**
 * Login Controller
 * * Handles the authentication process by verifying user credentials against 
 * stored hashes and initializing the session for Role-Based Access Control (RBAC).
 */
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../classes/User.class.php";

// Only process the request if it is a POST submission from the login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $user = new User();
    
    /**
     * The User->login() method performs the database lookup and 
     * utilizes password_verify() to validate the hashed password.
     */
    $data = $user->login($username, $password);
    
    if ($data) {
        /**
         * AUTHENTICATION SUCCESS
         * Initialize session variables to persist user identity and 
         * permissions across the application.
         */
        $_SESSION["userId"] = $data["Id"];
        $_SESSION["roleId"] = $data["RoleID"];
        
        // ProjectId is crucial for Role 3 (Regular Users) to restrict bug visibility
        $_SESSION["projectId"] = $data["ProjectId"];
        
        // Redirect to the dashboard hub
        header("Location: ../views/dashboard.php");
        exit; 
    } else {
        /**
         * AUTHENTICATION FAILURE
         * Store a generic error message in the session to be displayed on index.php.
         * We use generic messages to prevent 'Username Enumeration' security risks.
         */
        $_SESSION["error"] = "Invalid username or password.";
        header("Location: ../index.php");
        exit;
    }
} else {
    // If someone tries to access this script directly, send them back to login
    header("Location: ../index.php");
    exit;
}
?>