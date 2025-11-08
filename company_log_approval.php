<?php
// company_log_approval.php - Company view for approving student daily logs

session_start();
require_once 'db_connect.php'; 

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html"); 
    exit;
}

$user_id = $_SESSION['user_id'];
$company_id = null;
$company_name = "Company User";

// 2. Fetch Company ID and Name
$stmt_company = $mysqli->prepare("SELECT company_id, company_name FROM company WHERE user_id = ? LIMIT 1");
if ($stmt_company) {
    $stmt_company->bind_param('i', $user_id);
    $stmt_company->execute();
    $result_company = $stmt_company->get_result();
    if ($result_company->num_rows === 1) {
        $data = $result_company->fetch_assoc();
        $company_id = $data['company_id'];
        $company_name = htmlspecialchars($data['company_name']);
    }
    $stmt_company->close();
}

if (!$company_id) {
    die("Error: Company profile not found.");
}

// 3. Fetch Pending Logs for Interns Hired by THIS Company
$pending_logs = [];
$query_logs = "
    SELECT 
        dl.log_id,
        dl.log_date,
        dl.hours_logged,
        dl.activities_performed,
        s.first_name,
        s.last_name,
        ip.title AS position_title
    FROM 
        daily_log dl
    JOIN 
        intern_application ia ON dl.application_id = ia.application_id
    JOIN 
        intern_posting ip ON ia.posting_id = ip.posting_id
    JOIN 
        student s ON dl.student_id = s.student_id
    WHERE 
        ip.company_id = ? AND dl.status = 'Pending'
    ORDER BY 
        dl.log_date ASC, s.last_name ASC
";

$stmt_logs = $mysqli->prepare($query_logs);
if ($stmt_logs) {
    $stmt_logs->bind_param('i', $company_id);
    $stmt_logs->execute();
    $result_logs = $stmt_logs->get_result();
    while ($row = $result_logs->fetch_assoc()) {
        $pending_logs[] = $row;
    }
    $stmt_logs->close();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Log Approval | <?php echo $company_name; ?></title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="companydashboard.css"> 
    <link rel="stylesheet" href="company_logs.css"> </head>
</head>
<body>
    <div class="main-content">
        <header class="topbar">
            <h1>Daily Log Approval</h1>
            <div class="user-info">
                 <i class="fa-solid fa-user-circle"></i> <?php echo $company_name; ?>
            </div>
        </header>

        <div class="dashboard-body log-list-container">
            <h2>Pending Activity Logs (<?php echo count($pending_logs); ?>)</h2>

            <?php if (!empty($pending_logs)): ?>
                <?php foreach ($pending_logs as $log): ?>
                    <div class="log-item-card">
                        <div class="log-header">
                            <h3><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></h3>
                            <span>**Log Date:** <?php echo htmlspecialchars(date('M d, Y', strtotime($log['log_date']))); ?></span>
                        </div>
                        
                        <div class="log-details">
                            <p><strong>Position:</strong> <?php echo htmlspecialchars($log['position_title']); ?></p>
                            <p><strong>Hours Logged:</strong> **<?php echo htmlspecialchars(number_format($log['hours_logged'], 1)); ?>** hours</p>
                        </div>

                        <div class="log-activities">
                            <strong>Activities Performed:</strong>
                            <p><?php echo htmlspecialchars($log['activities_performed']); ?></p>
                        </div>
                        
                        <form action="process_log_approval.php" method="POST" class="action-form">
                            <input type="hidden" name="log_id" value="<?php echo htmlspecialchars($log['log_id']); ?>">
                            <input type="hidden" name="company_id" value="<?php echo htmlspecialchars($company_id); ?>">
                            
                            <button type="submit" name="action" value="Approved" class="btn-approve">Approve Log</button>
                            <button type="submit" name="action" value="Rejected" class="btn-reject">Reject Log</button>
                        </form>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="log-item-card" style="text-align: center;">
                    <p>✅ **All logs are up to date!** There are no pending daily logs awaiting your approval.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
    </body>
</html>