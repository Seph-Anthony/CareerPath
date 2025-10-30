<?php
// coordinator_signup.php
session_start();
require_once 'db_connect.php'; // Ensures connection to $mysqli

// Helper function for error handling
function abort_with($msg) {
    // We'll use a simple alert/redirect for production, 
    // but in a real app you'd log the error and use a session variable.
    echo "<script>alert('ERROR: " . addslashes($msg) . "'); window.history.back();</script>";
    exit;
}

// Ensure request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    abort_with('Invalid request method.');
}

// 1. Collect and trim all inputs (Including new fields: employee_id, department)
$full_name   = isset($_POST['name'])           ? trim($_POST['name']) : '';
$position    = isset($_POST['position'])       ? trim($_POST['position']) : '';
$employee_id = isset($_POST['employee_id'])    ? trim($_POST['employee_id']) : '';
$department  = isset($_POST['department'])     ? trim($_POST['department']) : '';
$email       = isset($_POST['email'])          ? trim($_POST['email']) : '';
$contact     = isset($_POST['contact'])        ? trim($_POST['contact']) : '';
$username    = isset($_POST['username'])       ? trim($_POST['username']) : '';
$password    = isset($_POST['password'])       ? $_POST['password'] : '';
$confirm     = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
$role        = isset($_POST['role'])           ? trim($_POST['role']) : 'coordinator';
$status      = isset($_POST['status'])         ? trim($_POST['status']) : 'Active';

// 2. Basic Server-Side Validation
if ($full_name === '' || $position === '' || $employee_id === '' || $department === '' || 
    $email === '' || $contact === '' || $username === '' || $password === '' || $confirm === '') {
    abort_with('Please fill in all required fields.');
}

if ($password !== $confirm) {
    abort_with('Passwords do not match.');
}

// Secure password check (optional, but good practice if not fully covered by JS)
if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password) || !preg_match('/[@$!%*?&]/', $password)) {
    abort_with('Password must be at least 8 characters long and include one letter, one number, and one special character.');
}

// 3. Check for Duplicate Username (Final Server-Side Check)
$checkUser = $mysqli->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
if (!$checkUser) {
    abort_with('Database error (prepare check user).');
}
$checkUser->bind_param('s', $username);
$checkUser->execute();
$checkUser->store_result();
if ($checkUser->num_rows > 0) {
    $checkUser->close();
    abort_with('That username is already taken. Please choose another one.');
}
$checkUser->close();

// 4. Hash password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Begin Transaction for Data Integrity
$mysqli->begin_transaction();
try {
    // 5. Insert user account into 'users' table
    $insertUser = $mysqli->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, ?)");
    if (!$insertUser) {
        throw new Exception('Failed to prepare user insert.');
    }
    $insertUser->bind_param('ssss', $username, $hashed, $role, $status);
    if (!$insertUser->execute()) {
        throw new Exception('Failed to execute user insert.');
    }
    $user_id = $insertUser->insert_id;
    $insertUser->close();

    // 6. Insert coordinator details into 'coordinator' table
    // ASSUMING TABLE COLUMNS: full_name, employee_id, position, department, email, contact_number
    $insertCoordinator = $mysqli->prepare("INSERT INTO coordinator (user_id, full_name, employee_id, position, department, email, contact_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$insertCoordinator) {
        throw new Exception('Failed to prepare coordinator insert. Check table schema.');
    }

    $insertCoordinator->bind_param('issssss', $user_id, $full_name, $employee_id, $position, $department, $email, $contact);
    
    if (!$insertCoordinator->execute()) {
        throw new Exception('Failed to execute coordinator insert. Check table columns vs. script variables.');
    }
    $insertCoordinator->close();

    // Commit transaction if all inserts succeed
    $mysqli->commit();

    // Success message and redirect
    echo "<script>
            alert('Coordinator registration successful! You can now sign in.');
            window.location.href = 'signincoordinator.html';
          </script>";

} catch (Exception $e) {
    // Rollback transaction on any error
    $mysqli->rollback();
    abort_with('Registration failed due to a server error. Please try again. (' . $e->getMessage() . ')');
}
?>