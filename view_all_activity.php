<?php
// view_all_activity.php - Displays the full activity log for the coordinator
session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: signincoordinator.html"); 
    exit;
}

// Get the user_id and username from the session
$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);

// --- 2. Fetch Coordinator's Profile Details for Sidebar ---
$coordinator_name = $username; 
$coordinator_position = 'Coordinator'; 
$coordinator_department = 'N/A';

$stmt = $mysqli->prepare("
    SELECT full_name, department, position 
    FROM coordinator 
    WHERE user_id = ? 
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $coordinator_name = htmlspecialchars($data['full_name']);
        $coordinator_position = htmlspecialchars($data['position']);
        $coordinator_department = htmlspecialchars($data['department']);
    }
    $stmt->close();
}


// --- 3. Fetch All Activity Logs ---
$all_activities = [];
// Assuming the table is named 'coordinator_log' based on the dashboard file
$query = "SELECT description, created_at FROM coordinator_log ORDER BY created_at DESC";
$result = $mysqli->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_activities[] = $row;
    }
} else {
    // Handle database error
    error_log("Failed to fetch activity logs: " . $mysqli->error);
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Activity Log | Career Path</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="coordinatordashboard.css"> 
    <style>
        /* Specific styles for the activity log table */
        .activity-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .activity-table th, .activity-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .activity-table th {
            background-color: rgb(32, 64, 227);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
        }

        .activity-table tbody tr:hover {
            background-color: #f5f5f5;
        }

        .no-activity {
            text-align: center;
            padding: 40px;
            color: #777;
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <aside class="sidebar">
        <div class="logo">
            <h2>Career Path</h2>
        </div>

        <nav class="menu">
            <a href="coordinatordashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Manage Students</a>
            <a href="manage_applications.php"><i class="fa-solid fa-clipboard-check"></i> Review Applications</a> 
            <a href="manage_companies.php"><i class="fa-solid fa-building"></i> Manage Companies</a>
            <a href="manage_placements.php"><i class="fa-solid fa-clipboard-list"></i> Placements/OJT</a>
            <a href="manage_evaluations.php"><i class="fa-solid fa-clipboard-check"></i> Review Evaluations</a> 
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <h1>Activity Log</h1>
            <p>Full history of all system activities in the OJT Management platform.</p>
        </header>

        <div class="dashboard-body">

            <section class="section-container">
                <h2><i class="fa-solid fa-clock-rotate-left"></i> Comprehensive Activity History</h2>
                
                <?php if (empty($all_activities)): ?>
                    <div class="no-activity">
                        No activities have been recorded yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th style="width: 75%;">Activity Description</th>
                                    <th style="width: 25%;">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_activities as $activity): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                    <td><?php echo date('M d, Y \a\t H:i A', strtotime($activity['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>

</body>
</html>
```eof