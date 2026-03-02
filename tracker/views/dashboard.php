<?php
/**
 * Main Dashboard Controller & View
 * * This file serves as the primary landing page for authenticated users.
 * It coordinates with the Bug and Project models to display role-specific 
 * data and administrative actions.
 */

// 1. ENVIRONMENT & SESSION INITIALIZATION
// Enables high-visibility error reporting for development and debugging.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Resumes the user's session to access identification and role data.
session_start();

/**
 * 2. AUTHENTICATION GUARD
 * Ensures that only logged-in users can access the dashboard. 
 * Redirects guests back to the landing page.
 */
if (!isset($_SESSION['userId'])) {
    header("Location: ../index.php"); 
    exit; 
}

// 3. DATA ACCESS LAYER INTEGRATION
// Imports the Logic Tier classes required for data retrieval.
require_once __DIR__ . "/../classes/Bug.class.php";
require_once __DIR__ . "/../classes/Project.class.php";

$bug = new Bug();
$project = new Project();

/**
 * 4. ROLE-BASED DATA RETRIEVAL (RBAC)
 * Requirement: Regular Users see only their assigned project's bugs.
 * Managers and Admins possess global visibility across all projects.
 */
if ($_SESSION['roleId'] == 3) { 
    // Data limited to the user's specific ProjectId stored in the session.
    $bugs = $bug->getBugsByProject($_SESSION['projectId']);
} else { 
    // Global data retrieval for administrative roles.
    $bugs = $bug->getAllBugs();
}

// Universal data needed for dashboard navigation/reference.
$projects = $project->getAllProjects();

/**
 * 5. UI DYNAMIC CONFIGURATION
 * Determines the visual theme and labels based on the user's RoleID.
 */
$roleName = "Regular User";
$badgeClass = "role-user";

if ($_SESSION['roleId'] == 1) {
    $roleName = "Administrator";
    $badgeClass = "role-admin"; // High-visibility red badge
} elseif ($_SESSION['roleId'] == 2) {
    $roleName = "Manager";
    $badgeClass = "role-manager"; // Purple badge
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bug Tracker Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css"> 
</head>
<body>

<header>
    <div style="display: flex; align-items: center;">
        <h1>Bug Tracker</h1>
        <span class="role-badge <?php echo $badgeClass; ?>"><?php echo $roleName; ?></span>
    </div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="../controllers/logout.php">Logout</a>
    </div>
</header>

<main>
    <div class="action-bar">
        <a href="create_bug.php" class="btn">+ Report New Bug</a>
        
        <?php if ($_SESSION['roleId'] == 1 || $_SESSION['roleId'] == 2): ?>
            <a href="create_project.php" class="btn btn-secondary">+ Create Project</a>
        <?php endif; ?>

        <?php if ($_SESSION['roleId'] == 1): ?>
            <a href="manage_users.php" class="btn btn-danger">Manage Users</a>
        <?php endif; ?>
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <h2>Your Projects</h2>
            <?php if(empty($projects)): ?>
                <p style="color: #666;">No projects found.</p>
            <?php else: ?>
                <ul>
                    <?php foreach($projects as $p) echo "<li><strong>{$p['Project']}</strong></li>"; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Recent Bugs</h2>
                <a href="bugs.php" class="btn btn-secondary" style="font-size: 0.8rem;">View All / Filter</a>
            </div>
            
            <table>
                <tr>
                    <th>ID</th>
                    <th>Summary</th>
                    <th>Status ID</th>
                    <th>Priority ID</th>
                    <th>Action</th>
                </tr>
                <?php if(empty($bugs)): ?>
                    <tr><td colspan="5">No bugs currently assigned to this view.</td></tr>
                <?php else: ?>
                    <?php foreach($bugs as $b): ?>
                        <tr>
                            <td>#<?php echo $b['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($b['summary']); ?></strong></td>
                            <td><span style="background: #eee; padding: 3px 8px; border-radius: 12px; font-size: 0.8rem;"><?php echo $b['statusId']; ?></span></td>
                            <td><?php echo $b['priorityId']; ?></td>
                            <td><a href="view_bug.php?id=<?php echo $b['id']; ?>">View Details</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>
</main>

</body>
</html>