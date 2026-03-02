<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['userId'])) {
    header("Location: ../index.php"); 
    exit; 
}

require_once __DIR__ . "/../classes/Bug.class.php";
require_once __DIR__ . "/../classes/Project.class.php"; // Needed for the project dropdown

$bug = new Bug();
$projectObj = new Project();

// 1. Get current filters from the URL (defaulting to 'all')
$statusFilter = $_GET['filter'] ?? 'all';
$projectFilter = $_GET['projectId'] ?? 'all'; 

$whereClauses = [];
$params = [];

// 2. PROJECT VISIBILITY RULES
if ($_SESSION['roleId'] == 3) {
    // Regular Users are strictly locked to their assigned project
    $whereClauses[] = "projectId = ?";
    $params[] = $_SESSION['projectId'];
} else {
    // Admins/Managers can see all, OR filter by a specific project from the dropdown
    if ($projectFilter != 'all') {
        $whereClauses[] = "projectId = ?";
        $params[] = $projectFilter;
    }
}

// 3. STATUS RULES (Open or Overdue)
// Assuming statusId 3 represents "Closed/Resolved". Change if your database uses a different ID!
if ($statusFilter == 'open') {
    $whereClauses[] = "statusId != 3"; 
} elseif ($statusFilter == 'overdue') {
    $whereClauses[] = "targetDate < NOW() AND statusId != 3";
}

// Build the final SQL string
$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = "WHERE " . implode(" AND ", $whereClauses);
}

// 4. SORTING RULE
// "Each of these filters can be for all bugs sorted by project"
if ($_SESSION['roleId'] == 1 || $_SESSION['roleId'] == 2) {
    $whereSql .= " ORDER BY projectId ASC";
}

// Run the query!
$sql = "SELECT * FROM bugs $whereSql";
$bugs = $bug->db->query($sql, $params);

// Get projects for the dropdown (Only needed for Admin/Manager)
$projects = [];
if ($_SESSION['roleId'] == 1 || $_SESSION['roleId'] == 2) {
    $projects = $projectObj->getAllProjects();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bugs</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .filter-form { background: #f4f4f4; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .filter-form select, .filter-form button { padding: 5px; margin-right: 15px; }
    </style>
</head>
<body>
<header>
    <h1>Bugs</h1> 
    <a href="dashboard.php">Dashboard</a> | 
    <a href="../controllers/logout.php">Logout</a>
</header>
<main>
    <h2>Bug List</h2>
    
    <div class="filter-form">
        <form method="GET" action="">
            <label for="filter">Status:</label>
            <select name="filter" id="filter">
                <option value="all" <?php if($statusFilter == 'all') echo 'selected'; ?>>All Bugs</option>
                <option value="open" <?php if($statusFilter == 'open') echo 'selected'; ?>>Open Bugs</option>
                <option value="overdue" <?php if($statusFilter == 'overdue') echo 'selected'; ?>>Overdue Bugs</option>
            </select>

            <?php if ($_SESSION['roleId'] == 1 || $_SESSION['roleId'] == 2): ?>
                <label for="projectId">Project:</label>
                <select name="projectId" id="projectId">
                    <option value="all" <?php if($projectFilter == 'all') echo 'selected'; ?>>All Projects</option>
                    <?php foreach($projects as $p): ?>
                        <option value="<?php echo $p['Id']; ?>" <?php if($projectFilter == $p['Id']) echo 'selected'; ?>>
                            <?php echo $p['Project']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <button type="submit">Apply Filters</button>
        </form>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Summary</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Actions</th> </tr>
        <?php if(empty($bugs)): ?>
            <tr><td colspan="5">No bugs found matching these filters.</td></tr>
        <?php else: ?>
            <?php foreach($bugs as $b): ?>
                <tr>
                    <td><?php echo $b['id']; ?></td>
                    <td><?php echo $b['summary']; ?></td>
                    <td><?php echo $b['statusId']; ?></td>
                    <td><?php echo $b['priorityId']; ?></td>
                    <td>
                        <a href="view_bug.php?id=<?php echo $b['id']; ?>">View</a> 

                        <?php 
                            // 5. UPDATE PERMISSIONS RULE
                            $canEdit = false;
                            
                            // Admins and Managers can edit anything
                            if ($_SESSION['roleId'] == 1 || $_SESSION['roleId'] == 2) {
                                $canEdit = true;
                            } 
                            // Regular Users can ONLY edit if they are the assigned person
                            elseif ($_SESSION['roleId'] == 3 && $b['assignedToId'] == $_SESSION['userId']) {
                                $canEdit = true;
                            }
                            
                            // Draw the Edit link only if they have permission
                            if ($canEdit): 
                        ?>
                            | <a href="edit_bug.php?id=<?php echo $b['id']; ?>">Edit</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</main>
</body>
</html>