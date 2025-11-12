<?php
// view_student_coordinator.php
session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: signincoordinator.html");
    exit;
}

// 2. Get Student ID from URL
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if ($student_id === 0) {
    die("<script>alert('No Student ID provided.'); window.location.href='manage_students.php';</script>");
}

$student_data = null;
$requirements = []; 
$error_message = "";
$message = "";

// --- Message Handling from URL ---
if (isset($_GET['status']) && isset($_GET['msg'])) {
    $status = $_GET['status'];
    $msg = htmlspecialchars($_GET['msg']);
    
    if ($status === 'success') {
        $message = $msg;
    } elseif ($status === 'error') {
        $error_message = $msg;
    }
}


// --- 3. Database Query: Fetch Student Data ---
$query_student = "
    SELECT 
        s.first_name, 
        s.last_name, 
        s.course, 
        s.year_level, 
        s.description, 
        s.email AS student_email,           
        s.phone_number,                     
        u.username,
        u.user_id, 
        u.status AS account_status
    FROM 
        student s
    JOIN 
        users u ON s.user_id = u.user_id
    WHERE
        s.student_id = ? AND u.role = 'student'
    LIMIT 1
"; 

$stmt_student = $mysqli->prepare($query_student);

if ($stmt_student) {
    $stmt_student->bind_param('i', $student_id);
    $stmt_student->execute();
    $result_student = $stmt_student->get_result();
    
    if ($result_student->num_rows > 0) {
        $student_data = $result_student->fetch_assoc();
    } else {
        die("<script>alert('Student profile not found or role is incorrect.'); window.location.href='manage_students.php';</script>");
    }
    
    $stmt_student->close();
} else {
    $error_message = "Database error fetching student profile: " . $mysqli->error;
}


// --- 4. Database Query: Fetch ALL Student Requirements ---
$query_req = "
    SELECT 
        requirement_id, 
        document_type, 
        file_name, 
        file_path, 
        approval_status,
        upload_date
    FROM 
        student_requirements 
    WHERE 
        student_id = ?
    ORDER BY 
        upload_date DESC
";

$stmt_req = $mysqli->prepare($query_req);

if ($stmt_req) {
    $stmt_req->bind_param('i', $student_id);
    $stmt_req->execute();
    $result_req = $stmt_req->get_result();
    while ($row = $result_req->fetch_assoc()) {
        $requirements[] = $row;
    }
    $stmt_req->close();
}


