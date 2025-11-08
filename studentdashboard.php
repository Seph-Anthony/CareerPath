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

// --- 2. Fetch Student Personal Data ---
$user_id = $_SESSION['user_id'];
$fullname = "Student Name";
$course = "N/A";
$year_level = "N/A";

$stmt_student = $mysqli->prepare("SELECT first_name, last_name, course, year_level FROM student WHERE user_id = ?");
if ($stmt_student) {
    $stmt_student->bind_param('i', $user_id);
    $stmt_student->execute();
    $result_student = $stmt_student->get_result();
    if ($result_student->num_rows === 1) {
        $student_data = $result_student->fetch_assoc();
        $fullname = htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']);
        $course = htmlspecialchars($student_data['course']);
        $year_level = htmlspecialchars($student_data['year_level']);
    }
    $stmt_student->close();
}

// --- 3. Fetch All Active OJT Postings ---
$job_posts = [];
$stmt_posts = $mysqli->prepare("
    SELECT 
        p.posting_id, 
        p.title, 
        p.description,
        p.slot_available,
        c.company_name
    FROM intern_posting p 
    JOIN company c ON p.company_id = c.company_id 
    WHERE p.status = 'Active' 
    ORDER BY p.create_at DESC
");

if ($stmt_posts) {
    $stmt_posts->execute();
    $result_posts = $stmt_posts->get_result();
    while ($row = $result_posts->fetch_assoc()) {
        $job_posts[] = $row;
    }
    $stmt_posts->close();
}

// --- 4. Placeholder Data for Status Cards ---
$hours_logged = 120;
$total_hours = 480;
$applications_submitted = 5;
$interviews_scheduled = 2;
$current_status = "Seeking Placement"; 

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Career Path</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="studentdashboard.css">
    
</head>
<body>

    <div class="sidebar">
        <div class="logo">Career Path</div>
        <nav class="menu">
            <a href="studentdashboard.php" class="nav-link active"><i class="fa-solid fa-house"></i> Dashboard</a>
            
            <a href="studentdashboard.php#job_listings" class="nav-link"><i class="fa-solid fa-briefcase"></i> Apply for OJT</a>
            <a href="my_applications.php" class="nav-link"><i class="fa-solid fa-file-contract"></i> My Applications</a>
            
            <a href="daily_log_submission.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> Daily Log</a> 
            
            <a href="student_tasks.php" class="nav-link"><i class="fa-solid fa-list-check"></i> My Tasks</a>
            <a href="#" class="nav-link"><i class="fa-solid fa-user-circle"></i> Profile & Resume</a>
            <a href="index.html" class="nav-link"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="header">
            <h1>Welcome, <?php echo $fullname; ?>!</h1>
            <div class="user-info">
                 <i class="fa-solid fa-user-circle"></i> <?php echo $fullname; ?>
            </div>
        </header>

        <div class="dashboard-body">
            
            <section class="grid-container">
                <div class="status-card main-status-card">
                    <i class="fa-solid fa-clock"></i>
                    <div>
                        <p>OJT Hours Logged</p>
                        <strong><?php echo $hours_logged; ?> / <?php echo $total_hours; ?></strong>
                    </div>
                </div>
                
                <div class="status-card main-status-card">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <div>
                        <p>Current Status</p>
                        <strong><?php echo $current_status; ?></strong>
                    </div>
                </div>

                <div class="status-card">
                    <i class="fa-solid fa-paper-plane"></i>
                    <div>
                        <p>Applications Sent</p>
                        <strong><?php echo $applications_submitted; ?></strong>
                    </div>
                </div>
                
                <div class="status-card">
                    <i class="fa-solid fa-calendar-check"></i>
                    <div>
                        <p>Interviews Scheduled</p>
                        <strong><?php echo $interviews_scheduled; ?></strong>
                    </div>
                </div>
            </section>

            <div class="available-jobs section-container" id="job_listings">
                <h2><i class="fas fa-handshake"></i> Active Internship Opportunities (<?php echo count($job_posts); ?>)</h2>
                <div> 
                
                <?php if (!empty($job_posts)): ?>
                    <?php foreach ($job_posts as $post): ?>
                        <div class="job-listing-card">
                            <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p>
                                <strong>Company:</strong> <?php echo htmlspecialchars($post['company_name']); ?> | 
                                <strong>Slots:</strong> <?php echo htmlspecialchars($post['slot_available']); ?> | 
                                <strong>Status:</strong> Active
                            </p>
                            <a href="view_post.php?id=<?php echo htmlspecialchars($post['posting_id']); ?>">View Details</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="job-listing-card empty-state">
                        <h3>No Active OJT Postings Yet</h3>
                        <p>Companies haven't posted new opportunities. Check back later!</p>
                    </div>
                <?php endif; ?>

                </div>
            </div>

            <div class="grid-container" style="grid-template-columns: 1fr 1fr; margin-bottom: 0;">
                
                <div class="section-container">
                    <h2><i class="fas fa-user-cog"></i> My Profile Summary</h2>
                    <p><strong>Name:</strong> <?php echo $fullname; ?></p>
                    <p><strong>Course:</strong> <?php echo $course; ?></p>
                    <p><strong>Year Level:</strong> <?php echo $year_level; ?></p>
                    <p style="margin-top: 15px;"><a href="#" style="font-weight: 600;"><i class="fas fa-edit"></i> Edit Profile & Upload Resume</a></p>
                </div>

                <div class="section-container">
                    <h2><i class="fas fa-chalkboard-teacher"></i> OJT Coordinator</h2>
                    <p><strong>Name:</strong> Ms. Maria Reyes</p>
                    <p><strong>Department:</strong> Information Technology</p>
                    <p><strong>Contact:</strong> reyes@scc.edu.ph</p>
                    <p><strong>Phone:</strong> 09XX-XXX-XXXX</p>
                </div>

            </div>
        </div>
    </div>
</body>
</html>