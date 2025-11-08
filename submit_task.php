<?php
// submit_task.php - Processes the Task Assignment form submission

session_start();
require_once 'db_connect.php'; 

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html"); 
    exit;
}

// Check for POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("<script>alert('Invalid request method.'); window.location.href='intern_progress.php';</script>");
}

// 2. Collect and Sanitize Data
$student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
$application_id = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT);
$company_id = filter_input(INPUT_POST, 'company_id', FILTER_VALIDATE_INT);
$task_title = trim($_POST['task_title'] ?? '');
$task_description = trim($_POST['task_description'] ?? '');
$due_date = trim($_POST['due_date'] ?? '');

// 3. Basic Validation
if (!$student_id || !$application_id || !$company_id || empty($task_title) || empty($task_description) || empty($due_date)) {
    die("<script>alert('Error: Missing required task details.'); window.location.href='assign_task.php?student_id=$student_id&application_id=$application_id';</script>");
}

// Ensure due date is a valid format (YYYY-MM-DD)
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $due_date)) {
    die("<script>alert('Error: Invalid due date format.'); window.location.href='assign_task.php?student_id=$student_id&application_id=$application_id';</script>");
}

// 4. Insert Task into the 'intern_tasks' table
$query_insert = "
    INSERT INTO intern_tasks 
    (application_id, company_id, student_id, task_title, task_description, due_date, status) 
    VALUES (?, ?, ?, ?, ?, ?, 'Pending')
";

$stmt_insert = $mysqli->prepare($query_insert);

if ($stmt_insert) {
    // Bind parameters: i i i s s s (int, int, int, string, string, string)
    $stmt_insert->bind_param(
        'iiisss', 
        $application_id, 
        $company_id, 
        $student_id, 
        $task_title, 
        $task_description, 
        $due_date
    );

    if ($stmt_insert->execute()) {
        $alert_message = "Task successfully assigned to the intern!";
    } else {
        $alert_message = "Error assigning task: " . $stmt_insert->error;
        error_log("Task Insert Failed: " . $stmt_insert->error);
    }
    $stmt_insert->close();
} else {
    $alert_message = "Database error: Could not prepare statement.";
    error_log("Task Prepare Failed: " . $mysqli->error);
}

$mysqli->close();

// 5. Redirect back to the task assignment page with the updated list
echo "<script>alert('$alert_message'); window.location.href='assign_task.php?student_id=$student_id&application_id=$application_id';</script>";
exit;
?>