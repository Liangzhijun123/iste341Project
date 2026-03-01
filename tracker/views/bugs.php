<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['userId'])) header("Location: login.php");

require_once __DIR__ . "/../classes/Bug.class.php";
$bug = new Bug();

// Filter
$filter = $_GET['filter'] ?? 'all';

$where = '';
$params = [];

if ($_SESSION['roleId'] == 3) {
    $where .= "WHERE projectId = ?";
    $params[] = $_SESSION['projectId'];
}

if ($filter == 'open') {
    $where .= ($where ? ' AND ' : 'WHERE ') . "statusId != 3";
}
if ($filter == 'overdue') {
    $where .= ($where ? ' AND ' : 'WHERE ') . "targetDate < NOW() AND statusId != 3";
}

$sql = "SELECT * FROM bugs $where";
$bugs = $bug->db->query($sql, $params)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bugs</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header><h1>Bugs</h1> <a href="dashboard.php">Dashboard</a> <a href="../controllers/logout.php">Logout</a></header>
<main>
    <h2>Bug List</h2>
    <nav>
        <a href="?filter=all">All</a> | 
        <a href="?filter=open">Open</a> | 
        <a href="?filter=overdue">Overdue</a>
    </nav>
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