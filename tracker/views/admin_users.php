<?php
/**
 * Admin User Management View
 * * Provides an interface for System Administrators (Role 1) to perform CRUD 
 * operations on user accounts. Enforces strict server-side role validation.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

/**
 * ACCESS CONTROL:
 * Verifies the user is authenticated and possesses Administrator privileges (Role 1).
 */
if (!isset($_SESSION['userId']) || $_SESSION['roleId'] != 1) {
    die("Access denied: Administrative privileges required.");
}

require_once __DIR__ . "/../classes/User.class.php";
$userClass = new User();

/**
 * ACTION: DELETE USER
 * Triggers the safe deletion process defined in the User class.
 */
if (isset($_GET['delete'])) {
    $userClass->deleteUser($_GET['delete']);
    header("Location: admin_users.php");
    exit;
}

/**
 * ACTION: CREATE USER
 * Processes POST data to register a new user with a secure password hash.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Passes form data to the User class for validation and database insertion
    $userClass->createUser(
        $_POST['username'], 
        $_POST['password'], 
        $_POST['role'], 
        $_POST['name'], 
        $_POST['project'] ?? null
    );
    header("Location: admin_users.php");
    exit;
}

// Fetch the full dataset for the user management table
$users = $userClass->getAllUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - User Management</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header>
    <div style="display: flex; align-items: center;">
        <h1>User Management</h1>
        <span class="role-badge role-admin">System Admin</span>
    </div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="../controllers/logout.php">Logout</a>
    </div>
</header>

<main>
    <div class="card">
        <h2>System Users</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Name</th>
                <th>Role</th>
                <th>Project</th>
                <th>Action</th>
            </tr>
            <?php foreach($users as $u): ?>
                <tr>
                    <td><?php echo $u['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($u['Username']); ?></strong></td>
                    <td><?php echo htmlspecialchars($u['Name']); ?></td>
                    <td>
                        <?php 
                            // Convert Role IDs to readable labels for better UX
                            if($u['RoleID'] == 1) echo "Admin";
                            elseif($u['RoleID'] == 2) echo "Manager";
                            else echo "Regular User";
                        ?>
                    </td>
                    <td><?php echo $u['ProjectId'] ?? 'N/A'; ?></td>
                    <td>
                        <?php if ($u['id'] != $_SESSION['userId']): ?>
                            <a href="?delete=<?php echo $u['id']; ?>" 
                               class="btn btn-danger" 
                               style="padding: 5px 10px; font-size: 0.8rem;"
                               onclick="return confirm('Are you sure you want to delete this user? This will unassign them from all bugs.')">Delete</a>
                        <?php else: ?>
                            <span style="color: #999;">(Current)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h3>Create New User</h3>
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="text" name="name" placeholder="Full Name" required>
                
                <select name="role">
                    <option value="3">Regular User</option>
                    <option value="2">Manager</option>
                    <option value="1">Admin</option>
                </select>
            </div>
            
            <input type="number" name="project" placeholder="Project ID (Optional for Managers/Admins)">
            
            <button type="submit" class="btn" style="width: 100%;">Create User Account</button>
        </form>
    </div>
</main>
</body>
</html>