<?php
// process_post.php
session_start();
require_once 'db_connect.php'; 

// --- Helper Function for Errors ---
function abort_with($msg, $mysqli) {
    if ($mysqli->ping()) {
        $mysqli->close();
    }
    // Redirects back to the posting page to ensure they can try again
    die("<script>alert('" . addslashes($msg) . "'); window.location.href='post_internship.php';</script>");
}

// --- 1. Security Checks ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    abort_with('Invalid request method.', $mysqli);
}
if ($_SESSION['role'] !== 'company' || !isset($_SESSION['user_id'])) {
    header("Location: signincompany.html");
    exit;
}

// --- 2. Input Collection and Validation ---
$title           = trim($_POST['title'] ?? '');
$description     = trim($_POST['description'] ?? '');
$requirements    = trim($_POST['requirements'] ?? '');
$slot_available  = intval($_POST['slot_available'] ?? 0);
$status          = 'Active'; // Default status for new posts

if ($title === '' || $description === '' || $requirements === '' || $slot_available < 1) {
    abort_with('Please fill in all required fields and ensure slots are greater than 0.', $mysqli);
}

// --- 3. Find the Company's ID (Foreign Key) ---
$user_id = $_SESSION['user_id'];
$company_id = null;

// The company_id is needed to link the post to the correct company
$stmt_company = $mysqli->prepare("SELECT company_id FROM company WHERE user_id = ? LIMIT 1");
if ($stmt_company) {
    $stmt_company->bind_param('i', $user_id);
    $stmt_company->execute();
    $result_company = $stmt_company->get_result();
    if ($result_company->num_rows === 1) {
        $company_data = $result_company->fetch_assoc();
        $company_id = $company_data['company_id'];
    }
    $stmt_company->close();
}

if (!$company_id) {
    abort_with('Could not identify your company profile. Please ensure your company is fully registered.', $mysqli);
}


// --- 4. Securely Insert the New Internship Post ---
// Columns: company_id, title, description, requirements, slot_available, status
$insertPost = $mysqli->prepare("
    INSERT INTO intern_posting 
    (company_id, title, description, requirements, slot_available, status) 
    VALUES (?, ?, ?, ?, ?, ?)
");

if (!$insertPost) {
    // Log a detailed error if the prepare statement failed
    error_log("MySQL Prepare Error: " . $mysqli->error);
    abort_with('A server error occurred during post creation. Please check the database.', $mysqli);
}

// Bind parameters: i for int, s for string
$insertPost->bind_param(
    'isssis', 
    $company_id, 
    $title, 
    $description, 
    $requirements, 
    $slot_available, 
    $status
);

$ok = $insertPost->execute();
$insertPost->close();
$mysqli->close();

// --- 5. Success or Failure Response ---
if ($ok) {
    die("<script>
            alert('Internship post successfully created and is now active!');
            window.location.href = 'companydashboard.php';
          </script>");
} else {
    // Execution failed
    error_log("Posting failed: " . $mysqli->error);
    abort_with('Failed to save the internship post. Please try again.', $mysqli);
}
?>