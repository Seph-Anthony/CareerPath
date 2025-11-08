<?php
// daily_log_submission.php - Student form for submitting daily activity log

session_start();
require_once 'db_connect.php'; 

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: signinstudent.html"); 
    exit;
}

$user_id = $_SESSION['user_id'];
$student_id = null;
$fullname = "Intern";
$active_internship = null;

// 2. Fetch Student ID
$stmt_student = $mysqli->prepare("SELECT student_id, first_name, last_name FROM student WHERE user_id = ? LIMIT 1");
if ($stmt_student) {
    $stmt_student->bind_param('i', $user_id);
    $stmt_student->execute();
    $result_student = $stmt_student->get_result();
    if ($result_student->num_rows === 1) {
        $data = $result_student->fetch_assoc();
        $student_id = $data['student_id'];
        $fullname = htmlspecialchars($data['first_name'] . ' ' . $data['last_name']);
    }
    $stmt_student->close();
}

if (!$student_id) {
    die("Error: Student profile not found.");
}

// 3. Find Current Active (Hired) Internship - FIXED QUERY
$query_active_internship = "
    SELECT 
        ia.application_id, 
        ip.title AS position, 
        c.company_name
    FROM 
        intern_application ia
    JOIN 
        intern_posting ip ON ia.posting_id = ip.posting_id
    JOIN 
        company c ON ip.company_id = c.company_id
    WHERE 
        ia.student_id = ? AND ia.status = 'Hired'
    LIMIT 1
";

$stmt_internship = $mysqli->prepare($query_active_internship);
if ($stmt_internship) {
    $stmt_internship->bind_param('i', $student_id);
    $stmt_internship->execute();
    $result_internship = $stmt_internship->get_result();
    if ($result_internship->num_rows === 1) {
        $active_internship = $result_internship->fetch_assoc();
    }
    $stmt_internship->close();
}

// 4. Fetch Existing Logs 
$recent_logs = [];
if ($active_internship) {
    $query_logs = "
        SELECT 
            log_date, 
            hours_logged, 
            status
        FROM 
            daily_log
        WHERE 
            application_id = ?
        ORDER BY 
            log_date DESC 
        LIMIT 5
    ";
    $stmt_logs = $mysqli->prepare($query_logs);
    if ($stmt_logs) {
        $stmt_logs->bind_param('i', $active_internship['application_id']);
        $stmt_logs->execute();
        $result_logs = $stmt_logs->get_result();
        while ($row = $result_logs->fetch_assoc()) {
            $recent_logs[] = $row;
        }
        $stmt_logs->close();
    }
}


$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Activity Log | Career Path</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="studentdashboard.css">
    <link rel="stylesheet" href="student_tasks.css"> 
    <link rel="stylesheet" href="daily_log.css"> 
</head>
<body>

    <div class="sidebar">
        <div class="logo">Career Path</div>
        <nav class="menu">
            <a href="studentdashboard.php" class="nav-link"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="#" class="nav-link"><i class="fa-solid fa-briefcase"></i> Apply for OJT</a>
            <a href="#" class="nav-link"><i class="fa-solid fa-file-contract"></i> My Applications</a>
            <a href="daily_log_submission.php" class="nav-link active"><i class="fa-solid fa-clock-rotate-left"></i> Daily Log</a>
            <a href="student_tasks.php" class="nav-link"><i class="fa-solid fa-list-check"></i> My Tasks</a>
            <a href="#" class="nav-link"><i class="fa-solid fa-user-circle"></i> Profile & Resume</a>
            <a href="index.html" class="nav-link"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="header">
            <h1>Daily Activity Log Submission</h1>
            <div class="user-info">
                 <i class="fa-solid fa-user-circle"></i> <?php echo $fullname; ?>
            </div>
        </header>

        <div class="dashboard-body log-container">

            <?php if ($active_internship): ?>
                <div class="info-bar">
                    <p>
                        **Active Internship:** **<?php echo htmlspecialchars($active_internship['position']); ?>** at **<?php echo htmlspecialchars($active_internship['company_name']); ?>**
                    </p>
                    <p>
                        Please submit your daily log entries for approval by your Company Supervisor.
                    </p>
                </div>

                <div class="log-card">
                    <h2>Submit Daily Entry</h2>
                    <form action="submit_daily_log.php" method="POST">
                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
                        <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($active_internship['application_id']); ?>">

                        <div class="form-group">
                            <label for="log_date">Date of Activity:</label>
                            <input type="date" id="log_date" name="log_date" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="hours_logged">Hours Logged (e.g., 8.0):</label>
                            <input type="number" id="hours_logged" name="hours_logged" step="0.5" min="0.5" max="16.0" required>
                        </div>

                        <div class="form-group">
                            <label for="activities_performed">Detailed Activities Performed Today:</label>
                            <textarea id="activities_performed" name="activities_performed" rows="10" placeholder="List the tasks you worked on, focusing on details related to your assigned tasks and position duties." required></textarea>
                        </div>

                        <button type="submit" class="submit-btn">Submit Log Entry</button>
                    </form>
                </div>
                
                <?php if (!empty($recent_logs)): ?>
                <div class="log-history">
                    <h3>Recent Log History (Last 5 Entries)</h3>
                    <?php foreach ($recent_logs as $log): ?>
                        <div class="log-item">
                            <span class="log-date"><?php echo htmlspecialchars(date('M d, Y', strtotime($log['log_date']))); ?></span>
                            <span class="log-hours"><?php echo htmlspecialchars(number_format($log['hours_logged'], 1)); ?> hours</span>
                            <span class="status-<?php echo htmlspecialchars($log['status']); ?>"><?php echo htmlspecialchars($log['status']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="log-card" style="text-align: center; border-left: 5px solid red;">
                    <h2>No Active Internship Found</h2>
                    <p>You must have an application with the status 'Hired' to submit a daily activity log.</p>
                    <p>Please check your applications or contact your OJT Coordinator.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>