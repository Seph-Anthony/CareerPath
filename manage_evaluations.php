<?php
// manage_evaluations.php - Coordinator view of submitted company evaluations

session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: signincoordinator.html"); 
    exit;
}

$evaluations = [];
$message = "";
$error = "";

// --- Message Handling from URL ---
if (isset($_GET['status']) && isset($_GET['msg'])) {
    $status = $_GET['status'];
    $msg = htmlspecialchars($_GET['msg']);
    
    if ($status === 'success') {
        $message = $msg;
    } elseif ($status === 'error') {
        $error = $msg;
    }
}

// 2. Fetch all completed evaluations
$query = "
    SELECT 
        e.evaluation_id,
        e.score,
        e.submitted_at,
        s.student_id,
        s.first_name, 
        s.last_name,
        c.company_name,
        ia.status AS application_status,
        ia.application_id
    FROM 
        evaluation e
    JOIN 
        student s ON e.student_id = s.student_id
    JOIN 
        company c ON e.company_id = c.company_id
    JOIN
        intern_application ia ON e.application_id = ia.application_id
    ORDER BY 
        e.submitted_at DESC
";

$result = $mysqli->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Format the date
        $row['submitted_at_formatted'] = date('M d, Y h:i A', strtotime($row['submitted_at']));
        // Determine if the final status has been set by the coordinator
        $row['final_status'] = ($row['application_status'] == 'Passed' || $row['application_status'] == 'Failed') ? $row['application_status'] : 'Pending Review';
        $evaluations[] = $row;
    }
} else {
    $error = "Database query failed: " . $mysqli->error;
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Evaluations | Coordinator Dashboard</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="coordinatordashboard.css"> 
    <style>
        .dashboard-body { padding: 30px; }
        .section-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .data-table th {
            background-color: rgb(32, 64, 227);
            color: white;
            font-size: 14px;
            text-transform: uppercase;
        }
        .data-table tr:hover {
            background-color: #f7f9ff;
        }
        .btn-review {
            background-color: #f39c12;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn-review:hover {
            background-color: #e67e22;
        }
        /* Status Tags for Coordinator Review */
        .status-tag {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-passed { background-color: #d4edda; color: #155724; }
        .status-failed { background-color: #f8d7da; color: #721c24; }
        .status-pending-review { background-color: #fff3cd; color: #856404; }
        /* Alert Styles */
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .alert.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<div class="dashboard-container">

    <aside class="sidebar">
        <div class="logo"><h2>Career Path</h2></div>
        <nav class="menu">
            <a href="coordinatordashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Manage Students</a>
            <a href="manage_applications.php"><i class="fa-solid fa-clipboard-check"></i> Review Applications</a> 
            <a href="manage_companies.php"><i class="fa-solid fa-building"></i> Manage Companies</a>
            <a href="manage_placements.php"><i class="fa-solid fa-clipboard-list"></i> Placements/OJT</a>
            <a href="manage_evaluations.php" class="active"><i class="fa-solid fa-clipboard-check"></i> Review Evaluations</a> 
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <h1>OJT Evaluation Reports</h1>
            <p>Review final performance evaluations submitted by companies.</p>
        </header>

        <div class="dashboard-body">

            <?php if ($message): ?>
                <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?php echo $message; ?></div>
            <?php elseif ($error): ?>
                <div class="alert error"><i class="fa-solid fa-circle-xmark"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <section class="section-container">
                <h2><i class="fa-solid fa-list-ul"></i> Submitted Evaluations</h2>

                <?php if (empty($evaluations)): ?>
                    <p>No final student evaluations have been submitted by companies yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Student Name</th>
                                <th>Company</th>
                                <th>Score (1-5)</th>
                                <th>Date Submitted</th>
                                <th>Final Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evaluations as $eval): ?>
                            <tr>
                                <td><?php echo $eval['evaluation_id']; ?></td>
                                <td><?php echo htmlspecialchars($eval['first_name'] . ' ' . $eval['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($eval['company_name']); ?></td>
                                <td><strong style="color: #2c3e50;"><?php echo $eval['score']; ?></strong></td>
                                <td><?php echo $eval['submitted_at_formatted']; ?></td>
                                <td>
                                    <span class="status-tag status-<?php echo strtolower(str_replace(' ', '-', $eval['final_status'])); ?>">
                                        <?php echo htmlspecialchars($eval['final_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_evaluation_coordinator.php?evaluation_id=<?php echo $eval['evaluation_id']; ?>" class="btn-review">
                                        <i class="fa-solid fa-search"></i> Review
                                    </a>
                                </td>
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