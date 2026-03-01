<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['userId'])) header("Location: login.php");
if ($_SESSION['roleId'] == 3) die("Access denied");

require_once __DIR__ . "/../classes/Project.class.php";
$project = new Project();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $project->createProject($_POST['projectName']);
    header("Location: projects.php");
}

$projects = $project->getAllProjects();
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