<?php
// process_application.php
session_start();
require_once 'db_connect.php'; // Your database connection file
require_once 'activity_logger.php'; // NEW: Include the logging utility

// --- Helper Function for Errors ---
function abort_with($msg, $location) {
    // Escapes single quotes in the message for JavaScript safety
    $safe_msg = addslashes($msg); 
    // We do not close $mysqli here as the calling code will handle it later if it fails the application insertion
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
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'unknown_user'; // For the log

if ($posting_id <= 0 || $student_id <= 0) {
    // If data is missing, redirect back to dashboard
    abort_with('Missing required application data.', 'studentdashboard.php');
}

// Set the redirect URL for error cases (so they land back on the job post they were viewing)
$redirect_url = "view_post.php?id={$posting_id}";

// --- LOGGING PREP: Fetch Student and Post Details ---
$student_name = 'Unknown Student';
$post_title = 'Unknown Post';
$company_name = 'Unknown Company'; // Assuming 'company' table exists

// 1. Fetch Student Name (Used for logging)
$stmt_name = $mysqli->prepare("SELECT first_name, last_name FROM student WHERE student_id = ? AND user_id = ? LIMIT 1");
if ($stmt_name) {
    $stmt_name->bind_param('ii', $student_id, $user_id);
    $stmt_name->execute();
    $result_name = $stmt_name->get_result();
    if ($result_name->num_rows === 1) {
        $data = $result_name->fetch_assoc();
        $student_name = htmlspecialchars($data['first_name'] . ' ' . $data['last_name']);
    }
    $stmt_name->close();
}

// 2. Fetch Post Title and Company Name (Crucial: Using 'intern_posting' as specified)
$stmt_post = $mysqli->prepare("
    SELECT p.title, c.company_name 
    FROM intern_posting p
    JOIN company c ON p.company_id = c.company_id
    WHERE p.posting_id = ? LIMIT 1
");
if ($stmt_post) {
    $stmt_post->bind_param('i', $posting_id);
    $stmt_post->execute();
    $result_post = $stmt_post->get_result();
    if ($result_post->num_rows === 1) {
        $data = $result_post->fetch_assoc();
        $post_title = htmlspecialchars($data['title']);
        $company_name = htmlspecialchars($data['company_name']);
    }
    $stmt_post->close();
}


// --- 3. Check for Duplicate Application ---
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


// --- 4. Insert Application into Database ---
// The `application_date` and `status` will automatically use their default values
// (CURRENT_TIMESTAMP and 'Pending', respectively)
$stmt_insert = $mysqli->prepare("
    INSERT INTO intern_application 
    (student_id, posting_id) 
    VALUES (?, ?)
");

if (!$stmt_insert) {
    error_log("MySQL Prepare Error (Insert): " . $mysqli->error);
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


// --- 5. Success or Failure Response and Logging ---
if ($ok) {
    
    // --- START LOGGING ---
    $log_message = "Student **{$student_name}** (User: **{$username}**) submitted an application for the post **'{$post_title}'** at **{$company_name}**.";
    log_activity($mysqli, $log_message);
    // --- END LOGGING ---

    $mysqli->close();
    // Success: Alert and redirect back to the post detail page
    die("<script>
            alert('Application submitted successfully! Status: Pending.');
            window.location.href = '{$redirect_url}';
          </script>");
} else {
    // Failure: Alert and redirect back to the post detail page
    error_log("Application failed: " . $mysqli->error);
    $mysqli->close();
    abort_with('Failed to submit application. Please try again.', $redirect_url);
}
?>