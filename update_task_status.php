<?php
// update_task_status.php - Processes task status updates from the student

session_start();
require_once 'db_connect.php'; 

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
$student_id = null;

// --- 1.5: Fetch the actual student_id from the user_id ---
// We need the student_id to ensure the user is updating their own task.
$stmt_fetch_student = $mysqli->prepare("SELECT student_id FROM student WHERE user_id = ? LIMIT 1");
if ($stmt_fetch_student) {
    $stmt_fetch_student->bind_param('i', $user_id);
    $stmt_fetch_student->execute();
    $result_student = $stmt_fetch_student->get_result();
    if ($result_student->num_rows === 1) {
        $student_data = $result_student->fetch_assoc();
        $student_id = $student_data['student_id'];
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

// 4. Update Task Status (with ownership check using student_id)
// Only update the task if its ID and its assigned student_id match the logged-in user.
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
            $alert_message = "Task status successfully updated to $new_status.";
        } else {
            // This catches attempts to update a task that doesn't belong to the user
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

// 5. Redirect back to the task list
echo "<script>alert('$alert_message'); window.location.href='student_tasks.php';</script>";
exit;
?>