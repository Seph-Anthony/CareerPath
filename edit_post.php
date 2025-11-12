<?php
// edit_post.php - Company form to edit an existing internship posting
session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html"); 
    exit;
}

// 2. Get Posting ID from URL
$posting_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$posting_id) {
    die("<script>alert('Invalid Posting ID provided.'); window.location.href='companydashboard.php';</script>");
}

$user_id = $_SESSION['user_id'];
$company_data = null;
$post_data = null;
$message = '';
$error = false;

// 3. Fetch Company ID and Name
$stmt_company = $mysqli->prepare("
    SELECT c.company_id, c.company_name, u.status 
    FROM company c JOIN users u ON c.user_id = u.user_id 
    WHERE c.user_id = ? 
    LIMIT 1
");

if ($stmt_company) {
    $stmt_company->bind_param('i', $user_id);
    $stmt_company->execute();
    $result_company = $stmt_company->get_result();
    $company_data = $result_company->fetch_assoc();
    $stmt_company->close();
    
    if (!$company_data || $company_data['status'] !== 'Active') {
        die("Account Error: Your account is not active or company profile not found.");
    }
} else {
    die("Database Error: Could not fetch company info.");
}

$company_id = $company_data['company_id'];
$company_name = htmlspecialchars($company_data['company_name']);


// 4. Fetch Existing Post Data
// *** FIX APPLIED HERE: Changed 'slots' to 'slot_available' ***
$stmt_post = $mysqli->prepare("
    SELECT title, description, requirements, slot_available, status
    FROM intern_posting 
    WHERE posting_id = ? AND company_id = ? 
    LIMIT 1
");

if ($stmt_post) {
    $stmt_post->bind_param('ii', $posting_id, $company_id);
    $stmt_post->execute();
    $result_post = $stmt_post->get_result();
    $post_data = $result_post->fetch_assoc();
    $stmt_post->close();
    
    if (!$post_data) {
        die("<script>alert('Internship posting not found or you do not have permission to edit it.'); window.location.href='companydashboard.php';</script>");
    }
}


// 5. Handle Form Submission (Update Logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    // Renamed variable for clarity
    $slot_available = filter_input(INPUT_POST, 'slot_available', FILTER_VALIDATE_INT);
    $current_status = $post_data['status']; 

    if (empty($title) || empty($description) || empty($requirements) || $slot_available === false || $slot_available <= 0) {
        $error = true;
        $message = "Please fill in all fields correctly.";
    } else {
        // If an Active/Pending post is edited, mark it for review again for coordinator oversight
        $new_status = ($current_status === 'Active') ? 'Pending Review' : $current_status; 
        
        // *** FIX APPLIED HERE: Changed 'slots' to 'slot_available' ***
        $update_query = "
            UPDATE intern_posting 
            SET title = ?, description = ?, requirements = ?, slot_available = ?, status = ? 
            WHERE posting_id = ? AND company_id = ?
        ";
        
        $stmt_update = $mysqli->prepare($update_query);
        
        if ($stmt_update) {
            // *** FIX APPLIED HERE: Using $slot_available variable ***
            $stmt_update->bind_param('sssisii', $title, $description, $requirements, $slot_available, $new_status, $posting_id, $company_id);
            
            if ($stmt_update->execute()) {
                $message = "Posting updated successfully. Status reset to **{$new_status}** for coordinator review.";
                // Refresh $post_data to show new status and values
                $post_data['title'] = $title;
                $post_data['description'] = $description;
                $post_data['requirements'] = $requirements;
                // *** FIX APPLIED HERE: Using correct array key ***
                $post_data['slot_available'] = $slot_available; 
                $post_data['status'] = $new_status;
            } else {
                $error = true;
                $message = "Database Error: Could not update posting. " . $mysqli->error;
            }
            $stmt_update->close();
        } else {
            $error = true;
            $message = "Database Preparation Error: " . $mysqli->error;
        }
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post | <?php echo $company_name; ?></title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="companydashboard.css"> 
</head>
<body>

<div class="dashboard-container">
    <aside class="sidebar">
        <div class="logo"><h2>Career Path</h2></div>
        <nav class="menu">
            <a href="companydashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="post_internship.php"><i class="fa-solid fa-file-circle-plus"></i> Post Internship</a>
            <a href="manage_applicants.php"><i class="fa-solid fa-users"></i> View Applicants</a>
            <a href="intern_progress.php"><i class="fa-solid fa-chart-line"></i> Intern Progress</a>
            <a href="#"><i class="fa-solid fa-user-circle"></i> Profile</a>
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <h1>Edit Internship Post: <?php echo htmlspecialchars($post_data['title']); ?></h1>
        </header>

        <div class="dashboard-body">
            <div class="form-container">
                <h2><i class="fa-solid fa-pen-to-square"></i> Edit Posting Details</h2>
                
                <?php if ($message): ?>
                    <div class="alert <?php echo $error ? 'alert-error' : 'alert-success'; ?>" style="margin-bottom: 20px;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div style="background: #fff3cd; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                    <i class="fa-solid fa-info-circle"></i> Current Status: <strong><?php echo htmlspecialchars($post_data['status']); ?></strong>. Any significant edit will reset status to "Pending Review".
                </div>

                <form action="edit_post.php?id=<?php echo $posting_id; ?>" method="POST">
                    
                    <div class="form-grid">
                        
                        <div class="form-group full-width">
                            <label for="title">Internship Title</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post_data['title']); ?>" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="description">Job Description</label>
                            <textarea id="description" name="description" required><?php echo htmlspecialchars($post_data['description']); ?></textarea>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="requirements">Requirements</label>
                            <textarea id="requirements" name="requirements" required><?php echo htmlspecialchars($post_data['requirements']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="slot_available">Number of Slots Available</label>
                            <input type="number" id="slot_available" name="slot_available" min="1" value="<?php echo htmlspecialchars($post_data['slot_available']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="duration">Duration (Informational - not stored in DB)</label>
                            <input type="text" id="duration" name="duration_info" placeholder="e.g., 480 Hours / 3 Months">
                        </div>

                        <button type="submit" class="submit-btn">Save Changes</button>

                    </div>
                </form>
                <div class="actions" style="justify-content: flex-end; margin-top: 15px;">
                    <a href="process_post.php?action=delete&id=<?php echo $posting_id; ?>" onclick="return confirm('Are you sure you want to delete this posting? This action cannot be undone.');" class="remove">Delete Post</a>
                    <a href="companydashboard.php" class="actions" style="background-color: #555;">Cancel</a>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>