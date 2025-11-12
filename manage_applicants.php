<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$company_name = "Company Representative";
$company_id = null;
$error_message = "";

// Fetch company information
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

if (!$company_id) {
    die("Error: Company profile not found.");
}

// 3. Main Query: Get ALL Active Postings and their Applicants
$applicant_data = [];

// --- REVISED QUERY: Searching for LATEST 'Information' document status ---
$query = "
    SELECT
        ip.posting_id,
        ip.title AS post_title,
        ip.slot_available,
        -- Calculate slots_filled dynamically using a subquery
        (
            SELECT COUNT(ia_sub.application_id)
            FROM intern_application ia_sub
            WHERE ia_sub.posting_id = ip.posting_id AND ia_sub.status = 'Hired'
        ) AS slots_filled,
        s.student_id,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        s.course,
        -- Get status from the LATEST_INFO join (lr)
        lr.approval_status AS resume_approval_status, 
        ia.application_date AS applied_at,
        ia.status AS status,
        ia.application_id
    FROM
        intern_posting ip
    LEFT JOIN
        intern_application ia ON ip.posting_id = ia.posting_id
    LEFT JOIN
        student s ON ia.student_id = s.student_id
    LEFT JOIN
        -- Derived Table (lr) to find the LATEST 'Information' record per student
        (
            SELECT
                sr1.student_id,
                sr1.approval_status
            FROM
                student_requirements sr1
            LEFT JOIN
                student_requirements sr2 ON sr1.student_id = sr2.student_id
                AND sr2.document_type = 'Information' -- *** MODIFIED TYPE ***
                AND sr1.upload_date < sr2.upload_date 
            WHERE
                sr1.document_type = 'Information' -- *** MODIFIED TYPE ***
                AND sr2.upload_date IS NULL 
        ) lr ON s.student_id = lr.student_id
    WHERE
        ip.company_id = ? AND ip.status = 'Active' 
        AND ia.status != 'Pending'
    ORDER BY
        ip.posting_id, ia.application_date DESC
";

// Use a prepared statement to bind the company_id for security
$stmt = $mysqli->prepare($query);

