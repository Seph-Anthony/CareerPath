<?php
// view_company_coordinator.php
session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: signincoordinator.html"); 
    exit;
}

// 2. Get Company ID from URL
$company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;

if ($company_id === 0) {
    die("<script>alert('No Company ID provided.'); window.location.href='manage_companies.php';</script>");
}

$company_data = null;

// --- 3. Database Query: Fetch ALL Company Data ---
// Joins company and users tables for a comprehensive view
$query = "
    SELECT 
        c.company_name, 
        c.industry, 
        c.contact_person, 
        c.phone_number,
        c.email AS company_email,
        c.address,
        c.description,
        u.username,
        u.user_id,             -- Critical for status update form
        u.status AS account_status
    FROM 
        company c
    JOIN 
        users u ON c.user_id = u.user_id
    WHERE
        c.company_id = ? AND u.role = 'company'
    LIMIT 1
";

$stmt = $mysqli->prepare($query);

if ($stmt) {
    $stmt->bind_param('i', $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $company_data = $result->fetch_assoc();
    } else {
        die("<script>alert('Company profile not found or role is incorrect.'); window.location.href='manage_companies.php';</script>");
    }
    
    $stmt->close();
} else {
    $error_message = "Database error: " . $mysqli->error;
}

// Fetch count of active postings (using the intern_posting table structure you provided)
$active_postings_count = 0;
$postings_query = "SELECT COUNT(*) AS count FROM intern_posting WHERE company_id = ? AND status = 'Active'";
$postings_stmt = $mysqli->prepare($postings_query);
if ($postings_stmt) {
    $postings_stmt->bind_param('i', $company_id);
    $postings_stmt->execute();
    $postings_result = $postings_stmt->get_result();
    $active_postings_count = $postings_result->fetch_assoc()['count'];
    $postings_stmt->close();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Company | Coordinator Dashboard</title>
  <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="coordinatordashboard.css"> 
  <link rel="stylesheet" href="view_student_coordinator.css"> 
  
</head>
<body>

<div class="dashboard-container">

  <aside class="sidebar">
    <div class="logo">
      <h2>Career Path</h2>
    </div>

    <nav class="menu">
      <a href="coordinatordashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
      <a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Manage Students</a>
      <a href="manage_companies.php" class="active"><i class="fa-solid fa-building"></i> Manage Companies</a>
      <a href="manage_placements.php"><i class="fa-solid fa-clipboard-list"></i> Placements/OJT</a>
      <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <h1>Company Profile: <?php echo htmlspecialchars($company_data['company_name']); ?></h1>
      <a href="manage_companies.php" style="color: rgb(32, 64, 227); text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Back to Company List</a>
    </header>

    <div class="dashboard-body">

      <div class="profile-card">
        <div class="profile-header">
            <h2><?php echo htmlspecialchars($company_data['company_name']); ?></h2>
            <span class="status-tag status-<?php echo strtolower($company_data['account_status']); ?>">
                <?php echo htmlspecialchars($company_data['account_status']); ?>
            </span>
        </div>

        <h3><i class="fa-solid fa-info-circle"></i> Basic Information</h3>
        <div class="detail-grid">
            <div class="detail-item">
                <strong>Industry</strong>
                <span><?php echo htmlspecialchars($company_data['industry']); ?></span>
            </div>
            <div class="detail-item">
                <strong>Username</strong>
                <span><?php echo htmlspecialchars($company_data['username']); ?></span>
            </div>
            <div class="detail-item">
                <strong>Contact Person</strong>
                <span><?php echo htmlspecialchars($company_data['contact_person']); ?></span>
            </div>
            <div class="detail-item">
                <strong>Contact Number</strong>
                <span><?php echo htmlspecialchars($company_data['phone_number']); ?></span>
            </div>
            <div class="detail-item">
                <strong>Email Address</strong>
                <span><?php echo htmlspecialchars($company_data['company_email']); ?></span>
            </div>
            <div class="detail-item">
                <strong>Total Active Postings</strong>
                <span><?php echo $active_postings_count; ?></span>
            </div>
        </div>
        
        <h3><i class="fa-solid fa-map-marker-alt"></i> Location & Overview</h3>
        <div class="detail-grid" style="grid-template-columns: 1fr;">
            <div class="detail-item" style="border-bottom: none;">
                <strong>Company Address</strong>
                <span><?php echo htmlspecialchars($company_data['address'] ?? 'Address not provided.'); ?></span>
            </div>
        </div>
        
        <br>
        
        <h3><i class="fa-solid fa-building"></i> Company Description</h3>
        <div class="description-box">
            <?php echo htmlspecialchars($company_data['description'] ?? 'No description provided by the company.'); ?>
        </div>

        <br>
        
        <h3><i class="fa-solid fa-user-cog"></i> Account Management</h3>
        <p>Current Account Status: <strong><?php echo htmlspecialchars($company_data['account_status']); ?></strong></p>
        
        <form action="update_company_status.php" method="POST" class="status-form">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($company_data['user_id']); ?>">
            <label for="new_status">Change Status:</label>
            <select name="new_status" id="new_status" required>
                <option value="Active" <?php if ($company_data['account_status'] == 'Active') echo 'selected'; ?>>Active</option>
                <option value="Inactive" <?php if ($company_data['account_status'] == 'Inactive') echo 'selected'; ?>>Inactive</option>
                <option value="Pending" <?php if ($company_data['account_status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                <option value="Banned" <?php if ($company_data['account_status'] == 'Banned') echo 'selected'; ?>>Banned</option>
            </select>
            <button type="submit">Update Account Status</button>
        </form>

      </div>

    </div>
  </div>
</div>

</body>
</html>