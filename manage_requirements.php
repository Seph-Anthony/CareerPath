<?php
// manage_requirements.php - Handles student profile, requirement upload, and status display
session_start();
require_once 'db_connect.php'; // Your database connection file

// 1. Authentication and Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: signinstudent.html"); 
    exit;
}

$user_id = $_SESSION['user_id'];
$student_id = null;
$first_name = "";
$last_name = "";
$fullname = "Student Name";
$course = "N/A";
$year_level = "N/A";
$email = "N/A"; // New field
$phone_number = "N/A"; // New field
$description = "N/A"; // New field
$requirements = [];
$error = "";
$message = "";

// --- Message Handling from URL ---
if (isset($_GET['status']) && isset($_GET['msg'])) {
    $status = $_GET['status'];
    $msg = htmlspecialchars($_GET['msg']);
    
    if ($status === 'success') {
        $message = $msg;
    } elseif ($status === 'error') {
        $error = $msg;
    }
}

// --- 2. Fetch Student ID and FULL Profile Data ---
$stmt_student = $mysqli->prepare("
    SELECT 
        student_id,
        first_name, 
        last_name, 
        course, 
        year_level,
        email, 
        phone_number,
        description
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
        $first_name = htmlspecialchars($student_data['first_name']); 
        $last_name = htmlspecialchars($student_data['last_name']);
        $fullname = $first_name . ' ' . $last_name;
        $course = htmlspecialchars($student_data['course']);
        $year_level = htmlspecialchars($student_data['year_level']);
        $email = htmlspecialchars($student_data['email']);
        $phone_number = htmlspecialchars($student_data['phone_number']);
        $description = htmlspecialchars($student_data['description']);
    } else {
        $error = "Student profile not found. Please contact the administrator.";
    }
    $stmt_student->close();
} else {
    $error = "Database query failed during profile retrieval: " . $mysqli->error;
}


