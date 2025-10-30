<?php
header("Content-Type: text/plain");
// This file assumes 'db_connect.php' defines the connection object as $mysqli
require_once 'db_connect.php'; 

// --- Input Parsing (Keeping your original method) ---
$raw = file_get_contents("php://input");
// Only parse if the raw body exists and $_POST is empty
if (!empty($raw) && empty($_POST)) {
    parse_str($raw, $_POST);
}

if (!isset($_POST['username']) || empty($_POST['username'])) {
    // If no username is provided, it's an error, not "available"
    echo "error: no username received";
    exit;
}

$username = trim($_POST['username']);

// --- Use Prepared Statements for Security ---

// 1. Prepare the SQL query to check for existing username
$query = "SELECT username FROM users WHERE username = ? LIMIT 1";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    echo "error: Database preparation error: " . $mysqli->error;
    exit;
}

// 2. Bind the user input to the placeholder. 's' means string.
$stmt->bind_param('s', $username);

// 3. Execute and store the results
$stmt->execute();
$stmt->store_result(); 

// 4. Check if any rows were found
if ($stmt->num_rows > 0) {
    echo "taken"; // Username found, so it is taken
} else {
    echo "available"; // Username not found, so it is available
}

$stmt->close();
$mysqli->close(); // Close the connection after use
?>