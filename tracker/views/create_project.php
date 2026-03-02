<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// SECURITY CHECK: Kick out unlogged users AND Regular Users (Role 3)
if (!isset($_SESSION['userId']) || $_SESSION['roleId'] == 3) {
    header("Location: dashboard.php"); 
    exit; 
}

require_once __DIR__ . "/../classes/Project.class.php";
$project = new Project();

$message = "";

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $projectName = trim($_POST['projectName']);
    
    if (!empty($projectName)) {
        // We saw this function inside your Project.class.php earlier!
        $project->createProject($projectName);
        $message = "<div style='color: green; margin-bottom: 15px;'>Success! Project created.</div>";
    } else {
        $message = "<div style='color: red; margin-bottom: 15px;'>Project name cannot be empty.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Project</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header>
    <h1>Create New Project</h1>
    <div class="nav-links">
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</header>
<main>
    <div class="card" style="max-width: 500px; margin: 0 auto;">
        <h2>Project Details</h2>
        <?php echo $message; ?>
        
        <form method="POST" action="">
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Project Name:</label>
                <input type="text" name="projectName" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <button type="submit" class="btn">Save Project</button>
        </form>
    </div>
</main>
</body>
</html>