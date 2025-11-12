<?php
session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: signincoordinator.html"); 
    exit;
}

// 2. Check for POST request and required fields
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['coordinator_id'])) {
    header("Location: coordinatordashboard.php?status=error&msg=Invalid request method.");
    exit;
}

// 3. Sanitize and validate input
$coordinator_id = (int)$_POST['coordinator_id'];
$user_id = $_SESSION['user_id'];

// Security Check: Verify that the submitted coordinator_id matches the logged-in user's
$stmt_verify = $mysqli->prepare("SELECT coordinator_id FROM coordinator WHERE user_id = ? AND coordinator_id = ?");
if ($stmt_verify) {
    $stmt_verify->bind_param('ii', $user_id, $coordinator_id);
    $stmt_verify->execute();
    $result_verify = $stmt_verify->get_result();
    if ($result_verify->num_rows === 0) {
        header("Location: coordinatordashboard.php?status=error&msg=Security violation: Profile ID mismatch.");
        exit;
    }
    $stmt_verify->close();
} else {
    header("Location: coordinatordashboard.php?status=error&msg=Database error on verification.");
    exit;
}

// Get and clean the posted data
$full_name = trim($_POST['full_name']);
$department = trim($_POST['department']);
$position = trim($_POST['position']);
$email = trim($_POST['email']);
$contact_number = trim($_POST['contact_number'] ?? ''); // Optional

// Basic validation
if (empty($full_name) || empty($department) || empty($position) || empty($email)) {
    header("Location: coordinatordashboard.php?status=error&msg=" . urlencode("Full Name, Department, Position, and Email are required."));
    exit;
}

// 4. Update the database
$query = "
    UPDATE coordinator 
    SET 
        full_name = ?, 
        department = ?, 
        position = ?, 
        email = ?, 
        contact_number = ?
    WHERE 
        coordinator_id = ?
";

$stmt = $mysqli->prepare($query);

if ($stmt) {
    $stmt->bind_param(
        'sssssi', // s=string, i=integer
        $full_name, 
        $department, 
        $position, 
        $email, 
        $contact_number, 
        $coordinator_id
    );

    if ($stmt->execute()) {
        $message = ($stmt->affected_rows > 0) 
            ? "Your profile has been successfully updated!" 
            : "No changes were made to your profile.";
            
        $stmt->close();
        $mysqli->close();
        
        // Success redirect
        header("Location: coordinatordashboard.php?status=success&msg=" . urlencode($message));
        exit;

    } else {
        $error = "Failed to update profile: " . $stmt->error;
        $stmt->close();
        $mysqli->close();
        header("Location: coordinatordashboard.php?status=error&msg=" . urlencode($error));
        exit;
    }
} else {
    $error = "Database preparation error: " . $mysqli->error;
    $mysqli->close();
    header("Location: coordinatordashboard.php?status=error&msg=" . urlencode($error));
    exit;
}
?>