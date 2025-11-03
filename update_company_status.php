<?php
// update_company_status.php
session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: signincoordinator.html"); 
    exit;
}

// 2. Collect Inputs
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';

if ($user_id === 0 || empty($new_status)) {
    die("<script>alert('Invalid data provided for status update.'); window.location.href='manage_companies.php';</script>");
}

// 3. Update the Users table
$query = "UPDATE users SET status = ? WHERE user_id = ? AND role = 'company'";
$stmt = $mysqli->prepare($query);

if ($stmt) {
    $stmt->bind_param('si', $new_status, $user_id);
    
    if ($stmt->execute()) {
        // Find the company_id to redirect back to the correct profile page
        $company_id_query = $mysqli->prepare("SELECT company_id FROM company WHERE user_id = ?");
        $company_id_query->bind_param('i', $user_id);
        $company_id_query->execute();
        $result = $company_id_query->get_result();
        $company_data = $result->fetch_assoc();
        $stmt->close();
        $company_id_query->close();
        
        $redirect_id = $company_data['company_id'] ?? 0;

        die("<script>
                alert('Company account status successfully updated to " . htmlspecialchars($new_status) . ".'); 
                window.location.href='view_company_coordinator.php?company_id=" . $redirect_id . "';
             </script>");
    } else {
        $stmt->close();
        die("<script>alert('Failed to update company status: " . $mysqli->error . "'); window.history.back();</script>");
    }
} else {
    die("<script>alert('Database preparation error: " . $mysqli->error . "'); window.history.back();</script>");
}
?>