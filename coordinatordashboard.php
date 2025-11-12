<?php
// coordinatordashboard.php
session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: signincoordinator.html"); 
    exit;
}

// Get the user_id and username from the session
$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);

$coordinator_id = null; // Initialize coordinator ID
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

// --- 2. Fetch Coordinator's Profile Details ---
$coordinator_name = $username; 
$coordinator_department = 'N/A';
$coordinator_position = 'Coordinator'; 
$coordinator_email = 'N/A';
$coordinator_contact = 'N/A';
$data = []; // Initialize $data array

// The SELECT statement now includes coordinator_id and all editable fields
$stmt = $mysqli->prepare("
    SELECT coordinator_id, full_name, department, position, email, contact_number
    FROM coordinator 
    WHERE user_id = ? 
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        // Assign the fetched values
        $coordinator_id = $data['coordinator_id']; // Crucial ID for update
        $coordinator_name = htmlspecialchars($data['full_name']);
        $coordinator_department = htmlspecialchars($data['department']);
        $coordinator_position = htmlspecialchars($data['position']);
        $coordinator_email = htmlspecialchars($data['email']);
        $coordinator_contact = htmlspecialchars($data['contact_number']);
    }
    $stmt->close();
}


// --- 3. Fetch Dashboard Metrics ---
$stats = [
    'total_students' => 0,
    'active_companies' => 0,
    'total_ojt_postings' => 0, // NEW STAT KEY
    'seeking_placement' => 0, // Updated stat key for consistency
];

// a) Total Students (Active in users table)
$query = "SELECT COUNT(*) AS count 
          FROM student s 
          JOIN users u ON s.user_id = u.user_id 
          WHERE u.status = 'Active' AND u.role = 'student'";
$result = $mysqli->query($query);
$stats['total_students'] = $result ? $result->fetch_assoc()['count'] : 0;

// b) Total Active Companies (Active in users table)
$query = "SELECT COUNT(*) AS count 
          FROM company c 
          JOIN users u ON c.user_id = u.user_id 
          WHERE u.status = 'Active' AND u.role = 'company'";
$result = $mysqli->query($query);
$stats['active_companies'] = $result ? $result->fetch_assoc()['count'] : 0;

// c) Total OJT Postings - Query the intern_posting table
$query = "SELECT COUNT(*) AS count FROM intern_posting";
$result = $mysqli->query($query);
$stats['total_ojt_postings'] = $result ? $result->fetch_assoc()['count'] : 0;

// d) Total Students seeking placement 
// Calculate based on students not yet accepted for OJT (simplified)
$query_placed = "SELECT COUNT(DISTINCT student_id) AS count FROM intern_application WHERE status = 'Accepted'";
$result_placed = $mysqli->query($query_placed);
$placed_students_count = $result_placed ? $result_placed->fetch_assoc()['count'] : 0; 

$stats['seeking_placement'] = max(0, $stats['total_students'] - $placed_students_count);


// --- 4. Fetch Recent Program Activity (Top 5) ---
$recent_activities = [];
// Assuming the 'coordinator_log' table exists as mentioned in the original code.
$query = "SELECT description, created_at FROM coordinator_log ORDER BY created_at DESC LIMIT 5";
$result = $mysqli->query($query);

if ($result) {
    // Fetch all results into an array
    while ($row = $result->fetch_assoc()) {
        $recent_activities[] = $row;
    }
}


