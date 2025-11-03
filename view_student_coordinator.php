<?php
// view_student_coordinator.php
session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
header("Location: signincoordinator.html");
  exit;
}

// 2. Get Student ID from URL
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if ($student_id === 0) {
 die("<script>alert('No Student ID provided.'); window.location.href='manage_students.php';</script>");
}

$student_data = null;
$resume_status = 'Not Uploaded'; // Default

// --- 3. Database Query: Fetch ALL Student Data ---
// Joins student, users, and requirement tables for a comprehensive view
$query = "
    SELECT 
        s.first_name, 
        s.last_name, 
        s.course, 
        s.year_level, 
        s.description, 
        s.email AS student_email,
        s.phone_number,
        u.username,
        u.user_id,             -- <--- ADDED user_id from users table
        u.status AS account_status,
        r.file_name,
        r.status AS resume_status_db
    FROM 
        student s
    JOIN 
        users u ON s.user_id = u.user_id
    LEFT JOIN
        requirement r ON s.student_id = r.student_id  -- Use LEFT JOIN in case no resume is uploaded
    WHERE
        s.student_id = ? AND u.role = 'student'
    LIMIT 1
";

$stmt = $mysqli->prepare($query);

if ($stmt) {
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student_data = $result->fetch_assoc();
        
        // Check for resume status
        if ($student_data['file_name']) {
             // Use coalesce operator '??' to safely default to 'Uploaded' if status is NULL
             $resume_status = htmlspecialchars($student_data['resume_status_db'] ?? 'Uploaded'); 
        }
        
    } else {
        die("<script>alert('Student profile not found or role is incorrect.'); window.location.href='manage_students.php';</script>");
    }
    
    $stmt->close();
} else {
    // Handle prepare error
    $error_message = "Database error: " . $mysqli->error;
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Student | Coordinator Dashboard</title>
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
      <a href="manage_students.php" class="active"><i class="fa-solid fa-user-graduate"></i> Manage Students</a>
      <a href="manage_companies.php"><i class="fa-solid fa-building"></i> Manage Companies</a>
      <a href="manage_placements.php"><i class="fa-solid fa-clipboard-list"></i> Placements/OJT</a>
      <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <h1>Student Profile: <?php echo htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']); ?></h1>
      <a href="manage_students.php" style="color: rgb(32, 64, 227); text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Back to Student List</a>
    </header>

    <div class="dashboard-body">

      <div class="profile-card">
        <div class="profile-header">
            <h2><?php echo htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']); ?></h2>
            <span class="status-tag status-<?php echo strtolower($student_data['account_status']); ?>">
                <?php echo htmlspecialchars($student_data['account_status']); ?>
            </span>
        </div>

        <h3><i class="fa-solid fa-info-circle"></i> Basic Information</h3>
        <div class="detail-grid">
            <div class="detail-item">
                <strong>Username</strong>
                <span><?php echo htmlspecialchars($student_data['username']); ?></span>
            </div>
            <div class="detail-item">
                <strong>Course / Year Level</strong>
                <span><?php echo htmlspecialchars($student_data['course'] . ' / ' . $student_data['year_level']); ?></span>
            </div>
            <div class="detail-item">
                <strong>Email Address</strong>
                <span><?php echo htmlspecialchars($student_data['student_email']); ?></span>
            </div>
            <div class="detail-item">
                <strong>Contact Number</strong>
                <span><?php echo htmlspecialchars($student_data['phone_number']); ?></span>
            </div>
        </div>
        
        <h3><i class="fa-solid fa-file-alt"></i> Student Description</h3>
        <div class="description-box">
            <?php echo htmlspecialchars($student_data['description'] ?? 'No description provided.'); ?>
        </div>
        
        <br>
        
        <h3><i class="fa-solid fa-paperclip"></i> OJT Requirements / Resume</h3>
        <div class="resume-box">
            <i class="fa-solid fa-file-pdf"></i>
            <div>
                <strong>Resume File:</strong> <?php echo $student_data['file_name'] ? htmlspecialchars($student_data['file_name']) : 'No File Uploaded'; ?>
                <br>
                <strong>Validation Status:</strong> 
                <span style="font-weight: 700; color: <?php 
                    if ($resume_status == 'Validated') echo '#28a745'; 
                    else if ($resume_status == 'Pending') echo '#ffc107'; 
                    else echo '#dc3545'; 
                ?>"><?php echo $resume_status; ?></span>
            </div>
            <?php if ($student_data['file_name']): ?>
                <a href="download_resume.php?file=<?php echo htmlspecialchars($student_data['file_name']); ?>" target="_blank" class="action-link" style="margin-left: auto;">
                    <i class="fa-solid fa-download"></i> Download Resume
                </a>
            <?php endif; ?>
        </div>

        <br>
        
        <h3><i class="fa-solid fa-user-cog"></i> Account Management</h3>
        <p>Current Account Status: <strong><?php echo htmlspecialchars($student_data['account_status']); ?></strong></p>
        
        <form action="update_student_status.php" method="POST" class="status-form">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($student_data['user_id']); ?>">
            <label for="new_status">Change Status:</label>
            <select name="new_status" id="new_status" required>
                <option value="Active" <?php if ($student_data['account_status'] == 'Active') echo 'selected'; ?>>Active</option>
                <option value="Inactive" <?php if ($student_data['account_status'] == 'Inactive') echo 'selected'; ?>>Inactive</option>
                <option value="Banned" <?php if ($student_data['account_status'] == 'Banned') echo 'selected'; ?>>Banned</option>
            </select>
            <button type="submit">Update Account Status</button>
        </form>

      </div>

    </div>
  </div>
</div>

</body>
</html>