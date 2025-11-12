<?php
// manage_companies.php
session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: signincoordinator.html"); 
    exit;
}

// --- 2. Database Query: Fetch All Companies ---
// Joins the 'company' table with the 'users' table to get both company details and account status.
$query = "
    SELECT 
        c.company_id, 
        c.company_name, 
        c.industry, 
        c.contact_person, 
        c.email AS company_email,
        u.username,
        u.user_id,             -- Important for status updates
        u.status AS account_status
    FROM 
        company c
    JOIN 
        users u ON c.user_id = u.user_id
    WHERE
        u.role = 'company'
    ORDER BY 
        c.company_name ASC
";

$result = $mysqli->query($query);
$companies = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }
    $result->free();
} else {
    $error_message = "Error fetching company data: " . $mysqli->error;
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Companies | Coordinator Dashboard</title>
  <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="coordinatordashboard.css">
  
  <style>
    /* Reusing/defining basic table styles for consistency */
    .table-container {
        background: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    .company-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    .company-table th, .company-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
        font-size: 14px;
    }
    .company-table th {
        background-color: rgb(247, 250, 252);
        color: rgb(32, 64, 227);
        font-weight: 700;
        text-transform: uppercase;
    }
    .company-table tr:hover {
        background-color: #f5f5ff;
    }
    .status-active, .status-inactive, .status-pending {
        padding: 4px 8px;
        border-radius: 5px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
    }
    .status-active { background-color: #d4edda; color: #155724; }
    .status-inactive { background-color: #f8d7da; color: #721c24; }
    .status-pending { background-color: #fff3cd; color: #856404; }
    
    .action-link {
        color: rgb(32, 64, 227);
        text-decoration: none;
        font-weight: 600;
        transition: color 0.2s;
    }
    .action-link:hover {
        color: rgb(25, 50, 190);
        text-decoration: underline;
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
            <a href="coordinatordashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Manage Students</a>
            <a href="manage_applications.php"><i class="fa-solid fa-clipboard-check"></i> Review Applications</a> 
            <a href="manage_companies.php" class="active"><i class="fa-solid fa-building"></i> Manage Companies</a>
            <a href="manage_placements.php"><i class="fa-solid fa-clipboard-list"></i> Placements/OJT</a>
            <a href="manage_evaluations.php"><i class="fa-solid fa-clipboard-check"></i> Review Evaluations</a> 
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <h1>Manage Companies</h1>
      <p>Oversight of all registered company accounts and their status.</p>
    </header>

    <div class="dashboard-body">

      <div class="table-container">
        <h2><i class="fa-solid fa-table"></i> Company List (Total: <?php echo count($companies); ?>)</h2>

        <?php if (isset($error_message)): ?>
            <div style="color: red; padding: 10px; border: 1px solid red; border-radius: 5px;"><?php echo $error_message; ?></div>
        <?php elseif (empty($companies)): ?>
            <p>No companies have registered yet.</p>
        <?php else: ?>
            <table class="company-table">
                <thead>
                    <tr>
                        <th>Company Name</th>
                        <th>Industry</th>
                        <th>Contact Person</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $company): 
                        $statusClass = strtolower(str_replace(' ', '-', $company['account_status']));
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($company['company_name']); ?></td>
                        <td><?php echo htmlspecialchars($company['industry']); ?></td>
                        <td><?php echo htmlspecialchars($company['contact_person']); ?></td>
                        <td><?php echo htmlspecialchars($company['company_email']); ?></td>
                        <td><span class="status-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($company['account_status']); ?></span></td>
                        <td><a href="view_company_coordinator.php?company_id=<?php echo $company['company_id']; ?>" class="action-link">View Details</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

      </div>

    </div>
  </div>
</div>

</body>
</html>