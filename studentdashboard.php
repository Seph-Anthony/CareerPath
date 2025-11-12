<?php
// studentdashboard.php - Displays Active Internship Posts
session_start();
require_once 'db_connect.php'; 

// 1. Authentication and Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    // If not logged in or not a student, redirect to the login page
    header("Location: signinstudent.html"); 
    exit;
}

// --- 2. Fetch Student Personal Data and Resume Status ---
$user_id = $_SESSION['user_id'];
$fullname = "Student Name";
$course = "N/A";
$year_level = "N/A";
$student_id = null; // Initialize student_id

// A. Fetch Student Profile and ID
$stmt_student = $mysqli->prepare("
    SELECT 
        student_id,
        first_name, 
        last_name, 
        course, 
        year_level
    FROM 
        student 
    WHERE 
        user_id = ?
");

if ($stmt_student) {
    $stmt_student->bind_param('i', $user_id);
    $stmt_student->execute();
    $result_student = $stmt_student->get_result();
    if ($result_student->num_rows === 1) {
        $student_data = $result_student->fetch_assoc();
        $student_id = $student_data['student_id'];
        $fullname = htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']);
        $course = htmlspecialchars($student_data['course']);
        $year_level = htmlspecialchars($student_data['year_level']);
    }
    $stmt_student->close();
}

// B. Fetch Resume Approval Status from the new table
// NOTE: This should ideally check for 'Information' if that is the final document type used in the DB
$resume_status = 'Not Uploaded'; // Default status
if ($student_id) {
    // Query to find the latest 'Resume' status
    $stmt_status = $mysqli->prepare("
        SELECT approval_status 
        FROM student_requirements 
        WHERE student_id = ? AND document_type = 'Resume'
        ORDER BY upload_date DESC
        LIMIT 1
    ");
    if ($stmt_status) {
        $stmt_status->bind_param('i', $student_id);
        $stmt_status->execute();
        $result_status = $stmt_status->get_result();
        if ($result_status->num_rows === 1) {
            $status_data = $result_status->fetch_assoc();
            $resume_status = htmlspecialchars($status_data['approval_status']);
        }
        $stmt_status->close();
    }
}


// --- 3. Fetch Active Intern Postings (No changes needed for this part's logic) ---
$intern_posts = [];
$query = "
    SELECT 
        ip.posting_id, 
        ip.title, 
        ip.description, 
        ip.requirements, 
        ip.slot_available,
        c.company_name,
        (
            SELECT ia.status 
            FROM intern_application ia 
            WHERE ia.posting_id = ip.posting_id AND ia.student_id = ?
        ) as application_status
    FROM 
        intern_posting ip
    JOIN 
        company c ON ip.company_id = c.company_id
    WHERE 
        ip.status = 'Active' 
    ORDER BY 
        ip.create_at DESC
";

$stmt_posts = $mysqli->prepare($query);
if ($stmt_posts) {
    $stmt_posts->bind_param('i', $student_id); // Use student_id here
    $stmt_posts->execute();
    $result_posts = $stmt_posts->get_result();
    while ($row = $result_posts->fetch_assoc()) {
        $intern_posts[] = $row;
    }
    $stmt_posts->close();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | <?php echo $fullname; ?></title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="companydashboard.css"> 
    <link rel="stylesheet" href="studentdashboard.css">
    <style>
        /* Add specific styles for the resume status tag if needed */
        .resume-status-tag {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-Approved { background-color: #d4edda; color: #155724; }
        .status-Pending { background-color: #fff3cd; color: #856404; }
        .status-Rejected { background-color: #f8d7da; color: #721c24; }
        .status-Not-Uploaded { background-color: #f0f0f0; color: #555; }
    </style>
</head>
<body>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">
                <h2>Career Path</h2>
            </div>
            <nav class="menu">
                <a href="studentdashboard.php" class="nav-link active"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="#active_post" class="nav-link"><i class="fa-solid fa-briefcase"></i> Apply for OJT</a>
                <a href="my_applications.php" class="nav-link"><i class="fa-solid fa-file-contract"></i> My Applications</a>
                <a href="daily_log_submission.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> Daily Log</a>
                <a href="student_tasks.php" class="nav-link"><i class="fa-solid fa-list-check"></i> My Tasks</a>
                <a href="manage_requirements.php"><i class="fa-solid fa-file-upload"></i> Manage Requirements</a> 
                <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <div class="main-content">
            <header class="topbar">
                <h1>Welcome Back, <?php echo $fullname; ?>!</h1>
                <p>Track your OJT progress and find new opportunities.</p>
            </header>

            <div class="dashboard-body">
                
                <div class="grid-container" style="grid-template-columns: 1fr; margin-bottom: 30px;">
                    
                    <div class="section-container">
                        <h2><i class="fas fa-user-cog"></i> My Profile Summary</h2>
                        <p><strong>Name:</strong> <?php echo $fullname; ?></p>
                        <p><strong>Course:</strong> <?php echo $course; ?></p>
                        <p><strong>Year Level:</strong> <?php echo $year_level; ?></p>
                        <p style="margin-top: 15px;">
                            <span style="font-size: 13px; margin-right: 10px;">
                                Resume Status: 
                                <span class="resume-status-tag status-<?php echo str_replace(' ', '-', $resume_status); ?>">
                                    <?php echo $resume_status; ?>
                                </span>
                            </span>
                            <a href="manage_requirements.php" style="font-weight: 600;"><i class="fas fa-edit"></i> Manage Requirements</a>
                        </p>
                    </div>

                    </div>
                <div class="section-container" id="active_post">
                    <h2><i class="fas fa-briefcase"></i> Active OJT Postings</h2>
                    
                    <?php if (!empty($intern_posts)): ?>
                        <div class="job-listing-grid">
                            <?php foreach ($intern_posts as $post): ?>
                                <div class="job-listing-card">
                                    <h3 class="job-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                    <p class="company-name"><i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($post['company_name']); ?></p>
                                    <p class="slots">Slots: <?php echo htmlspecialchars($post['slot_available']); ?></p>
                                    <p class="description"><?php echo substr(htmlspecialchars($post['description']), 0, 150); ?>...</p>
                                    <div class="card-footer">
                                        <?php if (!empty($post['application_status'])): ?>
                                            <span class="status-tag status-<?php echo strtolower($post['application_status']); ?>">
                                                Applied (<?php echo htmlspecialchars($post['application_status']); ?>)
                                            </span>
                                        <?php else: ?>
                                            <a href="view_post.php?id=<?php echo $post['posting_id']; ?>" class="btn-view">
                                                <i class="fa-solid fa-search"></i> View & Apply
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="job-listing-card empty-state">
                            <h3>No Active OJT Postings Yet</h3>
                            <p>Companies haven't posted new opportunities. Check back later!</p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</body>
</html>