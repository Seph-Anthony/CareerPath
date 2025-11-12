<?php
// submit_daily_log.php - Processes the student's daily log submission

session_start();
require_once 'db_connect.php'; 
require_once 'activity_logger.php'; // NEW: Include the logging utility

// Define the target redirect URL
$redirect_target = 'studentdashboard.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: signinstudent.html"); 
    exit;
}

// Check for POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Redirect invalid request to the dashboard
    die("<script>alert('Invalid request method.'); window.location.href='{$redirect_target}';</script>");
}

// 2. Collect and Sanitize Data
$student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
$application_id = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT);
$log_date = trim($_POST['log_date'] ?? '');
// FILTER_VALIDATE_FLOAT handles the decimal hours (e.g., 8.0 or 7.5)
$hours_logged = filter_input(INPUT_POST, 'hours_logged', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0.5]]);
$activities_performed = trim($_POST['activities_performed'] ?? '');

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'unknown_user'; // Get username for the log
$fullname = 'Unknown Student'; // Default name

// 3. Robust Validation
if (!$student_id || !$application_id || empty($log_date) || $hours_logged === false || empty($activities_performed)) {
    // Redirect validation errors to the submission page where fields can be fixed
    die("<script>alert('Error: Missing or invalid log details. Please fill out all fields correctly.'); window.location.href='daily_log_submission.php';</script>");
}

// Check if the current student ID matches the session's user ID (Security Check)
$stmt_check_owner = $mysqli->prepare("SELECT student_id, first_name, last_name FROM student WHERE user_id = ? AND student_id = ? LIMIT 1");
if (!$stmt_check_owner) {
    error_log("Owner check prepare failed: " . $mysqli->error);
    // Redirect database errors to the dashboard
    die("<script>alert('Database Error (Owner Check).'); window.location.href='{$redirect_target}';</script>");
}
$stmt_check_owner->bind_param('ii', $user_id, $student_id);
$stmt_check_owner->execute();
$result_owner = $stmt_check_owner->get_result();
if ($result_owner->num_rows === 0) {
    $stmt_check_owner->close();
    // Redirect security errors to the dashboard
    die("<script>alert('Security Error: Mismatched student data.'); window.location.href='{$redirect_target}';</script>");
}
$owner_data = $result_owner->fetch_assoc();
$fullname = htmlspecialchars($owner_data['first_name'] . ' ' . $owner_data['last_name']); // Set full name
$stmt_check_owner->close();


// 4. Check for duplicate submission for the same date and application
$stmt_duplicate = $mysqli->prepare("SELECT log_id FROM daily_log WHERE application_id = ? AND log_date = ? LIMIT 1");
if (!$stmt_duplicate) {
    error_log("Duplicate check prepare failed: " . $mysqli->error);
    // Redirect database errors to the dashboard
    die("<script>alert('Database Error (Duplicate Check).'); window.location.href='{$redirect_target}';</script>");
}
$stmt_duplicate->bind_param('is', $application_id, $log_date);
$stmt_duplicate->execute();
if ($stmt_duplicate->get_result()->num_rows > 0) {
    $stmt_duplicate->close();
    // Redirect duplicate warnings back to the submission page
    die("<script>alert('Warning: A log entry already exists for this date. You may only submit one log per day.'); window.location.href='daily_log_submission.php';</script>");
}
$stmt_duplicate->close();


// 5. Insert Log into the 'daily_log' table
$query_insert = "
    INSERT INTO daily_log 
    (student_id, application_id, log_date, hours_logged, activities_performed, status) 
    VALUES (?, ?, ?, ?, ?, 'Pending')
";

$stmt_insert = $mysqli->prepare($query_insert);

if ($stmt_insert) {
    // Bind parameters: i i s d s (int, int, string, decimal/float, string)
    $stmt_insert->bind_param(
        'iisds', 
        $student_id, 
        $application_id, 
        $log_date, 
        $hours_logged, 
        $activities_performed
    );

    if ($stmt_insert->execute()) {
        
        // --- START LOGGING ---
        $safe_log_date = htmlspecialchars($log_date);
        $safe_hours = htmlspecialchars(number_format($hours_logged, 1));
        $log_message = "Student **{$fullname}** (User: **{$username}**) submitted a **Daily Log** for **{$safe_log_date}** ({$safe_hours} hours).";
        log_activity($mysqli, $log_message);
        // --- END LOGGING ---

        $alert_message = "Daily log submitted successfully! It is now 'Pending' approval.";
    } else {
        $alert_message = "Error submitting log: " . $stmt_insert->error;
        error_log("Daily Log Insert Failed: " . $stmt_insert->error);
    }
    $stmt_insert->close();
} else {
    $alert_message = "Database error: Could not prepare statement.";
    error_log("Daily Log Prepare Failed: " . $mysqli->error);
}

$mysqli->close();

// 6. Redirect to the student dashboard
echo "<script>alert('$alert_message'); window.location.href='{$redirect_target}';</script>";
exit;
?>