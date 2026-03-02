<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// 1. MUST BE LOGGED IN
if (!isset($_SESSION['userId'])) {
    header("Location: ../index.php"); 
    exit; 
}

require_once __DIR__ . "/../classes/Bug.class.php";
$bugObj = new Bug();

// 2. GRAB THE ID FROM THE URL
$bugId = $_GET['id'] ?? null;

if (!$bugId) {
    die("<h2 style='text-align: center; color: red; margin-top: 50px;'>Error: No Bug ID provided.</h2>");
}

// 3. FETCH THE BUG
$bug = $bugObj->getBugById($bugId);

if (!$bug) {
    die("<h2 style='text-align: center; color: red; margin-top: 50px;'>Error: Bug not found.</h2>");
}

// 4. SECURITY CHECK: Regular Users (Role 3) can ONLY view their own project's bugs!
if ($_SESSION['roleId'] == 3 && $bug['projectId'] != $_SESSION['projectId']) {
    // If they are snooping, kick them back to the dashboard silently
    header("Location: dashboard.php");
    exit;
}

// 5. PERMISSION TO EDIT CHECK
$canEdit = false;
if ($_SESSION['roleId'] == 1 || $_SESSION['roleId'] == 2) {
    $canEdit = true; // Admins and Managers can edit anything
} elseif ($_SESSION['roleId'] == 3 && $bug['assignedToId'] == $_SESSION['userId']) {
    $canEdit = true; // Users can only edit if specifically assigned to them
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Bug #<?php echo $bug['id']; ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
            background: #f8fafc;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid var(--border);
        }
        .detail-item {
            font-size: 0.95rem;
        }
        .detail-label {
            font-weight: bold;
            color: var(--text-muted);
            display: block;
            font-size: 0.8rem;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
<header>
    <h1>Bug Details</h1>
    <div class="nav-links">
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</header>
<main>
    <div class="card" style="max-width: 800px; margin: 0 auto;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--bg-color); padding-bottom: 15px; margin-bottom: 15px;">
            <h2 style="margin: 0; border: none; padding: 0;">#<?php echo $bug['id']; ?>: <?php echo htmlspecialchars($bug['summary']); ?></h2>
            
            <?php if($canEdit): ?>
                <a href="edit_bug.php?id=<?php echo $bug['id']; ?>" class="btn">Edit Bug</a>
            <?php endif; ?>
        </div>

        <div class="details-grid">
            <div class="detail-item"><span class="detail-label">Project ID</span> <?php echo $bug['projectId']; ?></div>
            <div class="detail-item"><span class="detail-label">Status ID</span> <span style="background: #e2e8f0; padding: 2px 8px; border-radius: 12px;"><?php echo $bug['statusId']; ?></span></div>
            <div class="detail-item"><span class="detail-label">Priority ID</span> <?php echo $bug['priorityId']; ?></div>
            <div class="detail-item"><span class="detail-label">Reported By (User ID)</span> <?php echo $bug['ownerId']; ?></div>
            <div class="detail-item"><span class="detail-label">Assigned To (User ID)</span> <?php echo $bug['assignedToId'] ? $bug['assignedToId'] : '<em>Unassigned</em>'; ?></div>
            <div class="detail-item"><span class="detail-label">Date Raised</span> <?php echo $bug['dateRaised']; ?></div>
        </div>

        <div style="margin-bottom: 20px;">
            <span class="detail-label" style="margin-bottom: 5px;">Full Description</span>
            <div style="background: white; padding: 15px; border: 1px solid var(--border); border-radius: 4px; min-height: 80px;">
                <?php echo nl2br(htmlspecialchars($bug['description'])); ?>
            </div>
        </div>

        <?php if (!empty($bug['fixDescription'])): ?>
            <div style="margin-bottom: 20px;">
                <span class="detail-label" style="margin-bottom: 5px; color: #16a34a;">Fix Description</span>
                <div style="background: #f0fdf4; padding: 15px; border: 1px solid #bbf7d0; border-radius: 4px; min-height: 50px;">
                    <?php echo nl2br(htmlspecialchars($bug['fixDescription'])); ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</main>
</body>
</html>