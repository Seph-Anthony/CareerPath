<?php
// process_log_approval.php - Handles the approval or rejection of student daily logs

session_start();
require_once 'db_connect.php'; 

// 1. Authentication Check (Ensure user is a company supervisor)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html"); 
    exit;
}

// Check for POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("<script>alert('Invalid request method.'); window.location.href='company_log_approval.php';</script>");
}

// 2. Collect and Sanitize Data
$log_id = filter_input(INPUT_POST, 'log_id', FILTER_VALIDATE_INT);
// The company_id submitted via hidden field on the form
$submitted_company_id = filter_input(INPUT_POST, 'company_id', FILTER_VALIDATE_INT);
// The action (either 'Approved' or 'Rejected')
$action = trim($_POST['action'] ?? ''); 

$user_company_id = null;
$user_id = $_SESSION['user_id'];

// 3. Validation
if (!$log_id || !in_array($action, ['Approved', 'Rejected'])) {
    die("<script>alert('Error: Invalid log ID or action provided.'); window.location.href='company_log_approval.php';</script>");
}

// 4. Critical Security Check: Get the actual company_id from the session user_id
// This prevents a rogue user from trying to process a log for another company.
$stmt_fetch_company = $mysqli->prepare("SELECT company_id FROM company WHERE user_id = ? LIMIT 1");
$stmt_fetch_company->bind_param('i', $user_id);
$stmt_fetch_company->execute();
$result_company = $stmt_fetch_company->get_result();
if ($result_company->num_rows === 1) {
    $user_company_id = $result_company->fetch_assoc()['company_id'];
}
$stmt_fetch_company->close();

if (!$user_company_id || $user_company_id !== $submitted_company_id) {
    // If the company ID in the session doesn't match the one submitted, or is invalid
    die("<script>alert('Security Error: Company ID mismatch or invalid session.'); window.location.href='company_log_approval.php';</script>");
}

// 5. Update Log Status 
// The query joins three tables to ensure the log belongs to an intern hired by this company's posting
$query_update = "
    UPDATE daily_log dl
    JOIN intern_application ia ON dl.application_id = ia.application_id
    JOIN intern_posting ip ON ia.posting_id = ip.posting_id
    SET 
        dl.status = ?,
        dl.approved_by_company_id = ? 
    WHERE 
        dl.log_id = ? 
        AND dl.status = 'Pending'
        AND ip.company_id = ?
";

$stmt_update = $mysqli->prepare($query_update);

if ($stmt_update) {
    // Bind parameters: s i i i (status, company_id, log_id, ip.company_id)
    $stmt_update->bind_param('siii', $action, $user_company_id, $log_id, $user_company_id);

    if ($stmt_update->execute()) {
        if ($stmt_update->affected_rows > 0) {
            $alert_message = "Log ID $log_id successfully marked as $action.";
        } else {
            $alert_message = "Update failed. Log not found, or it was already processed.";
        }
    } else {
        $alert_message = "Error updating log status: " . $stmt_update->error;
        error_log("Log Approval Failed: " . $stmt_update->error);
    }
    $stmt_update->close();
} else {
    $alert_message = "Database error: Could not prepare statement.";
    error_log("Log Approval Prepare Failed: " . $mysqli->error);
}

$mysqli->close();

// 6. Redirect back to the approval list
echo "<script>alert('$alert_message'); window.location.href='company_log_approval.php';</script>";
exit;
?>