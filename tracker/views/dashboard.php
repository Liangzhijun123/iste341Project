<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['userId'])) header("Location: login.php");

require_once __DIR__ . "/../classes/Bug.class.php";
require_once __DIR__ . "/../classes/Project.class.php";

$bug = new Bug();
$project = new Project();

// Role-based bug summary
if ($_SESSION['roleId'] == 3) { // User
    $bugs = $bug->getBugsByProject($_SESSION['projectId']);
} else { // Manager/Admin
    $bugs = $bug->getAllBugs();
}

$projects = $project->getAllProjects();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header>
    <h1>Dashboard</h1>
    <a href="../controllers/logout.php">Logout</a>
</header>
<main>
    <h2>Projects</h2>
    <ul>
        <?php foreach($projects as $p) echo "<li>{$p['Project']}</li>"; ?>
    </ul>

    <h2>Bugs</h2>
    <table>
        <tr><th>ID</th><th>Summary</th><th>Status</th><th>Priority</th></tr>
        <?php foreach($bugs as $b) {
            echo "<tr>
                    <td>{$b['id']}</td>
                    <td>{$b['summary']}</td>
                    <td>{$b['statusId']}</td>
                    <td>{$b['priorityId']}</td>
                  </tr>";
        } ?>
    </table>
</main>
</body>
</html>