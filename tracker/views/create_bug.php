<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['userId'])) {
    header("Location: ../index.php"); 
    exit; 
}

require_once __DIR__ . "/../classes/Bug.class.php";
require_once __DIR__ . "/../classes/Project.class.php";

$bug = new Bug();
$projectObj = new Project();

// Everyone needs the list of projects for the dropdown
$projects = $projectObj->getAllProjects();
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $summary = $_POST['summary'];
    $description = $_POST['description'];
    $projectId = $_POST['projectId'];
    $priorityId = $_POST['priorityId'];
    
    // The currently logged in user is the owner
    $ownerId = $_SESSION['userId']; 

    // Insert directly into MySQL (Status 1 = Open by default)
    $sql = "INSERT INTO bugs (summary, description, projectId, priorityId, statusId, ownerId, dateRaised) 
            VALUES (?, ?, ?, ?, 1, ?, NOW())";
            
    // Tell the Bug class to do the heavy lifting!
    $bug->createBug($summary, $description, $projectId, $priorityId, $ownerId);
    
    $message = "<div style='color: green; margin-bottom: 15px;'>Bug successfully reported! <a href='dashboard.php'>Return to Dashboard</a></div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Bug</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header>
    <h1>Report a New Bug</h1>
    <div class="nav-links">
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</header>
<main>
    <div class="card" style="max-width: 600px; margin: 0 auto;">
        <h2>Bug Details</h2>
        <?php echo $message; ?>
        
        <form method="POST" action="">
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Summary (Short Title):</label>
                <input type="text" name="summary" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Description:</label>
                <textarea name="description" required rows="5" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
            </div>

            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">Project:</label>
                    <select name="projectId" required style="width: 100%; padding: 8px;">
                        <?php foreach($projects as $p): ?>
                            <option value="<?php echo $p['Id']; ?>"><?php echo htmlspecialchars($p['Project']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="flex: 1;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">Priority:</label>
                    <select name="priorityId" required style="width: 100%; padding: 8px;">
                        <option value="1">1 - Low</option>
                        <option value="2">2 - Medium</option>
                        <option value="3">3 - High</option>
                        <option value="4">4 - Critical</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-danger">Submit Bug</button>
        </form>
    </div>
</main>
</body>
</html>