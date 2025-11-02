<?php
// view_student.php
session_start();
require_once 'db_connect.php'; 

// --- Helper Function for Errors ---
function abort_with($msg) {
    $safe_msg = addslashes($msg); 
    die("<script>alert('{$safe_msg}'); window.location.href='view_applicants.php';</script>");
}

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html"); 
    exit;
}

// 2. Input Validation (Expects a student_id in the URL)
$student_id = intval($_GET['id'] ?? 0);

if ($student_id <= 0) {
    abort_with('Invalid student profile ID provided.');
}

$student_data = null;

// 3. Fetch Student Details (NOW GETTING EMAIL/PHONE FROM THE STUDENT TABLE)
// We only need the student table for all necessary profile info
$stmt = $mysqli->prepare("
    SELECT 
        first_name, 
        last_name, 
        course, 
        year_level, 
        description, 
        email,        
        phone_number
    FROM student
    WHERE student_id = ? 
    LIMIT 1
");

if (!$stmt) {
    error_log("MySQL Prepare Error: " . $mysqli->error);
    abort_with('A database error occurred while fetching the student profile.');
}

$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $mysqli->close();
    abort_with('Student profile not found.');
}

$student_data = $result->fetch_assoc();

$stmt->close();
$mysqli->close();

// Extract and sanitize data for display
$full_name = htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']);
$course = htmlspecialchars($student_data['course']);
$year_level = htmlspecialchars($student_data['year_level']);
$description = htmlspecialchars($student_data['description']);
$email = htmlspecialchars($student_data['email']);
$phone = htmlspecialchars($student_data['phone_number']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Profile: <?php echo $full_name; ?> | Career Path</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="companydashboard.css">
    <link rel="stylesheet" href="applicants.css"> 
    <style>
        /* Custom styles for profile view (reused from previous step) */
        .profile-card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 40px;
            margin: 0 auto;
            max-width: 800px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        .profile-header {
            text-align: center;
            padding-bottom: 20px;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
        }
        .profile-header h1 {
            color: rgb(32, 64, 227);
            font-size: 32px;
            margin: 10px 0 5px 0;
        }
        .profile-header p {
            font-size: 18px;
            color: #555;
        }
        .detail-section {
            margin-bottom: 30px;
            padding: 20px;
            border-left: 5px solid #f0f4ff;
            background-color: #f7faff;
            border-radius: 8px;
        }
        .detail-section h2 {
            font-size: 20px;
            color: #333;
            margin-top: 0;
            margin-bottom: 15px;
            font-weight: 700;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 8px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .info-grid p {
            margin: 5px 0;
            font-size: 15px;
            color: #444;
        }
        .info-grid strong {
            color: #222;
            font-weight: 600;
        }
        .description-box {
            line-height: 1.7;
            white-space: pre-wrap;
            color: #555;
            font-style: italic;
        }
        .action-link {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <aside class="sidebar">
        <div class="logo"><h2>Career Path</h2></div>
        <nav class="menu">
            <a href="companydashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="#"><i class="fa-solid fa-briefcase"></i> Post Internship</a>
            <a href="view_applicants.php" class="active"><i class="fa-solid fa-users"></i> View Applicants</a>
            <a href="#"><i class="fa-solid fa-user-circle"></i> Profile</a>
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <h1>Applicant Profile Review</h1>
        </header>

        <div class="dashboard-body">
            <div class="profile-card">
                <div class="profile-header">
                    <h1><?php echo $full_name; ?></h1>
                    <p><i class="fa-solid fa-graduation-cap"></i> Applicant ID: **<?php echo $student_id; ?>**</p>
                </div>

                <div class="detail-section">
                    <h2><i class="fa-solid fa-user-graduate"></i> Academic Details</h2>
                    <div class="info-grid">
                        <p><strong>Course:</strong> <?php echo $course; ?></p>
                        <p><strong>Year Level:</strong> <?php echo $year_level; ?></p>
                    </div>
                </div>

                <div class="detail-section">
                    <h2><i class="fa-solid fa-address-card"></i> Contact Information</h2>
                    <div class="info-grid">
                        <p><strong>Email:</strong> <a href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a></p>
                        <p><strong>Phone:</strong> <?php echo $phone; ?></p>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h2><i class="fa-solid fa-file-lines"></i> Student Description</h2>
                    <p class="description-box"><?php echo nl2br($description); ?></p>
                </div>

                <div class="action-link">
                    <a href="view_applicants.php" class="btn-primary" style="display: inline-block;">
                        <i class="fa-solid fa-arrow-left"></i> Back to Applicants List
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>
</body>
</html>