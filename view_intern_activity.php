<?php
// view_intern_activity.php - Company view of a single intern's activity log

session_start();
require_once 'db_connect.php'; 

// 1. Authentication and Input Validation
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html"); 
    exit;
}

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
// application_id is included for context but not strictly needed for this log
$application_id = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0; 

if ($student_id === 0) {
    die("<script>alert('Invalid Intern ID.'); window.location.href='intern_progress.php';</script>");
}

$user_id = $_SESSION['user_id'];
$company_id = null;
$intern_details = null;
$activity_logs = [];

// 2. Fetch Company ID and Intern Details (with security check)
// This query ensures the company is only viewing an intern who applied to *one* of their posts AND is Hired.
$query_details = "
    SELECT 
        c.company_id,
        s.first_name, 
        s.last_name, 
        ip.title AS posting_title
    FROM 
        company c
    JOIN 
        users u ON c.user_id = u.user_id
    LEFT JOIN
        intern_application ia ON ia.student_id = ? AND ia.status = 'Hired'
    LEFT JOIN
        intern_posting ip ON ia.posting_id = ip.posting_id
    LEFT JOIN
        student s ON s.student_id = ?
    WHERE 
        c.user_id = ? AND ip.company_id = c.company_id
    LIMIT 1
";

$stmt_details = $mysqli->prepare($query_details);
if ($stmt_details) {
    $stmt_details->bind_param('iii', $student_id, $student_id, $user_id);
    $stmt_details->execute();
    $result_details = $stmt_details->get_result();
    
    if ($result_details->num_rows === 1) {
        $details_data = $result_details->fetch_assoc();
        $company_id = $details_data['company_id'];
        $intern_details = $details_data;
    }
    $stmt_details->close();
}

if (!$company_id || !$intern_details || empty($intern_details['first_name'])) {
    die("<script>alert('Error: Intern not found or does not belong to your company.'); window.location.href='intern_progress.php';</script>");
}

// 3. Fetch Activity Log Entries
$query_logs = "
    SELECT 
        activity_des,
        created_at
    FROM 
        `activity-log`
    WHERE
        student_id = ? AND company_id = ?
    ORDER BY
        created_at DESC
";

$stmt_logs = $mysqli->prepare($query_logs);
if ($stmt_logs) {
    $stmt_logs->bind_param('ii', $student_id, $company_id);
    $stmt_logs->execute();
    $result_logs = $stmt_logs->get_result();
    
    while ($row = $result_logs->fetch_assoc()) {
        $activity_logs[] = $row;
    }
    $stmt_logs->close();
}

$mysqli->close();

$fullName = htmlspecialchars($intern_details['first_name'] . ' ' . $intern_details['last_name']);
$position = htmlspecialchars($intern_details['posting_title']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intern Activity Log | <?php echo $fullName; ?></title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="companydashboard.css"> 
    <link rel="stylesheet" href="manage_applicants.css"> 
    <link rel="stylesheet" href="intern_progress.css"> </head>
<body>

<div class="dashboard-container">

    <aside class="sidebar">
        <div class="logo"><h2>Career Path</h2></div>
        <nav class="menu">
            <a href="companydashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="post_internship.php"><i class="fa-solid fa-file-circle-plus"></i> Post Internship</a>
            <a href="manage_applicants.php"><i class="fa-solid fa-users"></i> View Applicants</a>
            <a href="intern_progress.php" class="active"><i class="fa-solid fa-chart-line"></i> Intern Progress</a>
            <a href="company_profile.php"><i class="fa-solid fa-user-circle"></i> Profile</a>
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <h1>Activity Log for **<?php echo $fullName; ?>**</h1>
            <a href="intern_progress.php" style="color: rgb(32, 64, 227); text-decoration: none; font-weight: 600;"><i class="fa-solid fa-arrow-left"></i> Back to Intern List</a>
        </header>

        <div class="dashboard-body">

            <h2 style="margin-bottom: 20px; font-size: 18px;">Position: **<?php echo $position; ?>**</h2>

            <div class="log-container">
                <?php if (count($activity_logs) > 0): ?>
                    <div class="timeline">
                        <?php foreach ($activity_logs as $log): ?>
                            <div class="timeline-item">
                                <span class="log-date"><?php echo date('F j, Y - g:i A', strtotime($log['created_at'])); ?></span>
                                <div class="log-description"><?php echo nl2br(htmlspecialchars($log['activity_des'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-logs">
                        <i class="fa-solid fa-clipboard-list"></i> This intern has not submitted any activity logs yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

</body>
</html>