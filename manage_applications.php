<?php
// manage_applications.php
session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: signincoordinator.html"); 
    exit;
}

$coordinator_id = $_SESSION['user_id'];
$coordinator_name = htmlspecialchars($_SESSION['username']);
$message = '';
$message_type = '';

// --- 2. Handle Application Approval/Rejection (FIXED: Table and column names) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'], $_POST['action'])) {
    $application_id = $_POST['application_id'];
    $action = $_POST['action']; // 'Approve' or 'Reject'
    
    // Determine the new status
    $new_status = ($action === 'Approve') ? 'Hired' : 'Rejected'; // Setting status to 'Hired' or 'Rejected'
    
    // NOTE: Removed 'coordinator_id' and 'review_date' as they don't exist in the intern_application table in ojtfind (10).sql.
    // The main status column is 'status'.
    $stmt = $mysqli->prepare("UPDATE intern_application SET status = ? WHERE application_id = ?");

    if ($stmt) {
        $stmt->bind_param('si', $new_status, $application_id);
        
        if ($stmt->execute()) {
            $message = "Application ID #{$application_id} has been successfully {$new_status}.";
            $message_type = 'success';
        } else {
            $message = "Error updating application status: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    } else {
        $message = "Database error preparing statement.";
        $message_type = 'error';
    }
}


// --- 3. Fetch Pending Applications for Display (FIXED: Table names, column names, and JOINs) ---
$pending_applications = [];

// The query uses the correct table names: intern_application, student, intern_posting, company
// It also concatenates first_name and last_name since full_name doesn't exist.
$query = "
    SELECT
        a.application_id,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        s.course,
        c.company_name,
        p.title AS post_title,
        a.application_date
    FROM
        intern_application a
    JOIN
        student s ON a.student_id = s.student_id
    JOIN
        intern_posting p ON a.posting_id = p.posting_id
    JOIN
        company c ON p.company_id = c.company_id
    WHERE
        a.status = 'Pending'
    ORDER BY
        a.application_date DESC
";

$result = $mysqli->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pending_applications[] = $row;
    }
    $result->free();
} else {
    $message = "Error fetching applications: " . $mysqli->error;
    $message_type = 'error';
}

// Close the connection
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Review Applications | Career Path</title>
  <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="coordinatordashboard.css">
  <link rel="stylesheet" href="manage_applications.css"> 
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
            <a href="manage_applications.php"  class="active"><i class="fa-solid fa-clipboard-check"></i> Review Applications</a> 
            <a href="manage_companies.php"><i class="fa-solid fa-building"></i> Manage Companies</a>
            <a href="manage_placements.php"><i class="fa-solid fa-clipboard-list"></i> Placements/OJT</a>
            <a href="manage_evaluations.php"><i class="fa-solid fa-clipboard-check"></i> Review Evaluations</a> 
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <h1>Review Student Applications</h1>
      <p>Manage pending OJT applications that require coordinator approval.</p>
    </header>

    <div class="dashboard-body">

      <?php if ($message): ?>
        <div class="alert-container <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
      <?php endif; ?>

      <section class="section-container">
        <h2><i class="fa-solid fa-clipboard-list"></i> Pending Applications (<?php echo count($pending_applications); ?>)</h2>

        <?php if (empty($pending_applications)): ?>
            <p style="text-align: center; color: #777; padding: 30px;">
                <i class="fa-solid fa-check-circle" style="color: #28a745; margin-right: 5px;"></i> All applications are up to date! There are no pending student applications requiring your review.
            </p>
        <?php else: ?>
            <table class="applications-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Applied to Post</th>
                        <th>Company</th>
                        <th>Date Applied</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_applications as $app): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($app['application_id']); ?></td>
                        <td><?php echo htmlspecialchars($app['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($app['course']); ?></td>
                        <td><?php echo htmlspecialchars($app['post_title']); ?></td>
                        <td><?php echo htmlspecialchars($app['company_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($app['application_date'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <form method="POST" action="manage_applications.php">
                                    <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                    <button type="submit" name="action" value="Approve" class="btn-approve">Approve</button>
                                </form>
                                <form method="POST" action="manage_applications.php">
                                    <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                    <button type="submit" name="action" value="Reject" class="btn-reject">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

      </section>

    </div>
  </div>
</div>

</body>
</html>