<?php
// update_task_status.php - Processes task status updates from the student

session_start();
require_once 'db_connect.php'; 
require_once 'activity_logger.php'; // NEW: Include the logging utility

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: signinstudent.html"); 
    exit;
}

// Check for POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("<script>alert('Invalid request method.'); window.location.href='student_tasks.php';</script>");
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'unknown_user'; // For log
$student_id = null;
$fullname = 'Unknown Student'; // For log

// --- 1.5: Fetch the actual student_id and full name ---
$stmt_fetch_student = $mysqli->prepare("SELECT student_id, first_name, last_name FROM student WHERE user_id = ? LIMIT 1");
if ($stmt_fetch_student) {
    $stmt_fetch_student->bind_param('i', $user_id);
    $stmt_fetch_student->execute();
    $result_student = $stmt_fetch_student->get_result();
    if ($result_student->num_rows === 1) {
        $student_data = $result_student->fetch_assoc();
        $student_id = $student_data['student_id'];
        $fullname = htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']);
    }
    $stmt_fetch_student->close();
}

if (!$student_id) {
    die("<script>alert('Error: Could not determine student ID.'); window.location.href='student_tasks.php';</script>");
}

// 2. Collect and Sanitize Data
$task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
$new_status = trim($_POST['new_status'] ?? '');

// 3. Basic Validation
$allowed_statuses = ['Pending', 'In Progress', 'Awaiting Review', 'Completed'];

if (!$task_id || !in_array($new_status, $allowed_statuses)) {
    die("<script>alert('Error: Invalid task ID or status provided.'); window.location.href='student_tasks.php';</script>");
}

// 4. Fetch the task title before update (for the log)
$task_title = "Unknown Task";
$stmt_fetch_task = $mysqli->prepare("SELECT title FROM intern_tasks WHERE task_id = ? AND student_id = ? LIMIT 1");
if ($stmt_fetch_task) {
    $stmt_fetch_task->bind_param('ii', $task_id, $student_id);
    $stmt_fetch_task->execute();
    $result_task = $stmt_fetch_task->get_result();
    if ($result_task->num_rows === 1) {
        $task_data = $result_task->fetch_assoc();
        $task_title = htmlspecialchars($task_data['title']);
    }
    $stmt_fetch_task->close();
}

// 5. Update Task Status (with ownership check using student_id)
$query_update = "
    UPDATE intern_tasks
    SET status = ?
    WHERE task_id = ? AND student_id = ?
";

$stmt_update = $mysqli->prepare($query_update);

if ($stmt_update) {
    // Bind parameters: s i i (string, task_id, student_id)
    $stmt_update->bind_param('sii', $new_status, $task_id, $student_id);

    if ($stmt_update->execute()) {
        if ($stmt_update->affected_rows > 0) {
            
            // --- START LOGGING ---
            $log_message = "Student **{$fullname}** (User: **{$username}**) updated task **'{$task_title}'** to status: **{$new_status}**.";
            log_activity($mysqli, $log_message);
            // --- END LOGGING ---

            $alert_message = "Task status successfully updated to $new_status.";
        } else {
            // This catches attempts to update a task that doesn't belong to the user or if status is unchanged
            $alert_message = "Status update failed. Task not found or already set to $new_status.";
        }
    } else {
        $alert_message = "Error updating task status: " . $stmt_update->error;
        error_log("Task Status Update Failed: " . $stmt_update->error);
    }
    $stmt_update->close();
} else {
    $alert_message = "Database error: Could not prepare statement.";
    error_log("Task Prepare Failed: " . $mysqli->error);
}

$mysqli->close();

// 6. Redirect back to the task list
echo "<script>alert('$alert_message'); window.location.href='student_tasks.php';</script>";
exit;
?>