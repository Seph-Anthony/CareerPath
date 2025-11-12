<?php
// intern_progress.php - Company view of currently active interns

session_start();
require_once 'db_connect.php'; 

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html"); 
    exit;
}

$user_id = $_SESSION['user_id'];
$company_id = null;
$company_name = "Your Company";

// Fetch Company ID and Name
$stmt_company = $mysqli->prepare("SELECT company_id, company_name FROM company WHERE user_id = ? LIMIT 1");
if ($stmt_company) {
    $stmt_company->bind_param('i', $user_id);
    $stmt_company->execute();
    $result_company = $stmt_company->get_result();
    if ($result_company->num_rows === 1) {
        $data = $result_company->fetch_assoc();
        $company_id = $data['company_id'];
        $company_name = htmlspecialchars($data['company_name']);
    }
    $stmt_company->close();
}

if (!$company_id) {
    die("Error: Company profile not found.");
}

// 2. Query to fetch Hired Interns for this company
// NOTE: We assume 'Hired' is the current status for an active intern. We exclude 'Completed' and 'Terminated'.
$query = "
    SELECT 
        s.student_id,
        s.first_name, 
        s.last_name, 
        s.course,
        ip.title AS posting_title,
        ia.application_id
    FROM 
        intern_application ia
    JOIN 
        student s ON ia.student_id = s.student_id
    JOIN 
        intern_posting ip ON ia.posting_id = ip.posting_id
    WHERE
        ip.company_id = ? AND ia.status = 'Hired'
    ORDER BY
        s.last_name
";

$stmt = $mysqli->prepare($query);
$active_interns = [];

if ($stmt) {
    $stmt->bind_param('i', $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $active_interns[] = $row;
    }
    $stmt->close();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intern Progress | Company Dashboard</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="companydashboard.css"> 
    <link rel="stylesheet" href="manage_applicants.css"> 
    <link rel="stylesheet" href="intern_progress.css"> 
</head>
<body>

<div class="dashboard-container">

    <aside class="sidebar">
    <div class="logo">
      <h2>Career Path</h2>
    </div>

    <nav class="menu">
        <a href="companydashboard.php" ><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="post_internship.php"><i class="fa-solid fa-file-circle-plus"></i> Post Internship</a>
        <a href="manage_applicants.php"><i class="fa-solid fa-users"></i> View Applicants</a>
        <a href="company_log_approval.php"><i class="fa-solid fa-file-signature"></i> Approve Daily Logs</a>
        <a href="intern_progress.php" class="active"><i class="fa-solid fa-chart-line"></i> Intern Progress</a>
        <a href="#"><i class="fa-solid fa-user-circle"></i> Profile</a>
        <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>

    <main class="main-content">
        <header class="topbar">
            <h1>Intern Progress Tracking</h1>
            <p>Monitoring active interns working with <?php echo $company_name; ?></p>
        </header>

        <div class="dashboard-body">

            <?php if (count($active_interns) > 0): ?>
                <table class="progress-table">
                    <thead>
                        <tr>
                            <th>Intern Name</th>
                            <th>Position Title</th>
                            <th>Course</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_interns as $intern): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($intern['posting_title']); ?></td>
                                <td><?php echo htmlspecialchars($intern['course']); ?></td>
                                <td style="display: flex; gap: 8px;"> 
                                    <a href="view_intern_activity.php?student_id=<?php echo $intern['student_id']; ?>&application_id=<?php echo $intern['application_id']; ?>" class="btn-view progress-log-btn">
                                        <i class="fa-solid fa-eye"></i> View Log
                                    </a>
                                    
                                    <a href="assign_task.php?student_id=<?php echo $intern['student_id']; ?>&application_id=<?php echo $intern['application_id']; ?>" class="btn-view" style="background: #ff7f00;">
                                        <i class="fa-solid fa-list-check"></i> Assign Task
                                    </a>

                                    <a href="intern_evaluation.php?student_id=<?php echo $intern['student_id']; ?>&application_id=<?php echo $intern['application_id']; ?>" class="btn-view evaluate-btn">
                                        <i class="fa-solid fa-star"></i> Evaluate
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-interns">
                    <i class="fa-solid fa-info-circle"></i> No students are currently marked as 'Hired' for your company's postings.
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

</body>
</html>