<?php
session_start();
require_once 'db_connect.php'; 
require_once 'activity_logger.php'; // NEW: Include the logging utility

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: signinstudent.html"); 
    exit;
}

// 2. Check for POST request and required fields
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['student_id'])) {
    header("Location: manage_requirements.php?status=error&msg=Invalid request method.");
    exit;
}

// 3. Sanitize and validate input
$student_id = (int)$_POST['student_id'];
$username = $_SESSION['username'] ?? 'unknown_user'; // Get username for the log

// Crucial: Verify that the submitted student_id matches the logged-in user's student_id
$user_id = $_SESSION['user_id'];
$stmt_verify = $mysqli->prepare("SELECT student_id, first_name, last_name FROM student WHERE user_id = ? AND student_id = ?");
if ($stmt_verify) {
    $stmt_verify->bind_param('ii', $user_id, $student_id);
    $stmt_verify->execute();
    $result_verify = $stmt_verify->get_result();
    if ($result_verify->num_rows === 0) {
        header("Location: manage_requirements.php?status=error&msg=Security violation: Profile ID mismatch.");
        exit;
    }
    $student_data = $result_verify->fetch_assoc();
    $existing_fullname = htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']);
    $stmt_verify->close();
} else {
    header("Location: manage_requirements.php?status=error&msg=Database error on verification.");
    exit;
}

// Get and clean the posted data
$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$email = trim($_POST['email']);
$phone_number = trim($_POST['phone_number'] ?? ''); // Phone is optional
$description = trim($_POST['description'] ?? ''); // Description is optional

// Basic validation
if (empty($first_name) || empty($last_name) || empty($email)) {
    header("Location: manage_requirements.php?status=error&msg=" . urlencode("First Name, Last Name, and Email are required."));
    exit;
}

// 4. Update the database
$query = "
    UPDATE student 
    SET 
        first_name = ?, 
        last_name = ?, 
        email = ?, 
        phone_number = ?, 
        description = ?
    WHERE 
        student_id = ?
";

$stmt = $mysqli->prepare($query);

if ($stmt) {
    $stmt->bind_param(
        'sssssi', // s=string, i=integer
        $first_name, 
        $last_name, 
        $email, 
        $phone_number, 
        $description, 
        $student_id
    );

    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $message = ($affected_rows > 0) 
            ? "Your profile has been successfully updated!" 
            : "No changes were made to your profile.";
            
        // --- START LOGGING ---
        if ($affected_rows > 0) {
            $log_message = "Student **{$existing_fullname}** (User: **{$username}**) successfully **updated their personal profile** details.";
            log_activity($mysqli, $log_message);
        }
        // --- END LOGGING ---

        $stmt->close();
        $mysqli->close();
        
        // Success redirect
        header("Location: manage_requirements.php?status=success&msg=" . urlencode($message));
        exit;

    } else {
        $error = "Failed to update profile: " . $stmt->error;
        $stmt->close();
        $mysqli->close();
        header("Location: manage_requirements.php?status=error&msg=" . urlencode($error));
        exit;
    }
} else {
    $error = "Database preparation error: " . $mysqli->error;
    $mysqli->close();
    header("Location: manage_requirements.php?status=error&msg=" . urlencode($error));
    exit;
}
?>