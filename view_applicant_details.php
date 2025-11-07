<?php
// view_applicant_details.php - Company view of a single applicant
session_start();
require_once 'db_connect.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html");
    exit;
}

// 2. Collect & Validate Inputs
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$posting_id = isset($_GET['posting_id']) ? intval($_GET['posting_id']) : 0;
$application_id = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0;

if ($student_id === 0 || $posting_id === 0) {
    die("<script>alert('Invalid applicant or posting ID.'); window.location.href='manage_applicants.php';</script>");
}

$user_id = $_SESSION['user_id'];
$company_name = "Company Representative";
$company_id = null;
$applicant_data = null;
$post_title = "";
$current_status = "";

// 3. Fetch Company ID (for security and contextual data)
$stmt_company = $mysqli->prepare("SELECT company_id, company_name FROM company WHERE user_id = ? LIMIT 1");
if ($stmt_company) {
    $stmt_company->bind_param('i', $user_id);
    $stmt_company->execute();
    $result_company = $stmt_company->get_result();

    if ($result_company->num_rows === 1) {
        $data = $result_company->fetch_assoc();
        $company_name = htmlspecialchars($data['company_name']);
        $company_id = $data['company_id'];
    }

    $stmt_company->close();
}

if (!$company_id) {
    die("Error: Company profile not found.");
}

// 4. Main Query: Fetch Applicant, Application, and Posting Details
$query = "
    SELECT 
        s.first_name, 
        s.last_name, 
        s.email, 
        s.course, 
        s.year_level,
        s.phone_number,
        s.description, 
        ip.title AS post_title,
        ip.company_id AS posting_company_id,
        ia.status AS application_status, 
        ia.application_date AS applied_at, 
        ia.application_id
    FROM 
        intern_application ia 
    JOIN 
        student s ON ia.student_id = s.student_id
    JOIN 
        intern_posting ip ON ia.posting_id = ip.posting_id
    WHERE
        ia.student_id = ? 
        AND ia.posting_id = ?
    LIMIT 1
";

$stmt = $mysqli->prepare($query);
if ($stmt) {
    $stmt->bind_param('ii', $student_id, $posting_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $applicant_data = $result->fetch_assoc();

        // Ownership check
        if ($applicant_data['posting_company_id'] !== $company_id) {
            die("<script>alert('Application found, but you do not have permission to view this application.'); window.location.href='manage_applicants.php';</script>");
        }

        $post_title = htmlspecialchars($applicant_data['post_title']);
        $current_status = htmlspecialchars($applicant_data['application_status']);
        $application_id = $applicant_data['application_id'];
    } else {
        die("<script>alert('Application not found.'); window.location.href='manage_applicants.php';</script>");
    }

    $stmt->close();
} else {
    die("Database error: " . $mysqli->error);
}

$mysqli->close();
$fullName = htmlspecialchars($applicant_data['first_name'] . ' ' . $applicant_data['last_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Applicant | Company Dashboard</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="companydashboard.css">
    <link rel="stylesheet" href="manage_applicants.css">
    <link rel="stylesheet" href="applicant_details.css">
</head>
<body>

<div class="dashboard-container">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <h2>Career Path</h2>
        </div>

        <nav class="menu">
            <a href="companydashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="post_internship.php"><i class="fa-solid fa-file-circle-plus"></i> Post Internship</a>
            <a href="manage_applicants.php" class="active"><i class="fa-solid fa-users"></i> View Applicants</a>
            <a href="intern_progress.php"><i class="fa-solid fa-chart-line"></i> Intern Progress</a>
            <a href="company_profile.php"><i class="fa-solid fa-user-circle"></i> Profile</a>
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">

        <header class="topbar">
            <h1>Review Applicant: <?php echo $fullName; ?></h1>
            <a href="manage_applicants.php" style="color: rgb(32, 64, 227); text-decoration: none; font-weight: 600;">
                <i class="fa-solid fa-arrow-left"></i> Back to Applicants List
            </a>
        </header>

        <div class="dashboard-body">

            <div class="detail-card">

                <div class="profile-header">
                    <h2><?php echo $fullName; ?></h2>
                    <p>Applied for: <strong><?php echo $post_title; ?></strong></p>
                </div>

                <div class="profile-info">
                    <div class="info-item">
                        <strong>Course / Year</strong>
                        <span><?php echo htmlspecialchars($applicant_data['course']); ?> (Year <?php echo htmlspecialchars($applicant_data['year_level']); ?>)</span>
                    </div>

                    <div class="info-item" style="grid-column: span 2;">
                        <strong>Student Description / Bio</strong>
                        <span><?php echo htmlspecialchars($applicant_data['description'] ?: 'No description provided.'); ?></span>
                    </div>

                    <div class="info-item">
                        <strong>Email</strong>
                        <span><?php echo htmlspecialchars($applicant_data['email']); ?></span>
                    </div>

                    <div class="info-item">
                        <strong>Phone Number</strong>
                        <span><?php echo htmlspecialchars($applicant_data['phone_number']); ?></span>
                    </div>

                    <div class="info-item">
                        <strong>Applied On</strong>
                        <span><?php echo date('M d, Y', strtotime($applicant_data['applied_at'])); ?></span>
                    </div>

                    <div class="info-item">
                        <strong>Student ID</strong>
                        <span><?php echo htmlspecialchars($student_id); ?></span>
                    </div>
                </div>

                <div class="status-management">
                    <h3>Application Status Management</h3>

                    <p>
                        Current Status:
                        <span class="status-tag status-<?php echo strtolower($current_status); ?>">
                            <?php echo $current_status; ?>
                        </span>
                    </p>

                    <form action="update_applicant_status.php" method="POST" class="status-form">
                        <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($application_id); ?>">
                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
                        <input type="hidden" name="posting_id" value="<?php echo htmlspecialchars($posting_id); ?>">

                        <label for="new_status" style="display: block; margin: 15px 0 8px 0; font-weight: 600;">Change Status:</label>

                        <select name="new_status" id="new_status" required>
                            <option value="Pending" <?php if ($current_status == 'Pending') echo 'selected'; ?>>Pending (Reviewing)</option>
                            <option value="Interviewing" <?php if ($current_status == 'Interviewing') echo 'selected'; ?>>Interviewing</option>
                            <option value="Hired" <?php if ($current_status == 'Hired') echo 'selected'; ?>>Accepted (Offer Sent)</option>
                            <option value="Rejected" <?php if ($current_status == 'Rejected') echo 'selected'; ?>>Rejected</option>
                        </select>

                        <button type="submit" class="btn-update">Update Status</button>
                    </form>
                </div>

            </div>
        </div>
    </main>
</div>

</body>
</html>
