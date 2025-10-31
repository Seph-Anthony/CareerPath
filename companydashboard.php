<?php
// companydashboard.php
require_once 'auth_check.php'; 

// 2. Check if the logged-in user has the correct role ('company')
if ($_SESSION['role'] !== 'company') {
    // If the user is logged in but is not a company, deny access.
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
    <title>Company Dashboard</title>
</head>
<body>
    <header>
        <h1>Welcome, Company: <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p>This area is for posting and managing job opportunities.</p>
        <a href="logout.php">Logout</a>
    </header>

    <main>
        <h2>Job Posting Management</h2>
        </main>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Dashboard</title>
</head>
<body>
    <h1>Welcome, Company Partner!</h1>
    <p>You have successfully logged in. Manage your postings here.</p>
    <a href="logout.php">Logout</a>
</body>
</html>