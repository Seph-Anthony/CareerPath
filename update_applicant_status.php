<?php
// update_applicant_status.php - Handles status change for an application
session_start();
require_once 'db_connect.php';

// 1. Authorization and Method Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: signincompany.html"); 
    exit;
}

// 2. Collect & Validate Inputs
$student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
$posting_id = isset($_POST['posting_id']) ? intval($_POST['posting_id']) : 0;
$application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0; 
$new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';

// Define allowed statuses for strict validation
$allowed_statuses = ['Pending', 'Interviewing', 'Hired', 'Rejected'];

if ($student_id === 0 || $posting_id === 0 || $application_id === 0 || !in_array($new_status, $allowed_statuses)) {
    die("<script>alert('Invalid data provided for status update. Missing Application ID, Posting ID, Student ID, or invalid status.'); window.history.back();</script>");
}

$user_id = $_SESSION['user_id'];

// 3. Security Check: Verify post belongs to the company
$stmt_company = $mysqli->prepare("
    SELECT c.company_id 
    FROM company c
    JOIN intern_posting ip ON c.company_id = ip.company_id
    WHERE c.user_id = ? AND ip.posting_id = ? 
    LIMIT 1
");

if ($stmt_company) {
    $stmt_company->bind_param('ii', $user_id, $posting_id);
    $stmt_company->execute();
    $result_company = $stmt_company->get_result();
    
    if ($result_company->num_rows === 0) {
        die("<script>alert('Security Error: This posting does not belong to your company.'); window.location.href='manage_applicants.php';</script>");
    }
    $stmt_company->close();
} else {
    die("<script>alert('Database validation error during security check.'); window.history.back();</script>");
}

// 4. Update the application status (Using application_id as the sole WHERE condition for simplicity and reliability)
$query = "
    UPDATE intern_application 
    SET status = ? 
    WHERE application_id = ?
";

$stmt = $mysqli->prepare($query);

if ($stmt) {
    // Bind parameters: new_status (string), application_id (integer)
    $stmt->bind_param('si', $new_status, $application_id);
    
    if ($stmt->execute()) {
        $rows_affected = $stmt->affected_rows;

        $stmt->close();
        $mysqli->close();
        
        // Determine the success message
        if ($rows_affected > 0) {
            $success_message = "Application status successfully updated to: {$new_status}";
        } else {
            // This handles the case where the status was already set to $new_status
            $success_message = "Status is already set to: {$new_status}";
        }

        // Success: Redirect back to the detail view
        $redirect_url = "view_applicant_details.php?student_id=" . $student_id . "&posting_id=" . $posting_id . "&application_id=" . $application_id;
        echo "<script>alert('{$success_message}'); window.location.href='{$redirect_url}';</script>";
        exit;
        
    } else {
        $stmt->close();
        $mysqli->close();
        // Failure: Redirect back with a fatal database error
        die("<script>alert('Failed to update application status due to database error: " . $mysqli->error . "'); window.history.back();</script>");
    }
} else {
    $mysqli->close();
    die("<script>alert('Database preparation error: " . $mysqli->error . "'); window.history.back();</script>");
}
?>