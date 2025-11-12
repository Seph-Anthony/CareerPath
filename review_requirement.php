<?php
// review_requirement.php - Allows coordinator to approve/reject a student requirement
session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: signincoordinator.html"); 
    exit;
}

$error = "";
$message = "";
$redirect_to = "coordinatordashboard.php"; // Default fallback redirect location

// --- Handle Status Update POST Request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['requirement_id'])) {
    
    $req_id = intval($_POST['requirement_id']);
    $new_status = ($_POST['action'] === 'approve') ? 'Approved' : 'Rejected';
    
    // Determine where to redirect back after processing (uses student_id from the form)
    if (isset($_POST['student_id'])) {
        $student_id = intval($_POST['student_id']);
        $redirect_to = "view_student_coordinator.php?student_id=" . $student_id;
    } 

    // Fetch coordinator_id
    $coordinator_id = null;
    $stmt_coord_id = $mysqli->prepare("SELECT coordinator_id FROM coordinator WHERE user_id = ? LIMIT 1");
    if ($stmt_coord_id) {
        $stmt_coord_id->bind_param('i', $_SESSION['user_id']);
        $stmt_coord_id->execute();
        $result_coord_id = $stmt_coord_id->get_result();
        if ($result_coord_id->num_rows > 0) {
            $coordinator_id = $result_coord_id->fetch_assoc()['coordinator_id'];
        }
        $stmt_coord_id->close();
    }


    if ($coordinator_id && $req_id > 0) {
        // Update the status in the database
        $stmt_update = $mysqli->prepare("
            UPDATE 
                student_requirements 
            SET 
                approval_status = ?, 
                coordinator_id = ?, 
                review_date = NOW()
            WHERE 
                requirement_id = ?
        ");
        
        if ($stmt_update) {
            $stmt_update->bind_param('sii', $new_status, $coordinator_id, $req_id);
            
            if ($stmt_update->execute()) {
                $message = "Requirement ID **#{$req_id}** status updated to **{$new_status}**.";
                header("Location: {$redirect_to}&status=success&msg=" . urlencode($message));
                exit;
            } else {
                $error = "Database update failed: " . $mysqli->error;
            }
            $stmt_update->close();

        } else {
            $error = "Database statement preparation failed: " . $mysqli->error;
        }
    } else {
        $error = "Error: Coordinator ID not found or invalid requirement ID.";
    }

    // If an error occurred during POST handling, redirect with error
    header("Location: {$redirect_to}&status=error&msg=" . urlencode($error));
    exit;
}


// --- Handle Non-POST/Invalid Access ---
// Redirect users who try to access this file directly without a POST submission
header("Location: coordinatordashboard.php?status=error&msg=" . urlencode("Invalid access method. Document status must be updated from the student profile page."));
exit;
?>