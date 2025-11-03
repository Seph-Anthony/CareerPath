<?php
// view_applicants.php
session_start();
require_once 'db_connect.php'; 

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html"); 
    exit;
}

$user_id = $_SESSION['user_id'];
$company_id = null;
$applicants_data = [];

// --- 2. Get the Company ID ---
$stmt_cid = $mysqli->prepare("SELECT company_id FROM company WHERE user_id = ? LIMIT 1");
$stmt_cid->bind_param('i', $user_id);
$stmt_cid->execute();
$result_cid = $stmt_cid->get_result();
$company_data = $result_cid->fetch_assoc();
$stmt_cid->close();

if ($company_data) {
    $company_id = $company_data['company_id'];
} else {
    die("<script>alert('Error: Company profile not found.'); window.location.href='companydashboard.php';</script>");
}

// --- 3. Fetch All Applications for Company's Posts ---
// We join intern_posting (p), intern_application (a), and student (s) tables.
$sql = "
    SELECT 
        p.title AS post_title,
        p.posting_id,
        a.application_id,
        a.application_date,
        a.status AS application_status,
        s.student_id,
        s.first_name,
        s.last_name,
        s.course,
        s.year_level
    FROM intern_posting p
    JOIN intern_application a ON p.posting_id = a.posting_id
    JOIN student s ON a.student_id = s.student_id
    WHERE p.company_id = ?
    ORDER BY p.posting_id, a.application_date DESC
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $company_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $applicants_data[] = $row;
}

$stmt->close();
$mysqli->close();

// Group applications by post_title for better display
$posts_with_applicants = [];
foreach ($applicants_data as $applicant) {
    $title = htmlspecialchars($applicant['post_title']);
    if (!isset($posts_with_applicants[$title])) {
        $posts_with_applicants[$title] = [
            'posting_id' => $applicant['posting_id'],
            'applicants' => []
        ];
    }
    $posts_with_applicants[$title]['applicants'][] = $applicant;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applicants | Career Path</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="companydashboard.css">
    <link rel="stylesheet" href="applicants.css"> </head>
<body>

<div class="dashboard-container">

    <aside class="sidebar">
        <div class="logo"><h2>Career Path</h2></div>
        <nav class="menu">
            <a href="companydashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="post_internship.php"><i class="fa-solid fa-briefcase"></i> Post Internship</a>
            <a href="#" class="active"><i class="fa-solid fa-users"></i> View Applicants</a> <a href="#"><i class="fa-solid fa-user-circle"></i> Profile</a>
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <h1>Manage Applications</h1>
        </header>

        <div class="dashboard-body">
            
            <?php if (empty($posts_with_applicants)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-box-open"></i>
                    <h2>No Applications Yet</h2>
                    <p>It looks like no students have applied to your internship posts. Share your listings to start receiving applications!</p>
                </div>
            <?php else: ?>
                
                <?php foreach ($posts_with_applicants as $title => $data): ?>
                    <div class="applicant-post-section">
                        <h2><i class="fa-solid fa-list-check"></i> Applications for: <?php echo $title; ?> (ID: <?php echo $data['posting_id']; ?>)</h2>
                        
                        <div class="applicant-list-grid">
                            <?php foreach ($data['applicants'] as $applicant): ?>
                                <div class="applicant-card status-<?php echo strtolower(str_replace(' ', '-', $applicant['application_status'])); ?>">
                                    <div class="applicant-header">
                                        <h3><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></h3>
                                        <span class="app-status"><?php echo htmlspecialchars($applicant['application_status']); ?></span>
                                    </div>
                                    <div class="applicant-details">
                                        <p><i class="fa-solid fa-graduation-cap"></i> <?php echo htmlspecialchars($applicant['course']); ?> - <?php echo htmlspecialchars($applicant['year_level']); ?></p>
                                        <p><i class="fa-solid fa-calendar-check"></i> Applied: <?php echo date('M d, Y', strtotime($applicant['application_date'])); ?></p>
                                    </div>
                                    <div class="applicant-actions">
                                        <a href="view_student.php?id=<?php echo $applicant['student_id']; ?>" class="btn-primary">View Student Profile</a>
                                        <button class="btn-secondary" onclick="document.getElementById('status_form_<?php echo $applicant['application_id']; ?>').style.display='block'">Update Status</button>
                                    </div>

                                    <form id="status_form_<?php echo $applicant['application_id']; ?>" class="status-update-form" style="display:none;" action="update_application_status.php" method="POST">
                                        <input type="hidden" name="application_id" value="<?php echo $applicant['application_id']; ?>">
                                        <label for="status_<?php echo $applicant['application_id']; ?>">New Status:</label>
                                        <select name="new_status" id="status_<?php echo $applicant['application_id']; ?>" required>
                                            <option value="Reviewed">Reviewed</option>
                                            <option value="Interview Scheduled">Interview Scheduled</option>
                                            <option value="Hired">Hired</option>
                                            <option value="Rejected">Rejected</option>
                                        </select>
                                        <button type="submit" class="btn-update">Save</button>
                                    </form>

                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>