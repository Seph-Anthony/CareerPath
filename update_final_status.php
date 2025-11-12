<?php
// update_final_status.php - Coordinator sets the final OJT result (Passed/Failed)

session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: signincoordinator.html"); 
    exit;
}

// 2. Collect & Validate Inputs
$application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
$final_ojt_status = isset($_POST['final_ojt_status']) ? $_POST['final_ojt_status'] : '';

// Validation
if ($application_id === 0 || !in_array($final_ojt_status, ['Passed', 'Failed'])) {
    $msg = "Error: Invalid input or missing application ID.";
    header("Location: manage_evaluations.php?status=error&msg=" . urlencode($msg));
    exit;
}

// 3. Update the intern_application status
$query = "UPDATE intern_application SET status = ? WHERE application_id = ?";
$stmt = $mysqli->prepare($query);

if ($stmt) {
    $stmt->bind_param('si', $final_ojt_status, $application_id);
    
    if ($stmt->execute()) {
        $msg = "Success: The student's OJT has been officially finalized as **{$final_ojt_status}**.";
        $status = 'success';
    } else {
        $msg = "Error updating application status: " . $stmt->error;
        $status = 'error';
    }
    $stmt->close();
} else {
    $msg = "Database error during preparation: " . $mysqli->error;
    $status = 'error';
}

$mysqli->close();

// 4. Redirect
header("Location: manage_evaluations.php?status={$status}&msg=" . urlencode($msg));
exit;
?>