<?php
// student_signin.php - Handles student authentication and logs successful sign-ins

session_start();
require_once 'db_connect.php'; // Includes your $mysqli connection
require_once 'activity_logger.php'; // NEW: Include the logging utility

// Helper function for login failure
function login_failure($msg) {
    // We redirect back to the sign-in page with an alert.
    echo "<script>alert('" . addslashes($msg) . "'); window.location.href = 'signinstudent.html';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    login_failure('Invalid request method.');
}

// 1. Collect and sanitize inputs
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($username === '' || $password === '') {
    login_failure('Please enter both username and password.');
}

// 2. Prepare and execute query to fetch user data
$stmt = $mysqli->prepare("SELECT user_id, password, role, status FROM users WHERE username = ? LIMIT 1");

if (!$stmt) {
    error_log('Student sign-in prepared statement failed: ' . $mysqli->error);
    login_failure('A server error occurred. Please try again.');
}

$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    login_failure('Invalid username or password.'); 
}

$user = $result->fetch_assoc();
$stmt->close();

// 3. Verify password and role
$user_id = $user['user_id'];
$hashed_password = $user['password'];
$user_role = $user['role'];
$user_status = $user['status'];

// Check if the hashed password matches the submitted password
if (password_verify($password, $hashed_password)) {
    
    // Crucial checks
    if ($user_role !== 'student') {
        login_failure('Your account role is not student. Please use the correct sign in page.');
    }
    
    if ($user_status !== 'Active') {
        login_failure('Your account is currently ' . $user_status . '. Please contact the coordinator for assistance.');
    }

    // 4. Successful Login: Set Session Variables
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $user_role;
    $_SESSION['logged_in'] = true;

    // --- 5. Dynamic Activity Logging ---
    // Fetch student's full name for a better log entry
    $student_name = $username;
    $stmt_name = $mysqli->prepare("SELECT first_name, last_name FROM student WHERE user_id = ? LIMIT 1");
    if ($stmt_name) {
        $stmt_name->bind_param('i', $user_id);
        $stmt_name->execute();
        $result_name = $stmt_name->get_result();
        if ($result_name->num_rows > 0) {
            $name_data = $result_name->fetch_assoc();
            $student_name = htmlspecialchars($name_data['first_name'] . ' ' . $name_data['last_name']);
        }
        $stmt_name->close();
    }

    $log_message = "Student user **$student_name** (Username: **$username**) successfully signed in.";
    log_activity($mysqli, $log_message); // Log the event!

    // 6. Redirect to Student Dashboard
    header("Location: studentdashboard.php");
    exit;

} else {
    // Password verification failed
    login_failure('Invalid username or password.');
}

// Close connection if not already closed by exit()
if ($mysqli) {
    $mysqli->close();
}
?>