<?php
// view_post.php
session_start();
require_once 'db_connect.php'; 

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: signinstudent.html"); 
    exit;
}

// 2. Validate Posting ID
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    die("<script>alert('Invalid internship post ID.'); window.location.href='studentdashboard.php';</script>");
}

$posting_id = $_GET['id'];
$post_details = null;
$application_status = null; // To check if the student has already applied

// --- 3. Fetch Job Post Details ---
$stmt_post = $mysqli->prepare("
    SELECT 
        p.posting_id, 
        p.title, 
        p.description, 
        p.requirements, 
        p.slot_available, 
        p.create_at,
        c.company_name
    FROM intern_posting p 
    JOIN company c ON p.company_id = c.company_id 
    WHERE p.posting_id = ? AND p.status = 'Active' 
    LIMIT 1
");

if ($stmt_post) {
    $stmt_post->bind_param('i', $posting_id);
    $stmt_post->execute();
    $result_post = $stmt_post->get_result();
    $post_details = $result_post->fetch_assoc();
    $stmt_post->close();
}

if (!$post_details) {
    die("<script>alert('Internship post not found or is no longer active.'); window.location.href='studentdashboard.php';</script>");
}

// --- 4. Check if Student has Already Applied ---
$student_user_id = $_SESSION['user_id'];

// Get the actual student_id from the session user_id
$stmt_student = $mysqli->prepare("SELECT student_id FROM student WHERE user_id = ? LIMIT 1");
$stmt_student->bind_param('i', $student_user_id);
$stmt_student->execute();
$result_student = $stmt_student->get_result();
$student_data = $result_student->fetch_assoc();
$student_id = $student_data['student_id'] ?? null;
$stmt_student->close();

if ($student_id) {
    $stmt_check = $mysqli->prepare("
        SELECT status 
        FROM intern_application 
        WHERE student_id = ? AND posting_id = ?
    ");
    $stmt_check->bind_param('ii', $student_id, $posting_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $application_data = $result_check->fetch_assoc();
    
    if ($application_data) {
        $application_status = htmlspecialchars($application_data['status']);
    }
    $stmt_check->close();
}

// --- 5. Determine Button State and Text ---
$button_html = '';
if ($application_status) {
    // Already applied
    $button_html = "<p class='status-applied'>You have already applied for this position. Current Status: <strong>{$application_status}</strong></p>";
} else if ($post_details['slot_available'] <= 0) {
    // No slots
    $button_html = "<p class='status-closed'>This posting is currently full or closed.</p>";
} else {
    // Ready to apply
    $button_html = "
        <form action='process_application.php' method='POST'>
            <input type='hidden' name='posting_id' value='{$posting_id}'>
            <input type='hidden' name='student_id' value='{$student_id}'>
            <button type='submit' class='apply-btn'>Apply for Internship</button>
        </form>
    ";
}


$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post_details['title']); ?> | Job Details</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="view_post.css"> 
</head>
<body>

    <div class="sidebar">
        <div class="logo">Career Path</div>
        <nav class="menu">
            <a href="studentdashboard.php" class="nav-link active"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="#" class="nav-link"><i class="fa-solid fa-briefcase"></i> Apply for OJT</a>
            <a href="#" class="nav-link"><i class="fa-solid fa-file-contract"></i> My Applications</a>
            <a href="#" class="nav-link"><i class="fa-solid fa-user-circle"></i> Profile & Resume</a>
            <a href="#" class="nav-link"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="header">
            <h1>Internship Details</h1>
        </header>

        <div class="dashboard-body">
            <div class="post-detail-container">
                <h1><?php echo htmlspecialchars($post_details['title']); ?></h1>
                <p class="company-name"><i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($post_details['company_name']); ?></p>

                <div class="meta-info">
                    <span><i class="fa-solid fa-user-tie"></i> Slots Available: <strong><?php echo htmlspecialchars($post_details['slot_available']); ?></strong></span>
                    <span><i class="fa-solid fa-calendar-alt"></i> Posted On: <strong><?php echo date('M d, Y', strtotime($post_details['create_at'])); ?></strong></span>
                </div>
                
                <h2><i class="fa-solid fa-clipboard-list"></i> Job Description</h2>
                <div class="description-content">
                    <?php echo nl2br(htmlspecialchars($post_details['description'])); // nl2br preserves line breaks ?>
                </div>

                <h2><i class="fa-solid fa-cogs"></i> Key Requirements & Skills</h2>
                <div class="requirements-content">
                    <?php echo nl2br(htmlspecialchars($post_details['requirements'])); // nl2br preserves line breaks ?>
                </div>
                
                <div class="apply-section">
                    <?php echo $button_html; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>