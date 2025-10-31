<?php
// coordinatordashboard.php
require_once 'auth_check.php'; 

// 2. Check if the logged-in user has the correct role ('coordinator')
if ($_SESSION['role'] !== 'coordinator') {
    // If the user is logged in but is not a coordinator, deny access.
    header("Location: index.html"); 
    exit;
}

// If they pass both checks, the rest of the HTML/PHP will load
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Dashboard</title>
</head>
<body>
    <h1>Welcome, OJT Coordinator!</h1>
    <p>You have successfully logged in to the coordinator management area.</p>
    <a href="logout.php">Logout</a>
</body>
</html>