$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student | Coordinator Dashboard</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="coordinatordashboard.css">
    <link rel="stylesheet" href="view_student_coordinator.css"> 
    <link rel="stylesheet" href="manage_requirements.css"> 
    <style>
        .profile-card { padding: 30px; }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
        .detail-item strong { display: block; font-weight: 700; color: #555; }
        .description-box { background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 25px; white-space: pre-wrap; }
        .requirements-section { margin-top: 30px; }
        .req-table { width: 100%; border-collapse: collapse; }
        .req-table th, .req-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        .req-table th { background-color: #f0f4ff; color: rgb(32, 64, 227); }
        .action-link { 
            background-color: #5bc0de; 
            color: white; 
            padding: 6px 12px; 
            border-radius: 5px; 
            text-decoration: none; 
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s;
        }
        .action-link:hover { background-color: #31b0d5; }
        
        .action-button-group {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .btn-approve {
            background-color: #28a745; 
            color: white; 
            padding: 6px 10px; 
            border-radius: 5px; 
            border: none;
            font-size: 13px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-reject {
            background-color: #dc3545; 
            color: white; 
            padding: 6px 10px; 
            border-radius: 5px; 
            border: none;
            font-size: 13px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-approve:hover { background-color: #1e7e34; }
        .btn-reject:hover { background-color: #bd2130; }

        /* Status colors for requirements (reusing manage_requirements.css styles if linked) */
        .status-tag.status-approved { background-color: #d4edda; color: #155724; }
        .status-tag.status-pending { background-color: #fff3cd; color: #856404; }
        .status-tag.status-rejected { background-color: #f8d7da; color: #721c24; }
        .alert {
            /* Use styles from manage_requirements.css */
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <aside class="sidebar">
        <div class="logo">
            <h2>Coordinator Portal</h2>
        </div>

        <nav class="menu">
            <a href="coordinatordashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="manage_students.php" class="active"><i class="fa-solid fa-user-graduate"></i> Manage Students</a>
            <a href="manage_applications.php"><i class="fa-solid fa-clipboard-check"></i> Review Applications</a> 
            <a href="manage_companies.php"><i class="fa-solid fa-building"></i> Manage Companies</a>
            <a href="manage_placements.php"><i class="fa-solid fa-clipboard-list"></i> Placements/OJT</a>
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <h1>Student Profile: <?php echo htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']); ?></h1>
            <a href="manage_students.php" style="color: rgb(32, 64, 227); text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Back to Student List</a>
        </header>

        <div class="dashboard-body">

            <?php if ($message): ?>
                <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?php echo $message; ?></div>
            <?php elseif ($error_message): ?>
                <div class="alert error"><i class="fa-solid fa-circle-xmark"></i> <?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="section-container profile-card">
                <div class="profile-header">
                    <h2><?php echo htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']); ?></h2>
                    <span class="status-tag status-<?php echo strtolower($student_data['account_status']); ?>">
                        <?php echo htmlspecialchars($student_data['account_status']); ?>
                    </span>
                </div>

                <h3><i class="fa-solid fa-info-circle"></i> Basic Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <strong>Username</strong>
                        <span><?php echo htmlspecialchars($student_data['username']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Course / Year Level</strong>
                        <span><?php echo htmlspecialchars($student_data['course'] . ' / ' . $student_data['year_level']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Email Address</strong>
                        <span><?php echo htmlspecialchars($student_data['student_email']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Contact Number</strong>
                        <span><?php echo htmlspecialchars($student_data['phone_number']); ?></span>
                    </div>
                </div>
                
                <h3><i class="fa-solid fa-file-alt"></i> Student Description</h3>
                <div class="description-box">
                    <?php echo nl2br(htmlspecialchars($student_data['description'] ?? 'No description provided.')); ?>
                </div>
                
                <div class="requirements-section">
                    <h3><i class="fa-solid fa-paperclip"></i> OJT Requirements</h3>
                    
                    <?php if (!empty($requirements)): ?>
                    <table class="req-table">
                        <thead>
                            <tr>
                                <th>Document Type</th>
                                <th>File Name</th>
                                <th>Date Submitted</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requirements as $req): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($req['document_type']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($req['file_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($req['upload_date'])); ?></td>
                                    <td>
                                        <span class="status-tag status-<?php echo strtolower($req['approval_status']); ?>">
                                            <?php echo htmlspecialchars($req['approval_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-button-group">
                                            <a href="<?php echo htmlspecialchars($req['file_path']); ?>" download class="action-link" style="background-color: #17a2b8;">
                                                <i class="fa-solid fa-download"></i> Download
                                            </a>
                                            
                                            <?php if ($req['approval_status'] === 'Pending'): ?>
                                                <form method="POST" action="review_requirement.php">
                                                    <input type="hidden" name="requirement_id" value="<?php echo $req['requirement_id']; ?>">
                                                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                                                    <button type="submit" name="action" value="approve" class="btn-approve">
                                                        <i class="fa-solid fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="review_requirement.php">
                                                    <input type="hidden" name="requirement_id" value="<?php echo $req['requirement_id']; ?>">
                                                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                                                    <button type="submit" name="action" value="reject" class="btn-reject">
                                                        <i class="fa-solid fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p class="empty-state-text"><i class="fas fa-box-open"></i> No OJT requirements have been uploaded yet.</p>
                    <?php endif; ?>
                </div>

                <br>
                
                <h3><i class="fa-solid fa-user-cog"></i> Account Management</h3>
                <p>Current Account Status: <strong><?php echo htmlspecialchars($student_data['account_status']); ?></strong></p>
                
                <form action="update_student_status.php" method="POST" class="status-form">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($student_data['user_id']); ?>">
                    <label for="new_status">Change Status:</label>
                    <select name="new_status" id="new_status" required>
                        <option value="Active" <?php if ($student_data['account_status'] == 'Active') echo 'selected'; ?>>Active</option>
                        <option value="Inactive" <?php if ($student_data['account_status'] == 'Inactive') echo 'selected'; ?>>Inactive</option>
                        <option value="Banned" <?php if ($student_data['account_status'] == 'Banned') echo 'selected'; ?>>Banned</option>
                    </select>
                    <button type="submit">Update Account Status</button>
                </form>

            </div>

        </div>
    </div>
</div>

</body>
</html>