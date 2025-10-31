<?php
// studentdashboard.php
require_once 'auth_check.php'; 

// 2. Check if the logged-in user has the correct role ('student')
if ($_SESSION['role'] !== 'student') {
    // If they logged in as a Coordinator/Company but ended up here, deny access.
    header("Location: index.html"); 
    exit;
}

// --- Placeholder Data for Presentation ---
$username = htmlspecialchars($_SESSION['username']);
$fullname = "Juan Dela Cruz"; // Placeholder: In a real app, fetch from `student` table
$course = "BS Information Technology";
$hours_logged = 120;
$total_hours = 480;
$current_status = "Seeking Placement"; // "Currently Interning" / "OJT Completed"
$applications_submitted = 5;
$interviews_scheduled = 2;
// ----------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Career Path</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

    <div class="sidebar">
        <div class="logo">Career Path</div>
        <nav>
            <a href="studentdashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="#" class="nav-link"><i class="fas fa-search"></i> Job Listings</a>
            <a href="#" class="nav-link"><i class="fas fa-tasks"></i> My Applications</a>
            <a href="#" class="nav-link"><i class="fas fa-user-circle"></i> Profile & Resume</a>
            <a href="logout.php" class="nav-link" style="margin-top: auto; color: #ffdddd;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        
        <header class="header">
            <h1>Student Dashboard</h1>
            <div class="user-info">
                <i class="fas fa-user-alt"></i> Logged in as: <a href="#"><?php echo $username; ?></a>
            </div>
        </header>

        <div class="dashboard-body">

            <div class="grid-container">
                <div class="status-card main-status-card">
                    <p style="font-size: 1.1em; font-weight: 600;">Current OJT Status</p>
                    <strong style="color: red;"><?php echo $current_status; ?></strong>
                    <p>Next Step: Start applying for available positions below.</p>
                </div>

                <div class="status-card">
                    <p>OJT Hours Logged</p>
                    <strong><?php echo $hours_logged; ?></strong>
                    <p>of <?php echo $total_hours; ?> required hours</p>
                </div>

                <div class="status-card">
                    <p>Applications Submitted</p>
                    <strong><?php echo $applications_submitted; ?></strong>
                    <p><?php echo $interviews_scheduled; ?> interview(s) scheduled</p>
                </div>
            </div>

            <div class="section-container">
                <h2><i class="fas fa-briefcase"></i> Latest Internship Listings</h2>
                
                <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                    <input type="text" placeholder="Search by Company or Position..." style="padding: 10px; flex-grow: 1; border: 1px solid #ccc; border-radius: 5px;">
                    <button style="background-color: var(--primary-blue); color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Search</button>
                    <select style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                        <option>Filter by Industry</option>
                        <option>IT & Software</option>
                        <option>HR & Admin</option>
                        <option>Accounting</option>
                    </select>
                </div>

                <div class="job-listing-card">
                    <h3>Software Development Intern</h3>
                    <p><strong>Company:</strong> TechSolutions Inc. | <strong>Industry:</strong> IT & Software | <strong>Status:</strong> Accepting Applications</p>
                    <a href="#">View Details</a>
                </div>
                <div class="job-listing-card">
                    <h3>Marketing Assistant (OJT)</h3>
                    <p><strong>Company:</strong> Acme Corp | <strong>Industry:</strong> Marketing | <strong>Status:</strong> Accepting Applications</p>
                    <a href="#">View Details</a>
                </div>
                <div class="job-listing-card">
                    <h3>Accounting Specialist Trainee</h3>
                    <p><strong>Company:</strong> Global Finance Co. | <strong>Industry:</strong> Finance | <strong>Status:</strong> Application Deadline Soon</p>
                    <a href="#">View Details</a>
                </div>

            </div>

            <div class="grid-container" style="grid-template-columns: 1fr 1fr;">
                
                <div class="section-container">
                    <h2><i class="fas fa-user-cog"></i> My Profile Summary</h2>
                    <p><strong>Name:</strong> <?php echo $fullname; ?></p>
                    <p><strong>Course:</strong> <?php echo $course; ?></p>
                    <p><strong>Year Level:</strong> 4th Year</p>
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