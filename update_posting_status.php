<?php
// update_posting_status.php
session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: signincoordinator.html"); 
    exit;
}

// 2. Collect Inputs
$posting_id = isset($_POST['posting_id']) ? intval($_POST['posting_id']) : 0;
$new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';

if ($posting_id === 0 || empty($new_status)) {
    die("<script>alert('Invalid data provided for status update.'); window.location.href='manage_placements.php';</script>");
}

// 3. Update the intern_posting table
// Note: We don't need to check the role here since only the coordinator can access this script.
$query = "UPDATE intern_posting SET status = ? WHERE posting_id = ?";
$stmt = $mysqli->prepare($query);

if ($stmt) {
    $stmt->bind_param('si', $new_status, $posting_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        $mysqli->close();
        
        die("<script>
                alert('OJT Posting status successfully updated to " . htmlspecialchars($new_status) . ".'); 
                window.location.href='view_placement_coordinator.php?posting_id=" . $posting_id . "';
             </script>");
    } else {
        $stmt->close();
        $mysqli->close();
        die("<script>alert('Failed to update posting status: " . $mysqli->error . "'); window.history.back();</script>");
    }
} else {
    $mysqli->close();
    die("<script>alert('Database preparation error: " . $mysqli->error . "'); window.history.back();</script>");
}
?>