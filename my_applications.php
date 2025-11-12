<?php
// my_applications.php - Student view of all submitted applications

session_start();
require_once 'db_connect.php'; 

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: signinstudent.html"); 
    exit;
}

$user_id = $_SESSION['user_id'];
$student_id = null;
$applications = [];

// 2. Fetch Student ID
$stmt_student = $mysqli->prepare("SELECT student_id, first_name FROM student WHERE user_id = ? LIMIT 1");
if ($stmt_student) {
    $stmt_student->bind_param('i', $user_id);
    $stmt_student->execute();
    $result_student = $stmt_student->get_result();
    if ($result_student->num_rows === 1) {
        $student_id = $result_student->fetch_assoc()['student_id'];
    }
    $stmt_student->close();
}

if (!$student_id) {
    die("Error: Student profile not found.");
}

// 3. Fetch All Applications for this Student
$query_applications = "
    SELECT 
        ia.application_id,
        ia.application_date,
        ia.status,
        ip.title AS position_title,
        c.company_name
    FROM 
        intern_application ia
    JOIN 
        intern_posting ip ON ia.posting_id = ip.posting_id
    JOIN 
        company c ON ip.company_id = c.company_id
    WHERE 
        ia.student_id = ?
    ORDER BY 
        ia.application_date DESC
";

$stmt_apps = $mysqli->prepare($query_applications);
if ($stmt_apps) {
    $stmt_apps->bind_param('i', $student_id);
    $stmt_apps->execute();
    $result_apps = $stmt_apps->get_result();
    while ($row = $result_apps->fetch_assoc()) {
        $applications[] = $row;
    }
    $stmt_apps->close();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications | Career Path</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="studentdashboard.css">
    <link rel="stylesheet" href="my_applications.css"> 
    <link rel="stylesheet" href="companydashboard.css"> 
</head>
<body>
    <div class="sidebar">
        <div class="logo">Career Path</div>
        <nav class="menu">
                <a href="studentdashboard.php" class="nav-link"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="#active_post" class="nav-link"><i class="fa-solid fa-briefcase"></i> Apply for OJT</a>
                <a href="my_applications.php" class="nav-link active"><i class="fa-solid fa-file-contract"></i> My Applications</a>
                <a href="daily_log_submission.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> Daily Log</a>
                <a href="student_tasks.php" class="nav-link"><i class="fa-solid fa-list-check"></i> My Tasks</a>
                <a href="manage_requirements.php"><i class="fa-solid fa-file-upload"></i> Manage Requirements</a> 
                <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
            </nav>
    </div>

    <div class="main-content">
        <header class="header">
            <h1>My Internship Applications</h1>
        </header>

        <div class="dashboard-body apps-container">
            <?php if (!empty($applications)): ?>
                <div class="app-list">
                    <?php foreach ($applications as $app): ?>
                        <div class="app-card">
                            <div class="app-details">
                                <h3><?php echo htmlspecialchars($app['position_title']); ?></h3>
                                <p>Company: **<?php echo htmlspecialchars($app['company_name']); ?>**</p>
                                <p>Applied On: <?php echo htmlspecialchars(date('M d, Y', strtotime($app['application_date']))); ?></p>
                            </div>
                            <span class="app-status status-<?php echo htmlspecialchars($app['status']); ?>">
                                <?php echo htmlspecialchars($app['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="app-card" style="border-left-color: orange; text-align: center;">
                    <p>You have not submitted any internship applications yet. Start applying from the Dashboard!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>