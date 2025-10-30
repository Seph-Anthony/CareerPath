<?php
// student_signup.php
session_start();
require_once 'db_connect.php'; // adjust path if necessary

function abort_with($msg) {
    $_SESSION['signup_error'] = $msg;
    header("Location: signupstudent.html");
    exit;
}

// Ensure request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    abort_with('Invalid request method.');
}

// Collect and trim inputs
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$last_name  = isset($_POST['last_name'])  ? trim($_POST['last_name'])  : '';
$course     = isset($_POST['course'])     ? trim($_POST['course'])     : '';
$year_level = isset($_POST['year_level']) ? trim($_POST['year_level']) : '';
$description= isset($_POST['description'])? trim($_POST['description']): '';
$username   = isset($_POST['username'])   ? trim($_POST['username'])   : '';
$password   = isset($_POST['password'])   ? $_POST['password']         : '';
$confirm    = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

// Basic validation
if ($first_name === '' || $last_name === '' || $course === '' || $year_level === '' || $username === '' || $password === '' || $confirm === '') {
    abort_with('Please fill in all required fields.');
}

if ($password !== $confirm) {
    abort_with('Passwords do not match.');
}

if (strlen($username) < 3 || strlen($username) > 50) {
    abort_with('Username must be between 3 and 50 characters.');
}

if (strlen($password) < 6) {
    abort_with('Password must be at least 6 characters.');
}

// SECURITY: Force role to 'student' (do not trust client)
$role = 'student';

// 1) Check duplicate username in users table
$stmt = $mysqli->prepare("SELECT user_id FROM users WHERE username = ?");
if (!$stmt) {
    abort_with('Database error (prepare).');
}
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    abort_with('Username already exists. Please choose another username.');
}
$stmt->close();

// 2) Insert into users table (hash the password)
$hashed = password_hash($password, PASSWORD_DEFAULT);
$insertUser = $mysqli->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
if (!$insertUser) {
    abort_with('Database error (prepare insert user).');
}
$insertUser->bind_param('sss', $username, $hashed, $role);
$ok = $insertUser->execute();
if (!$ok) {
    // possible duplicate or DB error
    $err = $insertUser->error;
    $insertUser->close();
    abort_with('Failed to create user account. DB error: ' . htmlspecialchars($err));
}
$user_id = $insertUser->insert_id;
$insertUser->close();

// 3) Insert into student table (linking user_id)
$insertStudent = $mysqli->prepare("INSERT INTO student (user_id, first_name, last_name, course, year_level, description, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
if (!$insertStudent) {
    // rollback remove user? (optional)
    // remove user we just created to keep DB consistent
    $mysqli->query("DELETE FROM users WHERE user_id = " . intval($user_id));
    abort_with('Database error (prepare insert student).');
}
$status = 'Active'; // default
$insertStudent->bind_param('issssss', $user_id, $first_name, $last_name, $course, $year_level, $description, $status);
$ok2 = $insertStudent->execute();
if (!$ok2) {
    // cleanup inserted user in case of failure
    $insertStudent->close();
    $mysqli->query("DELETE FROM users WHERE user_id = " . intval($user_id));
    abort_with('Failed to create student profile. DB error: ' . htmlspecialchars($insertStudent->error));
}
$insertStudent->close();

// Success: redirect to sign in (or show success)
header('Location: signinstudent.html?signup=success');
exit;
