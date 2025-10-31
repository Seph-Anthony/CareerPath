<?php
// student_signin.php
session_start();
require_once 'db_connect.php'; // Includes your $mysqli connection

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
// We fetch the hashed password, role, status, and user_id for verification and session setup.
$stmt = $mysqli->prepare("SELECT user_id, password, role, status FROM users WHERE username = ? LIMIT 1");

if (!$stmt) {
    // Log the error in production
    login_failure('A server error occurred. Please try again.');
}

$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    // Use a vague message to prevent user enumeration attacks
    login_failure('Invalid username or password.'); 
}

$user = $result->fetch_assoc();
$stmt->close();

// 3. Verify password and role
$hashed_password = $user['password'];
$user_role = $user['role'];
$user_status = $user['status'];

// Check if the hashed password matches the submitted password
if (password_verify($password, $hashed_password)) {
    
    // Crucial: Check if the user is a 'student'
    if ($user_role !== 'student') {
        login_failure('Your account role is not student. Please use the correct sign in page.');
    }
    
    // Check account status
    if ($user_status !== 'Active') {
        login_failure('Your account is currently ' . $user_status . '. Please contact the coordinator for assistance.');
    }

    // 4. Successful Login: Set Session Variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $user_role;
    $_SESSION['logged_in'] = true;

    // 5. Redirect to Student Dashboard
    // Use header() redirect for cleaner navigation
    header("Location: studentdashboard.html");
    exit;

} else {
    // Password verification failed
    login_failure('Invalid username or password.');
}
?>