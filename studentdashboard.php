
<?php
// studentdashboard.php
require_once 'auth_check.php'; 

// 2. Check if the logged-in user has the correct role ('student')
if ($_SESSION['role'] !== 'student') {
    // If they logged in as a Coordinator/Company but ended up here, redirect them
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
    <title>Student Dashboard | Career Path</title>
</head>
<body>
    <header>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p>You have successfully logged in as a student.</p>
        <a href="logout.php">Logout</a>
    </header>

    <main>
        <h2>Your OJT Journey Starts Here</h2>
        <p>This is where all your personalized content will go.</p>
    </main>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Career Path</title>
</head>
<body>
    <header>
        <h1>Welcome, Student!</h1>
        <p>You have successfully logged in.</p>
        <a href="logout.php">Logout</a>
    </header>

    <main>
        <h2>Your OJT Journey Starts Here</h2>
        <p>This is where all your personalized content will go.</p>
    </main>
</body>
</html>