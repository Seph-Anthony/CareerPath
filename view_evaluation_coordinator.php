<?php
// view_evaluation_coordinator.php - Coordinator views and sets final status for an evaluation

session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: signincoordinator.html"); 
    exit;
}

// 2. Get Evaluation ID from URL
$evaluation_id = isset($_GET['evaluation_id']) ? intval($_GET['evaluation_id']) : 0;

if ($evaluation_id === 0) {
    die("<script>alert('No Evaluation ID provided.'); window.location.href='manage_evaluations.php';</script>");
}

$evaluation_data = null;
$error_message = null;

// --- 3. Database Query: Fetch Evaluation Details, Student, and Company Info ---
$query = "
    SELECT 
        e.*,
        s.first_name, 
        s.last_name,
        c.company_name,
        ip.title AS posting_title,
        ia.status AS application_status,
        ia.application_id
    FROM 
        evaluation e
    JOIN 
        student s ON e.student_id = s.student_id
    JOIN 
        company c ON e.company_id = c.company_id
    JOIN
        intern_application ia ON e.application_id = ia.application_id
    JOIN
        intern_posting ip ON ia.posting_id = ip.posting_id
    WHERE
        e.evaluation_id = ?
    LIMIT 1
";

$stmt = $mysqli->prepare($query);

if ($stmt) {
    $stmt->bind_param('i', $evaluation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $evaluation_data = $result->fetch_assoc();
        // Format the date
        $evaluation_data['submitted_at_formatted'] = date('M d, Y h:i A', strtotime($evaluation_data['submitted_at']));
        
        // Determine the final status tag
        if ($evaluation_data['application_status'] == 'Passed' || $evaluation_data['application_status'] == 'Failed') {
            $evaluation_data['final_status'] = $evaluation_data['application_status'];
        } else {
            $evaluation_data['final_status'] = 'Pending Coordinator Review';
        }

    } else {
        die("<script>alert('Evaluation not found.'); window.location.href='manage_evaluations.php';</script>");
    }
    
    $stmt->close();
} else {
    $error_message = "Database error: " . $mysqli->error;
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Evaluation | Coordinator Dashboard</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="coordinatordashboard.css"> 
    <style>
        .dashboard-body { padding: 30px; }
        .review-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        .review-card h2 {
            margin-top: 0;
            color: rgb(32, 64, 227);
        }
        .detail-section {
            margin-bottom: 25px;
            padding: 15px;
            border: 1px solid #f0f0f0;
            border-radius: 8px;
        }
        .detail-section h3 {
            font-size: 16px;
            color: #555;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            font-size: 14px;
        }
        .info-item strong {
            display: block;
            color: #333;
            margin-bottom: 3px;
        }
        .info-item span {
            color: #666;
        }
        .remark-box {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 6px;
            white-space: pre-wrap;
            font-size: 14px;
        }
        .final-status-form {
            background-color: #f7faff;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid rgb(32, 64, 227, 0.2);
            margin-top: 30px;
        }
        .final-status-form h3 {
            color: rgb(32, 64, 227);
            margin-top: 0;
        }
        .btn-status {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-left: 10px;
            transition: background-color 0.3s;
        }
        .btn-pass {
            background-color: #2ecc71;
            color: white;
        }
        .btn-pass:hover {
            background-color: #27ae60;
        }
        .btn-fail {
            background-color: #e74c3c;
            color: white;
        }
        .btn-fail:hover {
            background-color: #c0392b;
        }
        .status-tag {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-passed { background-color: #d4edda; color: #155724; }
        .status-failed { background-color: #f8d7da; color: #721c24; }
        .status-pending-coordinator-review { background-color: #fff3cd; color: #856404; }
    </style>
</head>
<body>

<div class="dashboard-container">

    <aside class="sidebar">
        <div class="logo"><h2>Career Path</h2></div>
        <nav class="menu">
            <a href="coordinatordashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Manage Students</a>
            <a href="manage_companies.php"><i class="fa-solid fa-building"></i> Manage Companies</a>
            <a href="manage_placements.php"><i class="fa-solid fa-clipboard-list"></i> Placements/OJT</a>
            <a href="manage_evaluations.php" class="active"><i class="fa-solid fa-clipboard-check"></i> Review Evaluations</a>
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <h1>Evaluation Report for **<?php echo htmlspecialchars($evaluation_data['first_name'] . ' ' . $evaluation_data['last_name']); ?>**</h1>
            <a href="manage_evaluations.php" style="color: rgb(32, 64, 227); text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Back to Evaluations</a>
        </header>

        <div class="dashboard-body">

            <div class="review-card">
                
                <div class="detail-section">
                    <h3><i class="fa-solid fa-user-graduate"></i> Student & Internship Details</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Student Name:</strong>
                            <span><?php echo htmlspecialchars($evaluation_data['first_name'] . ' ' . $evaluation_data['last_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Company:</strong>
                            <span><?php echo htmlspecialchars($evaluation_data['company_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>OJT Position:</strong>
                            <span><?php echo htmlspecialchars($evaluation_data['posting_title']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Date Submitted:</strong>
                            <span><?php echo $evaluation_data['submitted_at_formatted']; ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Current Status:</strong>
                            <span class="status-tag status-<?php echo strtolower(str_replace(' ', '-', $evaluation_data['final_status'])); ?>">
                                <?php echo htmlspecialchars($evaluation_data['final_status']); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <strong>Application ID:</strong>
                            <span><?php echo $evaluation_data['application_id']; ?></span>
                        </div>
                    </div>
                </div>

                <div class="detail-section" style="border-left: 5px solid #f39c12;">
                    <h3><i class="fa-solid fa-chart-bar"></i> Company Evaluation</h3>
                    <div style="margin-bottom: 15px;">
                        <strong>Overall Score:</strong> 
                        <span style="font-size: 24px; font-weight: 700; color: #2ecc71;"><?php echo htmlspecialchars($evaluation_data['score']); ?> / 5</span>
                    </div>
                    
                    <strong style="display: block; margin-bottom: 5px;">Detailed Feedback (Remark):</strong>
                    <div class="remark-box">
                        <?php echo nl2br(htmlspecialchars($evaluation_data['remark'])); ?>
                    </div>
                </div>
                
                <hr style="margin: 30px 0;">

                <div class="final-status-form">
                    <h3><i class="fa-solid fa-graduation-cap"></i> Coordinator Final OJT Result</h3>
                    
                    <p>Select the final outcome for the student based on this evaluation.</p>
                    
                    <form action="update_final_status.php" method="POST">
                        <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($evaluation_data['application_id']); ?>">
                        
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button type="submit" name="final_ojt_status" value="Passed" class="btn-status btn-pass">
                                <i class="fa-solid fa-check"></i> Approve (Mark as **Passed**)
                            </button>
                            <button type="submit" name="final_ojt_status" value="Failed" class="btn-status btn-fail">
                                <i class="fa-solid fa-times"></i> Reject (Mark as **Failed**)
                            </button>
                        </div>
                        <p style="margin-top: 15px; font-size: 12px; color: #888;">This action updates the student's OJT Application status to the final result.</p>
                    </form>
                </div>

            </div>

        </div>
    </div>
</div>
</body>
</html>