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
$new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';

if ($student_id === 0 || $posting_id === 0 || empty($new_status)) {
    die("<script>alert('Invalid data provided for status update.'); window.history.back();</script>");
}

$user_id = $_SESSION['user_id'];
$company_id = null;

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
    die("<script>alert('Database validation error.'); window.history.back();</script>");
}


// 4. Update the application status
$query = "
    UPDATE application 
    SET status = ? 
    WHERE student_id = ? AND posting_id = ?
";

$stmt = $mysqli->prepare($query);

if ($stmt) {
    $stmt->bind_param('sii', $new_status, $student_id, $posting_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        $mysqli->close();
        
        // Success: Redirect back to the detail view with a success message
        $redirect_url = "view_applicant_details.php?student_id=" . $student_id . "&posting_id=" . $posting_id;
        $_SESSION['success_message'] = "Application status successfully updated to **" . htmlspecialchars($new_status) . "**.";
        header("Location: " . $redirect_url);
        exit;
        
    } else {
        $stmt->close();
        $mysqli->close();
        // Failure: Redirect back with an error message
        $_SESSION['error_message'] = "Failed to update application status: " . $mysqli->error;
        header("Location: view_applicant_details.php?student_id=" . $student_id . "&posting_id=" . $posting_id);
        exit;
    }
} else {
    $mysqli->close();
    $_SESSION['error_message'] = "Database preparation error: " . $mysqli->error;
    header("Location: view_applicant_details.php?student_id=" . $student_id . "&posting_id=" . $posting_id);
    exit;
}
?>