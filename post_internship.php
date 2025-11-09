<?php
// post_internship.php
session_start();
require_once 'db_connect.php'; 

// Basic Authentication check - Only companies can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html"); 
    exit;
}

// Fetch the company name for the header
$user_id = $_SESSION['user_id'];
$company_name = "Company Representative";

$stmt = $mysqli->prepare("SELECT company_name FROM company WHERE user_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        $company_name = htmlspecialchars($data['company_name']);
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Post Internship | Career Path</title>
  <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="companydashboard.css"> 
</head>
<body>

<div class="dashboard-container">
  <aside class="sidebar">
    <div class="logo">
      <h2>Career Path</h2>
    </div>

    <nav class="menu">
    <a href="companydashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
    <a href="post_internship.php" class="active"><i class="fa-solid fa-file-circle-plus"></i> Post Internship</a>
    <a href="manage_applicants.php"><i class="fa-solid fa-users"></i> View Applicants</a>
    <a href="company_log_approval.php"><i class="fa-solid fa-file-signature"></i> Approve Daily Logs</a>
    <a href="intern_progress.php"><i class="fa-solid fa-chart-line"></i> Intern Progress</a>
    <a href="company_profile.php"><i class="fa-solid fa-user-circle"></i> Profile</a>
    <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
</nav>
  </aside>

    <main class="main-content">
        <header class="topbar">
            <h1>Post a New Internship</h1>
            <div class="user-section">
                <div class="user-profile">
                    <span><?php echo $company_name; ?></span>
                </div>
            </div>
        </header>

        <div class="dashboard-body">
            <div class="form-container">
                <h2>Internship Details</h2>
                <form action="process_post.php" method="POST">
                    <div class="form-grid">
                        
                        <div class="form-group full-width">
                            <label for="title">Internship Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="description">Job Description</label>
                            <textarea id="description" name="description" required></textarea>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="requirements">Requirements</label>
                            <textarea id="requirements" name="requirements" placeholder="List required skills, technologies, or documents" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="slot_available">Number of Slots Available</label>
                            <input type="number" id="slot_available" name="slot_available" min="1" value="1" required>
                        </div>

                        <div class="form-group">
                            <label for="duration">Duration (Informational)</label>
                            <input type="text" id="duration" name="duration_info" placeholder="e.g., 480 Hours / 3 Months">
                        </div>

                        <button type="submit" class="submit-btn">Post Internship</button>

                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>