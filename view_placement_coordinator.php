<?php
// view_placement_coordinator.php
session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: signincoordinator.html"); 
    exit;
}

// 2. Get Posting ID from URL
$posting_id = isset($_GET['posting_id']) ? intval($_GET['posting_id']) : 0;

if ($posting_id === 0) {
    die("<script>alert('No Posting ID provided.'); window.location.href='manage_placements.php';</script>");
}

$posting_data = null;
$error_message = null;

// --- 3. Database Query: Fetch Posting Details and Company Info ---
$query = "
    SELECT 
        ip.posting_id, 
        ip.company_id,
        ip.title, 
        ip.description, 
        ip.requirements, 
        ip.slot_available, 
        ip.create_at,
        ip.status AS posting_status,
        c.company_name
    FROM 
        intern_posting ip
    JOIN 
        company c ON ip.company_id = c.company_id
    WHERE
        ip.posting_id = ?
    LIMIT 1
";

$stmt = $mysqli->prepare($query);

if ($stmt) {
    $stmt->bind_param('i', $posting_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $posting_data = $result->fetch_assoc();
        // Format the date
        $posting_data['date_posted_formatted'] = date('M d, Y', strtotime($posting_data['create_at']));
    } else {
        die("<script>alert('OJT posting not found.'); window.location.href='manage_placements.php';</script>");
    }
    
    $stmt->close();
} else {
    $error_message = "Database error: " . $mysqli->error;
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Review Posting | Coordinator Dashboard</title>
  <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="coordinatordashboard.css"> 
  <link rel="stylesheet" href="manage_placements.css"> </head>
<body>

<div class="dashboard-container">

  <aside class="sidebar">
    <div class="logo">
      <h2>Career Path</h2>
    </div>

    <nav class="menu">
      <a href="coordinatordashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
      <a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Manage Students</a>
      <a href="manage_applications.php"><i class="fa-solid fa-clipboard-check"></i> Review Applications</a> 
      <a href="manage_companies.php"><i class="fa-solid fa-building"></i> Manage Companies</a>
      <a href="manage_placements.php" class="active"><i class="fa-solid fa-clipboard-list"></i> Placements/OJT</a>
      <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <h1>Review OJT Posting</h1>
      <a href="manage_placements.php" style="color: rgb(32, 64, 227); text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Back to Postings List</a>
    </header>

    <div class="dashboard-body">

      <div class="review-card">
        
        <div class="posting-header">
            <h2><?php echo htmlspecialchars($posting_data['title']); ?></h2>
            <p><strong>Company:</strong> <?php echo htmlspecialchars($posting_data['company_name']); ?></p>
            <span class="status-tag status-<?php echo strtolower(str_replace(' ', '-', $posting_data['posting_status'])); ?>">
                Status: <?php echo htmlspecialchars($posting_data['posting_status']); ?>
            </span>
        </div>

        <div class="detail-section">
            <div class="detail-grid-placement">
                <div class="detail-item-placement">
                    <strong>Date Posted</strong>
                    <span><?php echo htmlspecialchars($posting_data['date_posted_formatted']); ?></span>
                </div>
                <div class="detail-item-placement">
                    <strong>Slots Available</strong>
                    <span><?php echo htmlspecialchars($posting_data['slot_available']); ?></span>
                </div>
                <div class="detail-item-placement">
                    <strong>Company ID</strong>
                    <span><?php echo htmlspecialchars($posting_data['company_id']); ?></span>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h3><i class="fa-solid fa-file-alt"></i> Job Description</h3>
            <div class="description-box-placement">
                <?php echo nl2br(htmlspecialchars($posting_data['description'])); ?>
            </div>
        </div>

        <div class="detail-section">
            <h3><i class="fa-solid fa-cogs"></i> Key Requirements & Skills (Courses/Eligibility)</h3>
            <div class="description-box-placement">
                <?php echo nl2br(htmlspecialchars($posting_data['requirements'])); ?>
            </div>
        </div>
        
        <hr style="margin: 30px 0;">

        <div class="review-form-container">
            <h3><i class="fa-solid fa-user-check"></i> Posting Status Management</h3>
            
            <p>Current Status: <strong><?php echo htmlspecialchars($posting_data['posting_status']); ?></strong></p>
            
            <form action="update_posting_status.php" method="POST" class="review-form">
                <input type="hidden" name="posting_id" value="<?php echo htmlspecialchars($posting_data['posting_id']); ?>">
                
                <label for="new_status" style="display: block; margin-bottom: 10px; font-weight: 600;">Change Posting Status:</label>
                
                <select name="new_status" id="new_status" required style="padding: 10px; border-radius: 5px; border: 1px solid #ccc; margin-right: 15px;">
                    <option value="Active" <?php if ($posting_data['posting_status'] == 'Active') echo 'selected'; ?>>Approve (Active)</option>
                    <option value="Pending Review" <?php if ($posting_data['posting_status'] == 'Pending Review') echo 'selected'; ?>>Set to Pending Review</option>
                    <option value="Inactive" <?php if ($posting_data['posting_status'] == 'Inactive') echo 'selected'; ?>>Deactivate (Inactive)</option>
                    <option value="Expired" <?php if ($posting_data['posting_status'] == 'Expired') echo 'selected'; ?>>Mark as Expired</option>
                </select>
                
                <button type="submit" class="btn-approve">Update Posting Status</button>
            </form>
            
        </div>

      </div>

    </div>
  </div>
</div>

</body>
</html>