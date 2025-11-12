<?php
session_start();
require_once 'db_connect.php'; // Ensure your database connection file is included
require_once 'activity_logger.php'; // NEW: Include the logging utility

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html"); 
    exit;
}

// 2. Check for POST request and required fields
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['company_id'])) {
    header("Location: companyprofile.php?status=error&msg=Invalid request method.");
    exit;
}

// 3. Sanitize and validate input
$company_id = (int)$_POST['company_id'];
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'unknown_company_user';

// Crucial Security Check: Verify that the submitted company_id matches the logged-in user's company_id
$stmt_verify = $mysqli->prepare("SELECT company_id, company_name FROM company WHERE user_id = ? AND company_id = ?");
$company_name_before = 'Unknown Company'; // Initialize variable for logging
if ($stmt_verify) {
    $stmt_verify->bind_param('ii', $user_id, $company_id);
    $stmt_verify->execute();
    $result_verify = $stmt_verify->get_result();
    if ($result_verify->num_rows === 0) {
        header("Location: companyprofile.php?status=error&msg=Security violation: Profile ID mismatch.");
        exit;
    }
    $company_data = $result_verify->fetch_assoc();
    $company_name_before = htmlspecialchars($company_data['company_name']); // Get current name for logging
    $stmt_verify->close();
} else {
    header("Location: companyprofile.php?status=error&msg=Database error on verification.");
    exit;
}

// Get and clean the posted data
$company_name = trim($_POST['company_name']);
$industry = trim($_POST['industry']);
$address = trim($_POST['address']);
$contact_person = trim($_POST['contact_person']);
$email = trim($_POST['email']);
$phone_number = trim($_POST['phone_number'] ?? '');
$description = trim($_POST['description'] ?? '');

// Basic validation
if (empty($company_name) || empty($industry) || empty($email) || empty($contact_person) || empty($address)) {
    header("Location: companyprofile.php?status=error&msg=" . urlencode("Company Name, Industry, Address, Contact Person, and Email are required."));
    exit;
}

// 4. Update the database
$query = "
    UPDATE company 
    SET 
        company_name = ?, 
        industry = ?, 
        address = ?, 
        contact_person = ?, 
        email = ?, 
        phone_number = ?, 
        description = ?
    WHERE 
        company_id = ?
";

$stmt = $mysqli->prepare($query);

if ($stmt) {
    $stmt->bind_param(
        'sssssssi', // s=string, i=integer
        $company_name, 
        $industry, 
        $address, 
        $contact_person, 
        $email, 
        $phone_number, 
        $description, 
        $company_id
    );

    if ($stmt->execute()) {
        
        if ($stmt->affected_rows > 0) {
            $message = "Your company profile has been successfully updated!";
            
            // --- START LOGGING ---
            $safe_new_name = htmlspecialchars($company_name);
            $log_message = "Company **{$company_name_before}** (User: **{$username}**) successfully updated its profile information. New Name: **{$safe_new_name}**.";
            log_activity($mysqli, $log_message);
            // --- END LOGGING ---
            
        } else {
            $message = "No changes were made to your profile.";
        }
            
        $stmt->close();
        $mysqli->close();
        
        // Success redirect
        header("Location: companyprofile.php?status=success&msg=" . urlencode($message));
        exit;

    } else {
        $error = "Failed to update profile: " . $stmt->error;
        $stmt->close();
        $mysqli->close();
        header("Location: companyprofile.php?status=error&msg=" . urlencode($error));
        exit;
    }
} else {
    $error = "Database preparation error: " . $mysqli->error;
    $mysqli->close();
    header("Location: companyprofile.php?status=error&msg=" . urlencode($error));
    exit;
}
?>