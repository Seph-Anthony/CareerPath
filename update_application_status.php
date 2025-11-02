<?php
// update_application_status.php
session_start();
require_once 'db_connect.php'; 

// --- Helper Function for Errors/Success ---
function redirect_back_with_alert($msg, $success = true) {
    // We redirect back to the view_applicants page
    $safe_msg = addslashes($msg); 
    $alert_type = $success ? 'Success' : 'Error';

    // The coordinator needs to refresh the page to see the change
    die("<script>
            alert('{$alert_type}: {$safe_msg}'); 
            window.location.href='view_applicants.php';
         </script>");
}

// --- 1. Security & Validation Checks ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_back_with_alert('Invalid request method.', false);
}

// Ensure the user is a logged-in company
if ($_SESSION['role'] !== 'company' || !isset($_SESSION['user_id'])) {
    header("Location: signincompany.html");
    exit;
}

// Get and validate inputs
$application_id = intval($_POST['application_id'] ?? 0);
$new_status = trim($_POST['new_status'] ?? '');

// List of allowed statuses to prevent SQL injection and invalid data
$allowed_statuses = ['Reviewed', 'Interview Scheduled', 'Hired', 'Rejected'];

if ($application_id <= 0 || !in_array($new_status, $allowed_statuses)) {
    redirect_back_with_alert('Missing or invalid application ID or status.', false);
}

// --- 2. Check Ownership (Crucial Security Step) ---
// We must ensure the company updating the application is the company that owns the posting.
$user_id = $_SESSION['user_id'];

$stmt_check_ownership = $mysqli->prepare("
    SELECT COUNT(a.application_id)
    FROM intern_application a
    JOIN intern_posting p ON a.posting_id = p.posting_id
    JOIN company c ON p.company_id = c.company_id
    WHERE a.application_id = ? AND c.user_id = ?
");
$stmt_check_ownership->bind_param('ii', $application_id, $user_id);
$stmt_check_ownership->execute();
$stmt_check_ownership->bind_result($count);
$stmt_check_ownership->fetch();
$stmt_check_ownership->close();

if ($count == 0) {
    redirect_back_with_alert('Security Alert: You do not have permission to modify this application.', false);
}

// --- 3. Update Application Status ---
$stmt_update = $mysqli->prepare("
    UPDATE intern_application 
    SET status = ? 
    WHERE application_id = ?
");

if (!$stmt_update) {
    error_log("MySQL Prepare Error: " . $mysqli->error);
    $mysqli->close();
    redirect_back_with_alert('A server error occurred during the status update. (DB Error)', false);
}

$stmt_update->bind_param(
    'si', 
    $new_status, 
    $application_id
);

$ok = $stmt_update->execute();
$stmt_update->close();
$mysqli->close();

// --- 4. Success or Failure Response ---
if ($ok) {
    redirect_back_with_alert("Application status updated to '{$new_status}'.", true);
} else {
    error_log("Status update failed: " . $mysqli->error);
    redirect_back_with_alert('Failed to update application status. Please try again.', false);
}
?>