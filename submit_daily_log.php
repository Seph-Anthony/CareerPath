<?php
// submit_daily_log.php - Processes the student's daily log submission

session_start();
require_once 'db_connect.php'; 

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: signinstudent.html"); 
    exit;
}

// Check for POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("<script>alert('Invalid request method.'); window.location.href='daily_log_submission.php';</script>");
}

// 2. Collect and Sanitize Data
$student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
$application_id = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT);
$log_date = trim($_POST['log_date'] ?? '');
// FILTER_VALIDATE_FLOAT handles the decimal hours (e.g., 8.0 or 7.5)
$hours_logged = filter_input(INPUT_POST, 'hours_logged', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0.5]]);
$activities_performed = trim($_POST['activities_performed'] ?? '');

$user_id = $_SESSION['user_id'];

// 3. Robust Validation
if (!$student_id || !$application_id || empty($log_date) || $hours_logged === false || empty($activities_performed)) {
    die("<script>alert('Error: Missing or invalid log details. Please fill out all fields correctly.'); window.location.href='daily_log_submission.php';</script>");
}

// Check if the current student ID matches the session's user ID (Security Check)
$stmt_check_owner = $mysqli->prepare("SELECT student_id FROM student WHERE user_id = ? AND student_id = ? LIMIT 1");
if (!$stmt_check_owner) {
    error_log("Owner check prepare failed: " . $mysqli->error);
    die("<script>alert('Database Error (Owner Check).'); window.location.href='daily_log_submission.php';</script>");
}
$stmt_check_owner->bind_param('ii', $user_id, $student_id);
$stmt_check_owner->execute();
if ($stmt_check_owner->get_result()->num_rows === 0) {
    $stmt_check_owner->close();
    die("<script>alert('Security Error: Mismatched student data.'); window.location.href='daily_log_submission.php';</script>");
}
$stmt_check_owner->close();


// 4. Check for duplicate submission for the same date and application
$stmt_duplicate = $mysqli->prepare("SELECT log_id FROM daily_log WHERE application_id = ? AND log_date = ? LIMIT 1");
if (!$stmt_duplicate) {
    error_log("Duplicate check prepare failed: " . $mysqli->error);
    die("<script>alert('Database Error (Duplicate Check).'); window.location.href='daily_log_submission.php';</script>");
}
$stmt_duplicate->bind_param('is', $application_id, $log_date);
$stmt_duplicate->execute();
if ($stmt_duplicate->get_result()->num_rows > 0) {
    $stmt_duplicate->close();
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

// 6. Redirect back to the log submission page
echo "<script>alert('$alert_message'); window.location.href='daily_log_submission.php';</script>";
exit;
?>