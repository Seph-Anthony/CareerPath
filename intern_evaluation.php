<?php
// intern_evaluation.php - Company submits final evaluation for an intern

session_start();
require_once 'db_connect.php'; 

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html"); 
    exit;
}

// 2. Collect & Validate Inputs (from the URL)
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$application_id = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0; 

if ($student_id === 0 || $application_id === 0) {
    die("<script>alert('Error: Missing required intern or application details.'); window.location.href='intern_progress.php';</script>");
}

$user_id = $_SESSION['user_id'];
$company_id = null;
$intern_details = null;
$evaluation_exists = false;

// 3. Fetch Company ID and Intern Details (with security check)
$query_details = "
    SELECT 
        c.company_id,
        s.first_name, 
        s.last_name, 
        ip.title AS posting_title
    FROM 
        company c
    JOIN 
        users u ON c.user_id = u.user_id
    LEFT JOIN
        intern_application ia ON ia.student_id = ? AND ia.application_id = ? AND ia.status = 'Hired'
    LEFT JOIN
        intern_posting ip ON ia.posting_id = ip.posting_id
    LEFT JOIN
        student s ON s.student_id = ?
    WHERE 
        c.user_id = ? AND ip.company_id = c.company_id
    LIMIT 1
";

$stmt_details = $mysqli->prepare($query_details);
if ($stmt_details) {
    // Note: Bind parameters order must match the '?' in the query (i i i i)
    $stmt_details->bind_param('iiii', $student_id, $application_id, $student_id, $user_id);
    $stmt_details->execute();
    $result_details = $stmt_details->get_result();
    
    if ($result_details->num_rows === 1) {
        $details_data = $result_details->fetch_assoc();
        $company_id = $details_data['company_id'];
        $intern_details = $details_data;
    }
    $stmt_details->close();
}

if (!$company_id || !$intern_details || empty($intern_details['first_name'])) {
    die("<script>alert('Error: Intern not found or not currently hired under your company.'); window.location.href='intern_progress.php';</script>");
}

$fullName = htmlspecialchars($intern_details['first_name'] . ' ' . $intern_details['last_name']);
$position = htmlspecialchars($intern_details['posting_title']);

// 4. Check if Evaluation Already Exists
// This assumes your 'evaluation' table has an 'application_id' column
$stmt_check = $mysqli->prepare("SELECT evaluation_id FROM evaluation WHERE application_id = ? LIMIT 1");
if ($stmt_check) {
    $stmt_check->bind_param('i', $application_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        $evaluation_exists = true;
    }
    $stmt_check->close();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluate Intern | <?php echo $fullName; ?></title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="companydashboard.css"> 
    <link rel="stylesheet" href="manage_applicants.css"> 
    <link rel="stylesheet" href="intern_progress.css"> 
    <link rel="stylesheet" href="intern_evaluation.css"> </head>
<body>

<div class="dashboard-container">

    <aside class="sidebar">
        <div class="logo"><h2>Career Path</h2></div>
        <nav class="menu">
            <a href="companydashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="post_internship.php"><i class="fa-solid fa-file-circle-plus"></i> Post Internship</a>
            <a href="manage_applicants.php"><i class="fa-solid fa-users"></i> View Applicants</a>
            <a href="intern_progress.php" class="active"><i class="fa-solid fa-chart-line"></i> Intern Progress</a>
            <a href="#"><i class="fa-solid fa-user-circle"></i> Profile</a>
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <h1>Evaluate Intern: **<?php echo $fullName; ?>**</h1>
            <a href="intern_progress.php" style="color: rgb(32, 64, 227); text-decoration: none; font-weight: 600;"><i class="fa-solid fa-arrow-left"></i> Back to Intern List</a>
        </header>

        <div class="dashboard-body">
            <p style="margin-bottom: 20px;">Position: <strong><?php echo $position; ?></strong></p>

            <?php if ($evaluation_exists): ?>
                <div class="alert-complete">
                    <i class="fa-solid fa-check-circle"></i> This intern has already been officially evaluated.
                </div>
                <?php else: ?>
                <div class="evaluation-form-card">
                    <h2>Internship Performance Review</h2>
                    <form action="submit_evaluation.php" method="POST">
                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
                        <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($application_id); ?>">
                        <input type="hidden" name="company_id" value="<?php echo htmlspecialchars($company_id); ?>">

                        <div class="form-group">
                            <label for="rating">Overall Rating (1-5, where 5 is Excellent):</label>
                            <input type="number" id="rating" name="rating" min="1" max="5" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="feedback">Detailed Feedback and Comments (Required):</label>
                            <textarea id="feedback" name="feedback" rows="6" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="final_status">Change Application Status to:</label>
                            <select id="final_status" name="final_status" required>
                                <option value="Hired" selected>Hired (Keep Status)</option>
                                <option value="Completed">Completed Internship</option>
                                <option value="Terminated">Terminated/Unsatisfactory</option>
                            </select>
                            <small style="display: block; margin-top: 5px; color: #666;">Choosing 'Completed Internship' will move the student from the active list.</small>
                        </div>

                        <button type="submit" class="btn-submit-eval">Submit Final Evaluation</button>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

</body>
</html>