<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['userId']) || $_SESSION['roleId'] != 1) die("Access denied");

require_once __DIR__ . "/../classes/User.class.php";
$userClass = new User();

// Handle deletion
if (isset($_GET['delete'])) {
    $userClass->deleteUser($_GET['delete']);
    header("Location: admin_users.php");
}

// Create user
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userClass->createUser($_POST['username'], $_POST['password'], $_POST['role'], $_POST['name'], $_POST['project'] ?? null);
    header("Location: admin_users.php");
}

$users = $userClass->getAllUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Users</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header><h1>Admin Users</h1> <a href="dashboard.php">Dashboard</a> <a href="../controllers/logout.php">Logout</a></header>
<main>
    <h2>All Users</h2>
    <table>
        <tr><th>ID</th><th>Username</th><th>Name</th><th>Role</th><th>Project</th><th>Action</th></tr>
        <?php foreach($users as $u) {
            echo "<tr>
                    <td>{$u['Id']}</td>
                    <td>{$u['Username']}</td>
                    <td>{$u['Name']}</td>
                    <td>{$u['RoleID']}</td>
                    <td>{$u['ProjectId']}</td>
                    <td><a href='?delete={$u['Id']}' onclick=\"return confirm('Delete user?')\">Delete</a></td>
                  </tr>";
        } ?>
    </table>

    <h3>Create New User</h3>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="text" name="name" placeholder="Full Name" required>
        <select name="role">
            <option value="1">Admin</option>
            <option value="2">Manager</option>
            <option value="3">User</option>
        </select>
        <input type="number" name="project" placeholder="Project ID (optional)">
        <button type="submit">Create User</button>
    </form>
</main>
</body>
</html>