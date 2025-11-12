<?php
// submit_evaluation.php - Handles the submission of the intern evaluation form

session_start();
require_once 'db_connect.php'; 
require_once 'activity_logger.php'; // NEW: Include the logging utility

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html"); 
    exit;
}

// Check for POST request and required data
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("<script>alert('Invalid request method.'); window.location.href='intern_progress.php';</script>");
}

// 2. Collect and Sanitize Data
$student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
$application_id = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT);
$company_id = filter_input(INPUT_POST, 'company_id', FILTER_VALIDATE_INT);
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT); // This variable holds the score
$feedback = trim($_POST['feedback'] ?? ''); // This variable holds the remark
$final_status = $_POST['final_status'] ?? '';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'unknown_company_user';

// Basic validation
if (!$student_id || !$application_id || !$company_id || !$rating || empty($feedback) || empty($final_status)) {
    die("<script>alert('Error: Missing required evaluation data.'); window.location.href='intern_progress.php';</script>");
}

// Further validation for score and status
if ($rating < 1 || $rating > 5) {
    die("<script>alert('Error: Rating must be between 1 and 5.'); window.location.href='intern_evaluation.php?student_id=$student_id&application_id=$application_id';</script>");
}

// --- LOGGING PREP: Fetch Names ---
$student_name = 'Unknown Student';
$company_name = 'Unknown Company';

// Fetch Student Name
$stmt_s_name = $mysqli->prepare("SELECT first_name, last_name FROM student WHERE student_id = ? LIMIT 1");
if ($stmt_s_name) {
    $stmt_s_name->bind_param('i', $student_id);
    $stmt_s_name->execute();
    $result = $stmt_s_name->get_result();
    if ($result->num_rows > 0) {
        $s_data = $result->fetch_assoc();
        $student_name = htmlspecialchars($s_data['first_name'] . ' ' . $s_data['last_name']);
    }
    $stmt_s_name->close();
}

// Fetch Company Name
$stmt_c_name = $mysqli->prepare("SELECT company_name FROM company WHERE company_id = ? LIMIT 1");
if ($stmt_c_name) {
    $stmt_c_name->bind_param('i', $company_id);
    $stmt_c_name->execute();
    $result = $stmt_c_name->get_result();
    if ($result->num_rows > 0) {
        $c_data = $result->fetch_assoc();
        $company_name = htmlspecialchars($c_data['company_name']);
    }
    $stmt_c_name->close();
}
// --- END LOGGING PREP ---


// 3. Check for Duplicate Submission
$stmt_check = $mysqli->prepare("SELECT evaluation_id FROM evaluation WHERE application_id = ? LIMIT 1");
if ($stmt_check) {
    $stmt_check->bind_param('i', $application_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        die("<script>alert('Evaluation already submitted for this intern.'); window.location.href='intern_progress.php';</script>");
    }
    $stmt_check->close();
}


// 4. Start Transaction for Atomic Updates
$mysqli->begin_transaction();
$success = true;

// 5. Insert Evaluation into the 'evaluation' table
$query_insert = "
    INSERT INTO evaluation 
    (application_id, company_id, student_id, score, remark, submitted_at) 
    VALUES (?, ?, ?, ?, ?, NOW())
";

$stmt_insert = $mysqli->prepare($query_insert);
if ($stmt_insert) {
    // Bind parameters: i i i i s (int, int, int, int, string)
    $stmt_insert->bind_param('iiiis', $application_id, $company_id, $student_id, $rating, $feedback);
    if (!$stmt_insert->execute()) {
        $success = false;
        error_log("Evaluation Insert Failed: " . $stmt_insert->error);
    }
    $stmt_insert->close();
} else {
    $success = false;
    error_log("Evaluation Prepare Failed: " . $mysqli->error);
}

// 6. Update Application Status (if required)
// Only update if the status is changing to Completed or Terminated
if ($success && ($final_status === 'Completed' || $final_status === 'Terminated')) {
    $query_update = "
        UPDATE intern_application 
        SET status = ? 
        WHERE application_id = ?
    ";
    
    $stmt_update = $mysqli->prepare($query_update);
    if ($stmt_update) {
        // Bind parameters: s i (string, int)
        $stmt_update->bind_param('si', $final_status, $application_id);
        if (!$stmt_update->execute()) {
            $success = false;
            error_log("Status Update Failed: " . $stmt_update->error);
        }
        $stmt_update->close();
    } else {
        $success = false;
        error_log("Status Update Prepare Failed: " . $mysqli->error);
    }
}

// 7. Commit or Rollback Transaction
if ($success) {
    
    // --- START LOGGING ---
    $log_message = "Company **{$company_name}** (User: **{$username}**) submitted an **evaluation** for student **{$student_name}**. Rating: **{$rating}/5**. Final Status set to **{$final_status}**.";
    log_activity($mysqli, $log_message);
    // --- END LOGGING ---
    
    $mysqli->commit();
    $mysqli->close();
    $alert_message = "Evaluation successfully submitted! Status updated to $final_status.";
} else {
    $mysqli->rollback();
    $mysqli->close();
    $alert_message = "Error submitting evaluation. Please try again.";
}

// Redirect back to the intern progress list
echo "<script>alert('$alert_message'); window.location.href='intern_progress.php';</script>";
exit;
?>