<?php
// student_signup.php
session_start();
require_once 'db_connect.php'; 
require_once 'activity_logger.php'; // NEW: Include the logging utility

// --- Helper Function ---
function handleError(mysqli $mysqli, $message) {
    // Attempt to roll back if the connection is still alive, ensuring data integrity.
    if ($mysqli->ping()) {
        if ($mysqli->begin_transaction()) {
            $mysqli->rollback();
        }
        $mysqli->close();
    }
    
    die("<script>
            alert('" . addslashes($message) . "'); 
            window.history.back();
           </script>");
}

// --- Input Collection and Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleError($mysqli, 'Invalid request method.');
}

// Collect and trim inputs for Student Table
$first_name     = isset($_POST['first_name'])    ? trim($_POST['first_name'])     : '';
$last_name      = isset($_POST['last_name'])     ? trim($_POST['last_name'])      : '';
$course         = isset($_POST['course'])        ? trim($_POST['course'])         : '';
$year_level     = isset($_POST['year_level'])    ? trim($_POST['year_level'])     : '';
$description    = isset($_POST['description'])   ? trim($_POST['description'])    : NULL;
$status         = isset($_POST['status'])        ? trim($_POST['status'])         : 'Active'; 

// Collect and trim inputs for Users Table & Student Contact Info
$email          = isset($_POST['email'])         ? trim($_POST['email'])          : '';
$phone_number   = isset($_POST['contact'])       ? trim($_POST['contact'])        : '';
$username       = isset($_POST['username'])      ? trim($_POST['username'])       : '';
$password       = isset($_POST['password'])      ? $_POST['password']             : '';
$role           = isset($_POST['role'])          ? trim($_POST['role'])           : 'student';


if (empty($username) || empty($password) || empty($first_name) || empty($email) || empty($phone_number)) {
    handleError($mysqli, 'All required fields must be filled out, including contact info.');
}

// Hash the password for security
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// --- 1. Start Transaction ---
$mysqli->begin_transaction();

try {
    // --- 2. Insert into users table (CLEANED: Only login details) ---
    $insertUser = $mysqli->prepare("
        INSERT INTO users (username, password, role, status) 
        VALUES (?, ?, ?, ?)
    ");
    
    $insertUser->bind_param('ssss', $username, $hashed_password, $role, $status);
    $ok1 = $insertUser->execute();

    if (!$ok1) {
        throw new Exception("Error saving user credentials: " . $mysqli->error);
    }

    $user_id = $insertUser->insert_id;
    $insertUser->close();
    
    // --- 3. Insert into student table (UPDATED: Now includes email and phone_number) ---
    $insertStudent = $mysqli->prepare("
        INSERT INTO student (user_id, first_name, last_name, course, year_level, email, phone_number, description, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // The types: i, s, s, s, s, s, s, s, s (9 parameters)
    $insertStudent->bind_param('issssssss', $user_id, $first_name, $last_name, $course, $year_level, $email, $phone_number, $description, $status);
    $ok2 = $insertStudent->execute();

    $insertStudent->close();

    if (!$ok2) {
        throw new Exception("Error saving student info: " . $mysqli->error);
    }

    // 4. Both queries succeeded, commit the transaction
    $mysqli->commit();
    
    // --- NEW: Dynamic Activity Logging (Must be AFTER commit) ---
    $full_name = htmlspecialchars($first_name . ' ' . $last_name);
    $log_message = "A new student, **$full_name** (Username: **$username**), successfully registered.";
    log_activity($mysqli, $log_message); 
    
    $mysqli->close(); // Close connection after everything is done

    // Success message and redirect
    die("<script>
            alert('Student registration successful! You can now sign in.');
            window.location.href = 'signinstudent.html';
         </script>");

} catch (Exception $e) {
    // 5. Something failed, roll back all changes
    $mysqli->rollback();
    
    $error_msg = $e->getMessage();
    
    if (strpos($error_msg, 'Duplicate entry') !== false && strpos($error_msg, 'username') !== false) {
        $user_friendly_msg = 'That username is already taken. Please choose another one.';
    } else {
        error_log("Student Signup Error: " . $error_msg);
        $user_friendly_msg = 'An error occurred during registration. Please try again. (Technical error logged.)';
    }

    // Final alert and redirect
    handleError($mysqli, $user_friendly_msg);
}
?>