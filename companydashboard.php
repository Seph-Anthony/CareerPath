<?php
// companydashboard.php - FINAL WORKING VERSION
session_start();
require_once 'db_connect.php'; 

// 1. Authentication and Authorization Check
// 🔥 CRITICAL FIX: The role MUST be 'company'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    // If not logged in or not a company, redirect to the company login page
    header("Location: signincompany.html"); 
    exit;
}

// 2. Fetch Company Name (for the header) and ID
$user_id = $_SESSION['user_id'];
$company_name = "Company Representative"; // Default in case of error
$company_id = null; 

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

// 3. Fetch Active Posts (for the listing)
$active_posts = [];
$active_posts_count = 0;

if ($company_id) {
    // Fetch posts specifically for this company
    $stmt_posts = $mysqli->prepare("
        SELECT posting_id, title, description 
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
            $active_posts[] = $row;
        }
        $active_posts_count = count($active_posts);
        $stmt_posts->close();
    }
}


// Placeholder data for status cards (you can replace these with real counts later)
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
  <style>
    /* ADDED: Styling for the new CTA button section for prominence */
    .dashboard-actions {
        display: flex;
        justify-content: flex-end; /* Pushes the button to the right */
        margin-bottom: 25px; 
    }
    .cta-button {
        background-color: rgb(32, 64, 227); /* Main theme blue */
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none; 
        transition: background-color 0.3s, transform 0.2s;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .cta-button:hover {
        background-color: rgb(25, 50, 190);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>
<body>

<div class="dashboard-container">

  <aside class="sidebar">
    <div class="logo">
      <h2>Career Path</h2>
    </div>

    <nav class="menu">
      <a href="companydashboard.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a>
      <a href="post_internship.php"><i class="fa-solid fa-briefcase"></i> Post Internships</a> 
      <a href="#"><i class="fa-solid fa-users"></i> Applicants</a>
      <a href="#"><i class="fa-solid fa-chart-line"></i> Intern Progress</a>
      <a href="#"><i class="fa-solid fa-user"></i> Profile</a>
      <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
  </aside>

  <main class="main-content">
    <header class="topbar">
        <h1>Welcome, <?php echo $company_name; ?>!</h1>
        <div class="user-section">
            <div class="user-profile">
                <span><?php echo $company_name; ?></span>
            </div>
        </div>
    </header>

    <div class="dashboard-body">
        
        <section class="status-cards grid-container">
        <div class="card"><i class="fa-solid fa-users"></i><div><h3>Total Applicants</h3><p><?php echo $total_applicants; ?></p></div></div>
        <div class="card"><i class="fa-solid fa-briefcase"></i><div><h3>Active Posts</h3><p><?php echo $active_posts_count; ?></p></div></div>
        <div class="card"><i class="fa-solid fa-user-tie"></i><div><h3>Current Interns</h3><p><?php echo $current_interns; ?></p></div></div>
        <div class="card"><i class="fa-solid fa-flag-checkered"></i><div><h3>Completed Interns</h3><p><?php echo $completed_interns; ?></p></div></div>
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
                    <p><strong>Posting ID:</strong> <?php echo htmlspecialchars($post['posting_id']); ?></p>
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