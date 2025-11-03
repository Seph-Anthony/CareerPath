<?php
// signupcompany.php
// Assumes 'db_connect.php' defines the connection object as $mysqli
require_once 'db_connect.php'; 

// Function to handle errors and redirect (ensures cleanup)
function handleError(mysqli $mysqli, $message) {
    // Attempt to roll back the transaction if it was started
    if ($mysqli->ping()) {
        $mysqli->rollback();
        $mysqli->close();
    }
    
    // Output the error message and redirect
    die("<script>
            alert('" . addslashes($message) . "'); 
            window.history.back();
           </script>");
}

// --- Input Collection and Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleError($mysqli, 'Invalid request method.');
}

// Collect and trim inputs
$company_name   = isset($_POST['company_name']) ? trim($_POST['company_name']) : '';
$industry       = isset($_POST['industry_type']) ? trim($_POST['industry_type']) : '';
$address        = isset($_POST['address']) ? trim($_POST['address']) : '';
$contact_person = isset($_POST['representative']) ? trim($_POST['representative']) : '';
$email          = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone_number   = isset($_POST['contact']) ? trim($_POST['contact']) : '';
$username       = isset($_POST['username']) ? trim($_POST['username']) : '';
$password       = isset($_POST['password']) ? $_POST['password'] : '';

$role           = "company"; // Fixed role
$status         = "Pending"; // <--- CRITICAL CHANGE: Set initial status for companies to Pending

// Simple check for required fields
if (empty($username) || empty($password) || empty($company_name) || empty($email) || empty($address) || empty($contact_person)) {
    handleError($mysqli, 'Please fill in all required fields.');
}

// 2️⃣ Hash password before storing
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// --- Database Transaction for Atomicity ---
// Turn off autocommit and begin transaction
$mysqli->autocommit(FALSE);

try {
    // 3️⃣ Insert user account into `users` table
    // MODIFICATION: Explicitly include 'status' in the columns and binding
    $insertUser = $mysqli->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, ?)");
    
    if (!$insertUser) {
        throw new Exception("Prepare statement failed (users): " . $mysqli->error);
    }
    
    // 'ssss' for 4 string parameters: username, hashed_password, role, status
    $insertUser->bind_param('ssss', $username, $hashed_password, $role, $status);
    $ok1 = $insertUser->execute();
    $user_id = $mysqli->insert_id;
    $insertUser->close();
    
    if (!$ok1) {
        throw new Exception("Failed to create user account. DB error: " . $mysqli->error);
    }
    
    // 4️⃣ Insert company info into `company` table
    $insertCompany = $mysqli->prepare("INSERT INTO company (user_id, company_name, industry, address, contact_person, email, phone_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if (!$insertCompany) {
        throw new Exception("Prepare statement failed (company): " . $mysqli->error);
    }

    // i for user_id (int), ssssss for the 6 string parameters
    $insertCompany->bind_param('issssss', $user_id, $company_name, $industry, $address, $contact_person, $email, $phone_number);
    $ok2 = $insertCompany->execute();

    $insertCompany->close();

    if (!$ok2) {
        throw new Exception("Error saving company info: " . $mysqli->error);
    }

    // 5️⃣ Both queries succeeded, commit the transaction
    $mysqli->commit();
    $mysqli->close();

    // Success message and redirect (Updated message to reflect Pending status)
    die("<script>
            alert('Company registration successful! Your account is pending review by the Coordinator. You will be able to sign in once approved.');
            window.location.href = 'signincompany.html';
         </script>");

} catch (Exception $e) {
    // 6️⃣ Something failed, roll back all changes
    $error_msg = $e->getMessage();
    
    // Handle specific error types
    if (strpos($error_msg, 'Duplicate entry') !== false && strpos($error_msg, 'username') !== false) {
        $user_friendly_msg = 'That username is already taken. Please choose another one.';
    } else {
        // Fallback friendly message (technical error logged to console)
        $user_friendly_msg = 'An error occurred during registration. Please try again.';
    }

    // Use console to log the technical error for your debugging
    echo "<script>console.error('Registration Error:', '" . addslashes($error_msg) . "');</script>";

    handleError($mysqli, $user_friendly_msg);
}

// Close connection if not already closed
$mysqli->close();
?>