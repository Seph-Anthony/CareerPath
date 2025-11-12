<?php
// companydashboard.php - FINAL WORKING VERSION (Aesthetic & Functional Fixes)
session_start();
require_once 'db_connect.php'; 

// 1. Authentication and Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html"); 
    exit;
}

// 2. Fetch Company Name, ID, AND Account Status (CRITICAL SECURITY FIX)
$user_id = $_SESSION['user_id'];
$company_name = "Company Representative"; 
$company_id = null; 

$stmt_company = $mysqli->prepare("
    SELECT 
        c.company_id, 
        c.company_name,
        u.status AS account_status  
    FROM 
        company c
    JOIN 
        users u ON c.user_id = u.user_id
    WHERE 
        c.user_id = ? 
    LIMIT 1
");

if ($stmt_company) {
    $stmt_company->bind_param('i', $user_id);
    $stmt_company->execute();
    $result_company = $stmt_company->get_result();
    
    if ($result_company->num_rows === 1) {
        $data = $result_company->fetch_assoc();
        $company_name = htmlspecialchars($data['company_name']);
        $company_id = $data['company_id'];
        $account_status = $data['account_status']; 

        // Security check: Redirect if status is not 'Active'
        if ($account_status !== 'Active') {
            die("Your account is currently **" . htmlspecialchars($account_status) . "** and cannot access the dashboard. Please contact the coordinator.");
        }

    }
    $stmt_company->close();
}


// 3. Fetch Active Posts for the listing and count
$active_posts = [];
$active_posts_count = 0;

if ($company_id) {
    // Fetch posts specifically for this company
    $stmt_posts = $mysqli->prepare("
        SELECT posting_id, title, description, create_at 
        FROM intern_posting 
        WHERE company_id = ? 
        AND status = 'Active' 
        ORDER BY create_at DESC
        LIMIT 5
    ");

    if ($stmt_posts) {
        $stmt_posts->bind_param('i', $company_id);
        $stmt_posts->execute();
        $result_posts = $stmt_posts->get_result();
        while ($row = $result_posts->fetch_assoc()) {
            $row['create_at_formatted'] = date('M d, Y', strtotime($row['create_at']));
            $active_posts[] = $row;
        }
        $active_posts_count = count($active_posts);
        $stmt_posts->close();
    }
}


// 4. Dashboard Statistics (Placeholders)
$total_applicants = 20; 
$current_interns = 5;
$completed_interns = 3;

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Company Dashboard | Career Path</title>
  <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="companydashboard.css">
</head>
<body>

<div class="dashboard-container">

  <aside class="sidebar">
    <div class="logo">
      <h2>Career Path</h2>
    </div>

    <nav class="menu">
        <a href="companydashboard.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="post_internship.php"><i class="fa-solid fa-file-circle-plus"></i> Post Internship</a>
        <a href="manage_applicants.php"><i class="fa-solid fa-users"></i> View Applicants</a>
        <a href="company_log_approval.php"><i class="fa-solid fa-file-signature"></i> Approve Daily Logs</a>
        <a href="intern_progress.php"><i class="fa-solid fa-chart-line"></i> Intern Progress</a>
        <a href="#"><i class="fa-solid fa-user-circle"></i> Profile</a>
        <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>

  <main class="main-content">
    <header class="topbar">
        <h1>Welcome, <?php echo $company_name; ?>!</h1>
            <div class="user-profile">
               <i class="fa-solid fa-user-circle"></i> <?php echo $company_name; ?>
            </div>
    </header>

    <div class="dashboard-body">
        
        <section class="status-cards grid-container">
            <div class="card">
                <h3>Total Applicants</h3>
                <div>
                    <p><?php echo $total_applicants; ?></p>
                    <i class="fa-solid fa-users icon-blue"></i>
                </div>
            </div>
            <div class="card">
                <h3>Active Posts</h3>
                <div>
                    <p><?php echo $active_posts_count; ?></p>
                    <i class="fa-solid fa-briefcase icon-blue"></i>
                </div>
            </div>
            <div class="card">
                <h3>Current Interns</h3>
                <div>
                    <p><?php echo $current_interns; ?></p>
                    <i class="fa-solid fa-user-tie icon-blue"></i>
                </div>
            </div>
            <div class="card">
                <h3>Completed Interns</h3>
                <div>
                    <p><?php echo $completed_interns; ?></p>
                    <i class="fa-solid fa-flag-checkered icon-blue"></i>
                </div>
            </div>
        </section>

        <div class="dashboard-actions">
            <a href="post_internship.php" class="cta-button">
                <i class="fa-solid fa-plus-circle"></i> Post New Internship
            </a>
        </div>
        
        <section class="dashboard-post">
            <div class="internship-posts">
                <h2><i class="fa-solid fa-briefcase"></i> Active Internship Posts</h2>

                <?php if (!empty($active_posts)): ?>
                    <?php foreach ($active_posts as $post): ?>
                    <div class="post-card">
                        <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                        <p><strong>Posted:</strong> <?php echo htmlspecialchars($post['create_at_formatted']); ?> | <strong>ID:</strong> <?php echo htmlspecialchars($post['posting_id']); ?></p>
                        <p><strong>Description:</strong> <?php echo substr(htmlspecialchars($post['description']), 0, 150) . (strlen($post['description']) > 150 ? '...' : ''); ?></p>
                        <div class="actions">
                            <a href="edit_post.php?id=<?php echo htmlspecialchars($post['posting_id']); ?>" class="edit button">Edit Post</a>
                            <button class="remove">Remove</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="post-card">
                        <h3>No Active Posts</h3>
                        <p>Click the "Post New Internship" button above to get started!</p>
                    </div>
                <?php endif; ?>

            </div>
        </section>
    </div>
  </main>
</div>

</body>
</html>