if ($stmt) {
    $stmt->bind_param('i', $company_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $posting_id = $row['posting_id'];
        if (!isset($applicant_data[$posting_id])) {
            $applicant_data[$posting_id] = [
                'post_title' => htmlspecialchars($row['post_title']),
                'slot_available' => $row['slot_available'],
                'slots_filled' => $row['slots_filled'], 
                'applicants' => []
            ];
        }

        // Only add applicant if an application exists for the post
        if ($row['application_id'] !== null) {
            $applicant_data[$posting_id]['applicants'][] = [
                'student_id' => $row['student_id'],
                'student_name' => htmlspecialchars($row['student_name']),
                'course' => htmlspecialchars($row['course']),
                'applied_at' => date('M d, Y', strtotime($row['applied_at'])),
                'status' => $row['status'],
                'application_id' => $row['application_id'],
                // Set default status if no record was found in the derived table (NULL in the join)
                'resume_approval_status' => $row['resume_approval_status'] ?? 'Not Uploaded' 
            ];
        }
    }
    $stmt->close();
} else {
    $error_message = "Database error: Could not fetch applicant data. " . $mysqli->error;
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applicants | <?php echo $company_name; ?></title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="companydashboard.css"> 
    <link rel="stylesheet" href="manage_applicants.css">
    <style>
        /* Add styles for disabled/pending resume button */
        .btn-disabled {
            background-color: #ccc;
            color: #666;
            cursor: not-allowed;
            pointer-events: none; /* Prevents clicks */
            opacity: 0.7;
        }
        .action-cell {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .status-tag.status-info {
            background-color: #e6f7ff;
            color: #0068b5;
            padding: 4px 8px;
            font-size: 11px;
            border-radius: 4px;
        }
        /* Ensure all action buttons are clearly visible on hover */
        .btn-detail:not(.btn-disabled) {
            transition: transform 0.2s;
        }
        .btn-detail:not(.btn-disabled):hover {
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
            <a href="companydashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="post_internship.php"><i class="fa-solid fa-file-circle-plus"></i> Post Internship</a>
            <a href="manage_applicants.php" class="active"><i class="fa-solid fa-users"></i> View Applicants</a>
            <a href="company_log_approval.php"><i class="fa-solid fa-file-signature"></i> Approve Daily Logs</a>
            <a href="intern_progress.php"><i class="fa-solid fa-chart-line"></i> Intern Progress</a>
            <a href="#"><i class="fa-solid fa-user-circle"></i> Edit Information</a>
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <h1>Manage Applicants</h1>
            <p>Review students approved by the coordinator for your OJT postings.</p>
        </header>

        <div class="dashboard-body">

            <?php if ($error_message): ?>
                <div class="alert-container error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php 
            $has_approved_applicants = false;
            // Check if any post has approved applicants (status is not 'Pending' in intern_application)
            foreach ($applicant_data as $post) {
                if (!empty($post['applicants'])) {
                    $has_approved_applicants = true;
                    break;
                }
            }
            ?>

            <?php if (!$has_approved_applicants): ?>
                <div class="applicants-card" style="text-align: center;">
                    <p style="color: #777; padding: 20px;">
                        <i class="fa-solid fa-hourglass-half" style="margin-right: 5px;"></i> 
                        There are currently no students who have been approved by the coordinator and applied to your active postings.
                    </p>
                </div>
            <?php endif; ?>

            <?php foreach ($applicant_data as $post_id => $post): ?>
                <?php if (empty($post['applicants'])) continue; // Skip posts with no approved applicants ?>

                <div class="applicants-card">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-clipboard-list"></i> <?php echo $post['post_title']; ?></h2>
                        <div class="slot-info">
                            Slots: **<?php echo $post['slots_filled']; ?>** / <?php echo $post['slot_available']; ?>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="applicants-table">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Course</th>
                                    <th>Applied Date</th>
                                    <th>Application Status</th>
                                    <th style="min-width: 300px;">Company Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($post['applicants'] as $applicant): ?>
                                    <tr>
                                        <td>
                                            **<?php echo $applicant['student_name']; ?>**
                                            <small>(ID: <?php echo $applicant['student_id']; ?>)</small>
                                        </td>
                                        <td><?php echo $applicant['course']; ?></td>
                                        <td><?php echo $applicant['applied_at']; ?></td>
                                        <td>
                                            <span class="status-tag status-<?php echo strtolower($applicant['status']); ?>">
                                                <?php echo $applicant['status']; ?>
                                            </span>
                                        </td>
                                        <td class="action-cell">
                                            <?php 
                                            // Check resume approval status
                                            $status = $applicant['resume_approval_status'];
                                            $is_resume_approved = $status === 'Approved';
                                            
                                            // Determine button style and text
                                            if ($is_resume_approved) {
                                                $resume_button_class = 'btn-detail';
                                                $resume_button_text = 'View Information';
                                                $resume_link = "view_student_resume.php?student_id=" . $applicant['student_id'];
                                            } elseif ($status === 'Pending') {
                                                $resume_button_class = 'btn-detail btn-disabled';
                                                $resume_button_text = 'Info Pending';
                                                $resume_link = '#';
                                            } elseif ($status === 'Rejected') {
                                                $resume_button_class = 'btn-detail btn-disabled';
                                                $resume_button_text = 'Info Rejected';
                                                $resume_link = '#';
                                            } else { // 'Not Uploaded' or other
                                                $resume_button_class = 'btn-detail btn-disabled';
                                                $resume_button_text = 'No Info Uploaded';
                                                $resume_link = '#';
                                            }
                                            ?>
                                            
                                            <a href="<?php echo $resume_link; ?>" class="<?php echo $resume_button_class; ?>">
                                                <i class="fa-solid fa-file-lines"></i> <?php echo $resume_button_text; ?>
                                            </a>
                                            
                                            <a href="view_applicant_details.php?student_id=<?php echo $applicant['student_id']; ?>&posting_id=<?php echo $post_id; ?>&application_id=<?php echo $applicant['application_id']; ?>" class="btn-detail" style="background-color: #4CAF50; border-color: #4CAF50;">
                                                <i class="fa-solid fa-check"></i> Review Application
                                            </a>

                                            <?php if (!$is_resume_approved): ?>
                                                <span class="status-tag status-info" title="Student's required documents are still under coordinator review.">
                                                    Coordinator Review Required
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </main>
</div>

</body>
</html>