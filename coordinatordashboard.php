<?php
// coordinatordashboard.php
session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: signincoordinator.html"); 
    exit;
}

// Get the user_id and username from the session
$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);

// --- 2. Fetch Coordinator's Profile Details ---
$coordinator_name = $username; 
$coordinator_department = 'N/A';
$coordinator_position = 'Coordinator'; 
$coordinator_email = 'N/A';
$coordinator_contact = 'N/A';

// The SELECT statement uses the correct column names from your table: full_name, position, email, contact_number
$stmt = $mysqli->prepare("
    SELECT full_name, department, position, email, contact_number
    FROM coordinator 
    WHERE user_id = ? 
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        // Assign the fetched values
        $coordinator_name = htmlspecialchars($data['full_name']);
        $coordinator_department = htmlspecialchars($data['department']);
        $coordinator_position = htmlspecialchars($data['position']);
        $coordinator_email = htmlspecialchars($data['email']);
        $coordinator_contact = htmlspecialchars($data['contact_number']);
    }
    $stmt->close();
}


// --- 3. Fetch Dashboard Metrics (FIXED: Join with users table for status filter) ---
$stats = [
    'total_students' => 0,
    'active_companies' => 0,
    'placed_students' => 0,
    'pending_students' => 0,
];

// a) Total Students (Active in users table)
// Joins student table with users table to check for users.status = 'Active'
$query = "SELECT COUNT(*) AS count 
          FROM student s 
          JOIN users u ON s.user_id = u.user_id 
          WHERE u.status = 'Active' AND u.role = 'student'";
$result = $mysqli->query($query);
$stats['total_students'] = $result ? $result->fetch_assoc()['count'] : 0;

// b) Total Active Companies (Active in users table)
// Joins company table with users table to check for users.status = 'Active'
$query = "SELECT COUNT(*) AS count 
          FROM company c 
          JOIN users u ON c.user_id = u.user_id 
          WHERE u.status = 'Active' AND u.role = 'company'";
$result = $mysqli->query($query);
$stats['active_companies'] = $result ? $result->fetch_assoc()['count'] : 0;

// c) Total Placed Students 
// This count requires the Placements table (not built yet). 
// Temporarily set to 0. We will rely on a future Placements table for accuracy.
$stats['placed_students'] = 0; 

// d) Total Students seeking placement (All Active students are seeking placement until Placed)
$stats['pending_students'] = max(0, $stats['total_students'] - $stats['placed_students']);

// Fetch some recent activities (PLACEHOLDER DATA)
$recent_activity = [
    ['text' => 'Tech Solutions Inc. posted 3 new internships.', 'type' => 'post'],
    ['text' => 'Global Finance Co. account was set to Active.', 'type' => 'active'],
    ['text' => '5 new Company signups pending review.', 'type' => 'pending'],
];


$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Coordinator Dashboard | Career Path</title>
  <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="coordinatordashboard.css">
</head>
<body>

<div class="dashboard-container">

  <aside class="sidebar">
    <div class="logo">
      <h2>Career Path</h2>
    </div>

    <nav class="menu">
      <a href="coordinatordashboard.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a>
      <a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Manage Students</a>
      <a href="manage_companies.php"><i class="fa-solid fa-building"></i> Manage Companies</a>
      <a href="manage_placements.php"><i class="fa-solid fa-clipboard-list"></i> Placements/OJT</a>
      <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <h1>Welcome, <?php echo $coordinator_name; ?>!</h1>
      <p>OJT Management Overview for the **<?php echo $coordinator_department; ?>** Department.</p>
    </header>

    <div class="dashboard-body">

      <section class="stats-cards">
        
        <div class="card">
            <i class="fa-solid fa-users"></i>
            <div>
                <h3>Total Students</h3>
                <p><?php echo $stats['total_students']; ?></p>
            </div>
        </div>

        <div class="card">
            <i class="fa-solid fa-briefcase"></i>
            <div>
                <h3>Active Companies</h3>
                <p><?php echo $stats['active_companies']; ?></p>
            </div>
        </div>
        
        <div class="card">
            <i class="fa-solid fa-user-check"></i>
            <div>
                <h3>Students Placed</h3>
                <p><?php echo $stats['placed_students']; ?></p>
            </div>
        </div>
        
        <div class="card">
            <i class="fa-solid fa-hourglass-half"></i>
            <div>
                <h3>Seeking Placement</h3>
                <p><?php echo $stats['pending_students']; ?></p>
            </div>
        </div>
      </section>

      <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        
        <section class="section-container">
            <h2><i class="fa-solid fa-building"></i> Recent Program Activity</h2>
            
            <?php foreach ($recent_activity as $activity): ?>
            <div class="list-item">
                <p><?php echo htmlspecialchars($activity['text']); ?></p>
                <?php if ($activity['type'] == 'active'): ?>
                    <span class="status-dot status-active"></span>
                <?php elseif ($activity['type'] == 'pending'): ?>
                    <span class="status-dot status-pending"></span>
                <?php else: ?>
                    <span style="font-size: 13px; color: #999;">Activity Log</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <p style="text-align: right; margin-top: 15px;"><a href="#" style="font-weight: 600; color: rgb(32, 64, 227);">View All Activity &raquo;</a></p>
        </section>

        <section class="section-container">
            <h2><i class="fa-solid fa-user-tie"></i> My Profile</h2>
            <div class="list-item">
                <p><strong>Full Name:</strong></p>
                <span><?php echo $coordinator_name; ?></span>
            </div>
            <div class="list-item">
                <p><strong>Position:</strong></p>
                <span><?php echo $coordinator_position; ?></span>
            </div>
            <div class="list-item">
                <p><strong>Department:</strong></p>
                <span><?php echo $coordinator_department; ?></span>
            </div>
            <div class="list-item">
                <p><strong>Email:</strong></p>
                <span><?php echo $coordinator_email; ?></span>
            </div>
            <div class="list-item">
                <p><strong>Contact:</strong></p>
                <span><?php echo $coordinator_contact; ?></span>
            </div>

            <p style="text-align: right; margin-top: 15px;"><a href="#" style="font-weight: 600; color: rgb(32, 64, 227);">Edit Profile &raquo;</a></p>
        </section>
      </div>


    </div>
  </div>
</div>

</body>
</html>