$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Dashboard | Career Path</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="coordinatordashboard.css">
    <style>
        /* ADDED: Styles for the Profile Edit Feature */
        .section-container {
            /* Inherits styles from coordinatordashboard.css */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .list-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .list-item p {
            margin: 0;
            font-weight: 500;
        }
        .list-item span {
            color: #555;
            font-size: 14px;
        }
        
        /* Edit Form Styles */
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
        #edit-profile-form input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-family: inherit;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end; /* Align buttons to the right */
            margin-top: 20px;
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
        /* Alert Styles */
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
    </style>
</head>
<body>

<div class="dashboard-container">

    <aside class="sidebar">
        <div class="logo">
            <h2>Career Path</h2>
        </div>

        <nav class="menu">
            <a href="coordinatordashboard.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Manage Students</a>
            <a href="manage_applications.php"><i class="fa-solid fa-clipboard-check"></i> Review Applications</a> 
            <a href="manage_companies.php"><i class="fa-solid fa-building"></i> Manage Companies</a>
            <a href="manage_placements.php"><i class="fa-solid fa-clipboard-list"></i> Placements/OJT</a>
            <a href="manage_evaluations.php"><i class="fa-solid fa-clipboard-check"></i> Review Evaluations</a> 
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <h1>Welcome, <?php echo $coordinator_name; ?>!</h1>
            <p>OJT Management Overview for the <?php echo $coordinator_department; ?> Department.</p>
        </header>

        <div class="dashboard-body">

            <?php if ($message): ?>
                <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?php echo $message; ?></div>
            <?php elseif ($error): ?>
                <div class="alert error"><i class="fa-solid fa-circle-xmark"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <section class="stats-cards">
                
                <div class="card">
                    <i class="fa-solid fa-users"></i>
                    <div>
                        <h3>Total Students</h3>
                        <p><?php echo $stats['total_students']; ?></p>
                    </div>
                </div>

                <div class="card">
                    <i class="fa-solid fa-briefcase"></i>
                    <div>
                        <h3>Active Companies</h3>
                        <p><?php echo $stats['active_companies']; ?></p>
                    </div>
                </div>
                
                <div class="card">
                    <i class="fa-solid fa-list-check"></i>
                    <div>
                        <h3>Total OJT Postings</h3>
                        <p><?php echo $stats['total_ojt_postings']; ?></p>
                    </div>
                </div>
                
                <div class="card">
                    <i class="fa-solid fa-hourglass-half"></i>
                    <div>
                        <h3>Seeking Placement</h3>
                        <p><?php echo $stats['seeking_placement']; ?></p>
                    </div>
                </div>
            </section>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                
                <section class="section-container">
                    <h2><i class="fa-solid fa-building"></i> Recent Program Activity</h2>
                    
                    <?php if (empty($recent_activities)): ?>
                        <div class="list-item">
                            <p>No recent activity recorded.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="list-item">
                            <p><?php echo htmlspecialchars($activity['description']); ?></p>
                            <span style="font-size: 13px; color: #999; white-space: nowrap;">
                                <?php echo date('M d, Y H:i A', strtotime($activity['created_at'])); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <p style="text-align: right; margin-top: 15px;"><a href="view_all_activity.php" style="font-weight: 600; color: rgb(32, 64, 227);">View All Activity &raquo;</a></p>
                </section>

                <section class="section-container">
                    <h2><i class="fa-solid fa-user-tie"></i> My Profile</h2>
                    
                    <div id="profile-view">
                        <div class="list-item">
                            <p><strong>Full Name:</strong></p>
                            <span><?php echo $coordinator_name; ?></span>
                        </div>
                        <div class="list-item">
                            <p><strong>Position:</strong></p>
                            <span><?php echo $coordinator_position; ?></span>
                        </div>
                        <div class="list-item">
                            <p><strong>Department:</strong></p>
                            <span><?php echo $coordinator_department; ?></span>
                        </div>
                        <div class="list-item">
                            <p><strong>Email:</strong></p>
                            <span><?php echo $coordinator_email; ?></span>
                        </div>
                        <div class="list-item">
                            <p><strong>Contact:</strong></p>
                            <span><?php echo $coordinator_contact; ?></span>
                        </div>
                        <p style="text-align: right; margin-top: 15px;">
                            <a href="#" id="btn-edit" style="font-weight: 600; color: rgb(32, 64, 227);"><i class="fas fa-edit"></i> Edit Profile &raquo;</a>
                        </p>
                    </div>

                    <form id="edit-profile-form" action="update_coordinator_profile.php" method="POST">
                        <input type="hidden" name="coordinator_id" value="<?php echo $coordinator_id; ?>">
                        
                        <div class="form-group">
                            <label for="full_name">Full Name <span class="required">*</span></label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($data['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="department">Department <span class="required">*</span></label>
                            <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($data['department'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="position">Position <span class="required">*</span></label>
                            <input type="text" id="position" name="position" value="<?php echo htmlspecialchars($data['position'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="contact_number">Contact Number</label>
                            <input type="text" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($data['contact_number'] ?? ''); ?>">
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
                            <button type="button" id="btn-cancel" class="btn-secondary">Cancel</button>
                        </div>
                    </form>

                </section>
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
            btnEdit.addEventListener('click', function(e) {
                e.preventDefault(); // Stop the default link behavior
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
```eof