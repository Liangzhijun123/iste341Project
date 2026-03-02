<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// THE ADMIN BOUNCER: Kick out anyone who isn't Role 1 (Admin)
if (!isset($_SESSION['userId']) || $_SESSION['roleId'] != 1) {
    header("Location: dashboard.php"); 
    exit; 
}

require_once __DIR__ . "/../classes/User.class.php";
$userObj = new User();
$message = "";

// Handle Form Submissions (Add or Delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ACTION: ADD USER
    if (isset($_POST['action']) && $_POST['action'] == 'add_user') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $name = trim($_POST['name']);
        $roleId = $_POST['roleId'];
        
        if (!empty($username) && !empty($password) && !empty($name)) {
            $userObj->addUser($username, $password, $name, $roleId);
            $message = "<div style='color: green; margin-bottom: 15px;'>Success! New user added securely.</div>";
        } else {
            $message = "<div style='color: red; margin-bottom: 15px;'>Please fill in all fields.</div>";
        }
    }
    
    // ACTION: DELETE USER
    if (isset($_POST['action']) && $_POST['action'] == 'delete_user') {
        $deleteId = $_POST['delete_id'];
        
        // Prevent the Admin from accidentally deleting themselves!
        if ($deleteId == $_SESSION['userId']) {
            $message = "<div style='color: red; margin-bottom: 15px;'>Error: You cannot delete your own account!</div>";
        } else {
            $userObj->deleteUser($deleteId);
            $message = "<div style='color: green; margin-bottom: 15px;'>User successfully deleted and unassigned from all tasks.</div>";
        }
    }
}

// Fetch fresh list of users to display
$allUsers = $userObj->getAllUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header>
    <h1>User Management</h1>
    <div class="nav-links">
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</header>
<main>
    <?php echo $message; ?>

    <div class="dashboard-grid" style="grid-template-columns: 1fr 2fr;">
        
        <div class="card">
            <h2>Add New User</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_user">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">Full Name:</label>
                    <input type="text" name="name" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">Username:</label>
                    <input type="text" name="username" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">Password:</label>
                    <input type="password" name="password" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                    <small style="color: #666;">Will be securely hashed.</small>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">Role:</label>
                    <select name="roleId" style="width: 100%; padding: 8px; box-sizing: border-box;">
                        <option value="3">Regular User</option>
                        <option value="2">Manager</option>
                        <option value="1">Admin</option>
                    </select>
                </div>

                <button type="submit" class="btn" style="width: 100%;">Create User</button>
            </form>
        </div>

        <div class="card">
            <h2>System Users</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Role ID</th>
                    <th>Action</th>
                </tr>
                <?php foreach($allUsers as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($u['Name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['Username']); ?></td>
                        <td>
                            <?php 
                                if($u['RoleID'] == 1) echo "Admin";
                                elseif($u['RoleID'] == 2) echo "Manager";
                                else echo "User";
                            ?>
                        </td>
                        <td>
                            <?php if ($u['id'] != $_SESSION['userId']): ?>
                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this user? This cannot be undone.');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="delete_id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 0.8rem;">Delete</button>
                                </form>
                            <?php else: ?>
                                <span style="color: #999; font-size: 0.8rem;">(You)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

    </div>
</main>
</body>
</html>