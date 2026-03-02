<?php
/**
 * Bug List View
 * * Provides a filterable interface for viewing bug reports. This file enforces 
 * strict Role-Based Access Control (RBAC) to ensure users only see data 
 * permitted by their role.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

/**
 * AUTHENTICATION GUARD
 * Prevents unauthenticated access to the bug repository.
 */
if (!isset($_SESSION['userId'])) {
    header("Location: ../index.php"); 
    exit; 
}

require_once __DIR__ . "/../classes/Bug.class.php";
require_once __DIR__ . "/../classes/Project.class.php";

$bug = new Bug();
$projectObj = new Project();

// Capture UI filter states from the URL
$statusFilter = $_GET['filter'] ?? 'all';
$projectFilter = $_GET['projectId'] ?? 'all'; 

$whereClauses = [];
$params = [];

/**
 * 1. PROJECT VISIBILITY LOGIC
 * Regular Users (Role 3) are restricted to their specific assigned project.
 * Admins (1) and Managers (2) possess global visibility.
 */
if ($_SESSION['roleId'] == 3) {
    $whereClauses[] = "projectId = ?";
    $params[] = $_SESSION['projectId'];
} else {
    if ($projectFilter != 'all') {
        $whereClauses[] = "projectId = ?";
        $params[] = $projectFilter;
    }
}

/**
 * 2. STATUS & TEMPORAL FILTERING
 * Filters results by 'Open' status or 'Overdue' criteria based on current date.
 */
if ($statusFilter == 'open') {
    $whereClauses[] = "statusId != 3"; 
} elseif ($statusFilter == 'overdue') {
    $whereClauses[] = "targetDate < NOW() AND statusId != 3";
}

// Construct Dynamic Prepared Statement
$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = "WHERE " . implode(" AND ", $whereClauses);
}

/**
 * 3. SORTING REQUIREMENTS
 * Admins and Managers receive bugs sorted by Project ID for better organization.
 */
if ($_SESSION['roleId'] == 1 || $_SESSION['roleId'] == 2) {
    $whereSql .= " ORDER BY projectId ASC";
}

// Execute Data Retrieval via the Data Access Layer
$sql = "SELECT * FROM bugs $whereSql";
$bugs = $bug->db->query($sql, $params);

// Fetch projects for administrative filtering dropdown
$projects = [];
if ($_SESSION['roleId'] == 1 || $_SESSION['roleId'] == 2) {
    $projects = $projectObj->getAllProjects();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bug Repository</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .filter-form { background: #f4f4f4; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .filter-form select, .filter-form button { padding: 5px; margin-right: 15px; }
    </style>
</head>
<body>
<header>
    <h1>Bug Repository</h1> 
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a> | 
        <a href="../controllers/logout.php">Logout</a>
    </div>
</header>
<main>
    <h2>Filtered Bug List</h2>
    
    <div class="filter-form">
        <form method="GET" action="">
            <label for="filter">Status:</label>
            <select name="filter" id="filter">
                <option value="all" <?php if($statusFilter == 'all') echo 'selected'; ?>>All Bugs</option>
                <option value="open" <?php if($statusFilter == 'open') echo 'selected'; ?>>Open Bugs</option>
                <option value="overdue" <?php if($statusFilter == 'overdue') echo 'selected'; ?>>Overdue Bugs</option>
            </select>

            <?php if ($_SESSION['roleId'] == 1 || $_SESSION['roleId'] == 2): ?>
                <label for="projectId">Project Scope:</label>
                <select name="projectId" id="projectId">
                    <option value="all" <?php if($projectFilter == 'all') echo 'selected'; ?>>All Projects</option>
                    <?php foreach($projects as $p): ?>
                        <option value="<?php echo $p['Id']; ?>" <?php if($projectFilter == $p['Id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($p['Project']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <button type="submit" class="btn">Apply Filters</button>
        </form>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Summary</th>
            <th>Status ID</th>
            <th>Priority ID</th>
            <th>Actions</th> 
        </tr>
        <?php if(empty($bugs)): ?>
            <tr><td colspan="5">No bug records match the current filter criteria.</td></tr>
        <?php else: ?>
            <?php foreach($bugs as $b): ?>
                <tr>
                    <td>#<?php echo $b['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($b['summary']); ?></strong></td>
                    <td><?php echo $b['statusId']; ?></td>
                    <td><?php echo $b['priorityId']; ?></td>
                    <td>
                        <a href="view_bug.php?id=<?php echo $b['id']; ?>">View Details</a> 

                        <?php 
                            /**
                             * 4. DYNAMIC ACTION PERMISSIONS
                             * Determines if the 'Edit' action should be exposed based on 
                             * ownership or administrative role.
                             */
                            $canEdit = false;
                            if ($_SESSION['roleId'] == 1 || $_SESSION['roleId'] == 2) {
                                $canEdit = true;
                            } elseif ($_SESSION['roleId'] == 3 && $b['assignedToId'] == $_SESSION['userId']) {
                                $canEdit = true;
                            }
                            
                            if ($canEdit): ?>
                                | <a href="edit_bug.php?id=<?php echo $b['id']; ?>">Edit Bug</a>
                            <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</main>
</body>
</html>