// --- 3. Fetch All Submitted Requirements ---
if ($student_id) {
    $stmt_req = $mysqli->prepare("
        SELECT 
            requirement_id, 
            document_type, 
            file_name, 
            approval_status, 
            upload_date,
            file_path
        FROM 
            student_requirements 
        WHERE 
            student_id = ?
        ORDER BY 
            upload_date DESC
    ");

    if ($stmt_req) {
        $stmt_req->bind_param('i', $student_id);
        $stmt_req->execute();
        $result_req = $stmt_req->get_result();
        while ($row = $result_req->fetch_assoc()) {
            $requirements[] = $row;
        }
        $stmt_req->close();
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile & Requirements | <?php echo $fullname; ?></title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="companydashboard.css"> 
    <link rel="stylesheet" href="studentdashboard.css">
    <link rel="stylesheet" href="manage_requirements.css"> 
    <style>
        /* Profile Edit Form Styles */
        #edit-profile-form {
            display: none; /* Initially hidden */
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        #edit-profile-form .form-group {
            margin-bottom: 15px;
        }
        #edit-profile-form label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 14px;
        }
        #edit-profile-form input[type="text"], 
        #edit-profile-form input[type="email"], 
        #edit-profile-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-family: inherit;
        }
        #edit-profile-form textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-secondary {
            background-color: #f0f0f0;
            color: #555;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn-secondary:hover {
            background-color: #e0e0e0;
        }
        /* Alert and Button Consistency */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
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
        .btn-primary {
            background-color: rgb(32, 64, 227);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: rgb(25, 50, 190);
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
                <a href="studentdashboard.php" class="nav-link"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="#active_post" class="nav-link"><i class="fa-solid fa-briefcase"></i> Apply for OJT</a>
                <a href="my_applications.php" class="nav-link"><i class="fa-solid fa-file-contract"></i> My Applications</a>
                <a href="daily_log_submission.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> Daily Log</a>
                <a href="student_tasks.php" class="nav-link"><i class="fa-solid fa-list-check"></i> My Tasks</a>
                <a href="manage_requirements.php" class="nav-link active"><i class="fa-solid fa-file-upload"></i> Manage Requirements</a> 
                <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <div class="main-content">
            <header class="topbar">
                <h1>My Profile & Requirements</h1>
                <p>Manage your personal details and submit necessary OJT documents.</p>
            </header>

            <div class="dashboard-body">

                <div class="section-container profile-card">
                    <h2><i class="fas fa-id-card"></i> Personal Profile</h2>
                    
                    <div id="profile-view">
                        <div class="profile-details">
                            <p><strong>Name:</strong> <span><?php echo $fullname; ?></span></p>
                            <p><strong>Course:</strong> <span><?php echo $course; ?></span></p>
                            <p><strong>Year Level:</strong> <span><?php echo $year_level; ?></span></p>
                            <p><strong>Email:</strong> <span><?php echo $email; ?></span></p>
                            <p><strong>Phone:</strong> <span><?php echo $phone_number; ?></span></p>
                            <p><strong>Description:</strong> <span><?php echo nl2br($description); ?></span></p>
                        </div>
                        <button id="btn-edit" class="btn-primary" style="margin-top: 15px;"><i class="fas fa-edit"></i> Edit Profile</button>
                    </div>

                    <form id="edit-profile-form" action="update_profile.php" method="POST">
                        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                        <div class="form-group">
                            <label for="first_name">First Name <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo $first_name; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name <span class="required">*</span></label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo $last_name; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone_number">Phone Number</label>
                            <input type="text" id="phone_number" name="phone_number" value="<?php echo $phone_number; ?>">
                        </div>
                        <div class="form-group">
                            <label for="description">Short Description</label>
                            <textarea id="description" name="description"><?php echo $description; ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
                            <button type="button" id="btn-cancel" class="btn-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
                <?php if ($message): ?>
                    <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?php echo $message; ?></div>
                <?php elseif ($error): ?>
                    <div class="alert error"><i class="fa-solid fa-circle-xmark"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <div class="section-container upload-card">
                    <h2><i class="fas fa-upload"></i> Upload OJT Requirements</h2>
                    
                    <form action="upload_requirement.php" method="POST" enctype="multipart/form-data" class="upload-form">
                        
                        <input type="hidden" name="document_type" value="Information">
                        <div class="form-group">
                             <label>Document Type</label>
                             <p><strong>Information</strong> (Fixed document type for all submissions)</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="document_file">Select File (PDF, DOC, DOCX, Max 5MB) <span class="required">*</span></label>
                            <input type="file" name="document_file" id="document_file" accept=".pdf,.doc,.docx" required>
                        </div>
                        
                        <button type="submit" name="upload_document" class="btn-primary">
                            <i class="fa-solid fa-cloud-upload-alt"></i> Upload Document
                        </button>
                    </form>
                </div>

                <div class="section-container history-card">
                    <h2><i class="fas fa-list-check"></i> Submission History</h2>
                    
                    <?php if (!empty($requirements)): ?>
                    <div class="table-responsive">
                        <table class="requirements-table">
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
                                            <a href="<?php echo htmlspecialchars($req['file_path']); ?>" download class="btn-small-download">
                                                <i class="fa-solid fa-download"></i> Download
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <p class="empty-state-text"><i class="fas fa-box-open"></i> No requirements have been uploaded yet.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileView = document.getElementById('profile-view');
            const editForm = document.getElementById('edit-profile-form');
            const btnEdit = document.getElementById('btn-edit');
            const btnCancel = document.getElementById('btn-cancel');

            if (btnEdit && btnCancel) {
                btnEdit.addEventListener('click', function() {
                    profileView.style.display = 'none';
                    editForm.style.display = 'block';
                });

                btnCancel.addEventListener('click', function() {
                    editForm.style.display = 'none';
                    profileView.style.display = 'block';
                });
            }
        });
    </script>
</body>
</html>