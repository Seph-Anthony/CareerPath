<?php
// process_application.php
session_start();
require_once 'db_connect.php'; // Your database connection file

// --- Helper Function for Errors ---
function abort_with($msg, $location) {
    // Escapes single quotes in the message for JavaScript safety
    $safe_msg = addslashes($msg); 
    die("<script>alert('{$safe_msg}'); window.location.href='{$location}';</script>");
}

// --- 1. Security & Validation Checks ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    abort_with('Invalid request method.', 'studentdashboard.php');
}

// Ensure the user is a logged-in student
if ($_SESSION['role'] !== 'student' || !isset($_SESSION['user_id'])) {
    header("Location: signinstudent.html");
    exit;
}

// Get and validate inputs (these hidden fields come from view_post.php)
$posting_id = intval($_POST['posting_id'] ?? 0);
$student_id = intval($_POST['student_id'] ?? 0);

if ($posting_id <= 0 || $student_id <= 0) {
    // If data is missing, redirect back to dashboard
    abort_with('Missing required application data.', 'studentdashboard.php');
}

// Set the redirect URL for error cases (so they land back on the job post they were viewing)
$redirect_url = "view_post.php?id={$posting_id}";

// --- 2. Check for Duplicate Application ---
// Prevents a student from applying to the same job multiple times
$stmt_check = $mysqli->prepare("
    SELECT application_id 
    FROM intern_application 
    WHERE student_id = ? AND posting_id = ?
");
$stmt_check->bind_param('ii', $student_id, $posting_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $stmt_check->close();
    $mysqli->close();
    abort_with('You have already applied for this internship.', $redirect_url);
}
$stmt_check->close();


// --- 3. Insert Application into Database ---
// The `application_date` and `status` will automatically use their default values
// (CURRENT_TIMESTAMP and 'Pending', respectively)
$stmt_insert = $mysqli->prepare("
    INSERT INTO intern_application 
    (student_id, posting_id) 
    VALUES (?, ?)
");

if (!$stmt_insert) {
    error_log("MySQL Prepare Error: " . $mysqli->error);
    $mysqli->close();
    abort_with('A server error occurred during application submission. (DB Error)', $redirect_url);
}

$stmt_insert->bind_param(
    'ii', 
    $student_id, 
    $posting_id
);

$ok = $stmt_insert->execute();
$stmt_insert->close();
$mysqli->close();

// --- 4. Success or Failure Response ---
if ($ok) {
    // Success: Alert and redirect back to the post detail page
    die("<script>
            alert('Application submitted successfully! Status: Pending.');
            window.location.href = '{$redirect_url}';
          </script>");
} else {
    // Failure: Alert and redirect back to the post detail page
    error_log("Application failed: " . $mysqli->error);
    abort_with('Failed to submit application. Please try again.', $redirect_url);
}
?>