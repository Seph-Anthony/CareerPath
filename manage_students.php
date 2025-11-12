<?php
// manage_students.php
session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: signincoordinator.html"); 
    exit;
}

// Get the user_id and username from the session (used for profile display if needed)
$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);

// --- 2. Database Query: Fetch All Students ---
// We join the 'student' table with the 'users' table to get both personal details and account status.
$query = "
    SELECT 
        s.student_id, 
        s.first_name, 
        s.last_name, 
        s.course, 
        s.year_level,
        u.username,
        u.status AS account_status,
        s.email
    FROM 
        student s
    JOIN 
        users u ON s.user_id = u.user_id
    WHERE
        u.role = 'student'
    ORDER BY 
        s.last_name ASC
";

$result = $mysqli->query($query);
$students = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $result->free();
} else {
    // Handle query error
    $error_message = "Error fetching student data: " . $mysqli->error;
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Students | Coordinator Dashboard</title>
  <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="coordinatordashboard.css"> <style>
    /* Specific styles for the Manage Students table */
    .table-container {
        background: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    .student-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    .student-table th, .student-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
        font-size: 14px;
    }
    .student-table th {
        background-color: rgb(247, 250, 252);
        color: rgb(32, 64, 227);
        font-weight: 700;
        text-transform: uppercase;
    }
    .student-table tr:hover {
        background-color: #f5f5ff;
    }
    .status-active, .status-inactive, .status-pending {
        padding: 4px 8px;
        border-radius: 5px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
    }
    .status-active { background-color: #d4edda; color: #155724; }
    .status-inactive { background-color: #f8d7da; color: #721c24; }
    .status-pending { background-color: #fff3cd; color: #856404; }
    
    .action-link {
        color: rgb(32, 64, 227);
        text-decoration: none;
        font-weight: 600;
        transition: color 0.2s;
    }
    .action-link:hover {
        color: rgb(25, 50, 190);
        text-decoration: underline;
    }
  </style>

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
            <a href="manage_applications.php"><i class="fa-solid fa-clipboard-check"></i> Review Applications</a> 
            <a href="manage_companies.php"><i class="fa-solid fa-building"></i> Manage Companies</a>
            <a href="manage_placements.php"><i class="fa-solid fa-clipboard-list"></i> Placements/OJT</a>
            <a href="manage_evaluations.php"><i class="fa-solid fa-clipboard-check"></i> Review Evaluations</a> 
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <h1>Manage Students</h1>
      <p>Oversight of all registered student accounts.</p>
    </header>

    <div class="dashboard-body">

      <div class="table-container">
        <h2><i class="fa-solid fa-table"></i> Student List (Total: <?php echo count($students); ?>)</h2>

        <?php if (isset($error_message)): ?>
            <div style="color: red; padding: 10px; border: 1px solid red; border-radius: 5px;"><?php echo $error_message; ?></div>
        <?php elseif (empty($students)): ?>
            <p>No students have registered yet.</p>
        <?php else: ?>
            <table class="student-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Course / Year</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): 
                        $fullName = htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
                        $courseYear = htmlspecialchars($student['course'] . ' / ' . $student['year_level']);
                        $statusClass = strtolower(str_replace(' ', '-', $student['account_status'])); // Converts 'Active' to 'active'
                    ?>
                    <tr>
                        <td><?php echo $fullName; ?></td>
                        <td><?php echo $courseYear; ?></td>
                        <td><?php echo htmlspecialchars($student['username']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><span class="status-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($student['account_status']); ?></span></td>
                        <td><a href="view_student_coordinator.php?student_id=<?php echo $student['student_id']; ?>" class="action-link">View Profile</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

      </div>

    </div>
  </div>
</div>

</body>
</html>