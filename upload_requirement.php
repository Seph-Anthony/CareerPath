<?php
// upload_requirement.php - Processes the file upload for student requirements
session_start();
require_once 'db_connect.php'; // Your database connection file
require_once 'activity_logger.php'; // NEW: Include the logging utility

// --- 1. Authentication and Authorization Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: signinstudent.html"); 
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['upload_document'])) {
    header("Location: manage_requirements.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'unknown_user'; // Get username for the log
$student_id = null;
$fullname = 'Unknown Student'; // Default name for logging if not found
$error = "";
$message = "";

// --- 2. Fetch Student ID and Name ---
$stmt_student = $mysqli->prepare("SELECT student_id, first_name, last_name FROM student WHERE user_id = ? LIMIT 1");

if ($stmt_student) {
    $stmt_student->bind_param('i', $user_id);
    $stmt_student->execute();
    $result_student = $stmt_student->get_result();
    
    if ($result_student->num_rows === 1) {
        $student_data = $result_student->fetch_assoc();
        $student_id = $student_data['student_id'];
        $fullname = htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']);
    } else {
        $error = "Student ID not found.";
    }
    $stmt_student->close();
} else {
    $error = "Database error fetching profile: " . $mysqli->error;
}

// Proceed only if student_id is valid
if ($student_id && empty($error)) {

    // --- 3. Gather POST Data ---
    $document_type = 'Information'; 
    
    // --- 4. File Upload and Validation ---
    if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
        // Handle common file upload errors
        $php_error_code = $_FILES['document_file']['error'] ?? 'N/A';
        switch ($php_error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error = "The uploaded file exceeds the maximum file size limit.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $error = "No file was selected for upload.";
                break;
            default:
                $error = "File upload failed with error code: {$php_error_code}.";
        }
    } else {
        
        $file = $_FILES['document_file'];
        $file_name_original = $file['name'];
        $file_tmp_path = $file['tmp_name'];
        $file_size = $file['size'];
        $file_ext = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'doc', 'docx'];
        $max_file_size = 5 * 1024 * 1024; // 5MB

        // File Type Validation
        if (!in_array($file_ext, $allowed_extensions)) {
            $error = "Invalid file type. Only PDF, DOC, or DOCX are allowed.";
        } 
        // File Size Validation
        elseif ($file_size > $max_file_size) {
            $error = "File size exceeds 5MB limit.";
        } 
        // Final Processing
        else {
            // Define the secure upload directory
            $upload_dir = 'uploads/requirements/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true); 
            }
            
            // Create a unique name: studentID_timestamp_DocumentType.ext
            $safe_type = 'Information'; 
            $new_file_name = $student_id . '_' . time() . '_' . $safe_type . '.' . $file_ext;
            $file_path_db = $upload_dir . $new_file_name; // Path to store in DB

            // Move the file
            if (move_uploaded_file($file_tmp_path, $file_path_db)) {
                
                // --- 5. Insert Record into Database ---
                $stmt_insert = $mysqli->prepare("
                    INSERT INTO student_requirements 
                        (student_id, document_type, file_path, file_name, approval_status, upload_date) 
                    VALUES 
                        (?, ?, ?, ?, ?, NOW()) 
                ");

                if ($stmt_insert) {
                    $initial_status = 'Pending';
                    $stmt_insert->bind_param('issss', 
                        $student_id, 
                        $document_type, // Will be 'Information'
                        $file_path_db, 
                        $file_name_original,
                        $initial_status
                    );
                    
                    if ($stmt_insert->execute()) {
                        
                        // --- START LOGGING ---
                        $safe_file_name = htmlspecialchars($file_name_original);
                        $log_message = "Student **{$fullname}** (User: **{$username}**) successfully **uploaded a requirement**: **$document_type** (File: **$safe_file_name**).";
                        log_activity($mysqli, $log_message);
                        // --- END LOGGING ---

                        $message = "Your **" . htmlspecialchars($document_type) . "** document has been uploaded successfully and is awaiting review.";
                    } else {
                        $error = "Database insertion failed: " . $mysqli->error;
                        // Clean up: delete the file if DB insertion fails
                        unlink($file_path_db); 
                    }
                    $stmt_insert->close();

                } else {
                    $error = "Database statement preparation failed: " . $mysqli->error;
                }
            } else {
                $error = "Failed to move the uploaded file. Check the web server's permissions on the `uploads/requirements/` folder.";
            }
        }
    }
}

$mysqli->close();

// --- 6. Redirect Back to Requirements Page with Status ---
if (!empty($error)) {
    header("Location: manage_requirements.php?status=error&msg=" . urlencode($error));
} else {
    header("Location: manage_requirements.php?status=success&msg=" . urlencode($message));
}

exit;
?>