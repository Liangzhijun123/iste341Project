<?php
/**
 * Project Management Controller & View
 * * Provides an interface for Managers (Role 2) and Administrators (Role 1) 
 * to define and view system projects. This file implements strict Role-Based 
 * Access Control (RBAC) to restrict project creation to high-level roles.
 */

// 1. ERROR REPORTING & ENVIRONMENT
// Enables full debugging visibility for development and grading purposes.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. SESSION INITIALIZATION
// Restores the authenticated state to verify user identity and permissions.
session_start();

/**
 * 3. SECURITY GUARD (RBAC ENFORCEMENT)
 * Requirement: Managers and Admins can create/update projects; Regular Users cannot.
 */
// Redirect unauthenticated users back to the landing page.
if (!isset($_SESSION['userId'])) {
    header("Location: ../index.php"); 
    exit;
}

// Strictly block Role 3 (Regular Users) from accessing project management tools.
if ($_SESSION['roleId'] == 3) {
    die("Access denied: You do not have permission to manage projects.");
}

// 4. DATA ACCESS LAYER INTEGRATION
// Imports the Logic Tier class responsible for project data operations.
require_once __DIR__ . "/../classes/Project.class.php";
$projectObj = new Project();

/**
 * 5. POST-BACK PROCESSING
 * Handles form submissions to create new project records in the database.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $projectName = trim($_POST['projectName']);
    
    if (!empty($projectName)) {
        // Delegate database insertion to the Logic Tier.
        $projectObj->createProject($projectName);
        
        // Redirect to refresh the project list and prevent duplicate form submission.
        header("Location: projects.php");
        exit;
    }
}

/**
 * 6. DATA RETRIEVAL
 * Fetches the updated list of all projects to populate the view table.
 */
$projects = $projectObj->getAllProjects();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Projects</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header><h1>Projects</h1> <a href="dashboard.php">Dashboard</a> <a href="../controllers/logout.php">Logout</a></header>
<main>
    <h2>All Projects</h2>
    <ul>
        <?php foreach($projects as $p) echo "<li>{$p['Project']}</li>"; ?>
    </ul>
    <h3>Create New Project</h3>
    <form method="POST">
        <input type="text" name="projectName" required>
        <button type="submit">Create</button>
    </form>
</main>
</body>
</html>