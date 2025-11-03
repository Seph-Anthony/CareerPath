<?php
// manage_placements.php
session_start();
require_once 'db_connect.php'; 

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: signincoordinator.html"); 
    exit;
}

// --- 2. Database Query: Fetch All Postings (Aligned with Your Schema) ---
$query = "
    SELECT 
        ip.posting_id,
        ip.title,               -- Uses 'title'
        ip.requirements,        -- Uses 'requirements' (for course/student matching in the list view)
        ip.status AS posting_status,
        ip.create_at AS date_posted, -- Uses 'create_at' and aliases to 'date_posted'
        c.company_name
    FROM 
        intern_posting ip
    JOIN 
        company c ON ip.company_id = c.company_id
    ORDER BY 
        ip.create_at DESC
";

$result = $mysqli->query($query);
$postings = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Format the date using the 'date_posted' alias
        $row['date_posted_formatted'] = date('M d, Y', strtotime($row['date_posted']));
        $postings[] = $row;
    }
    $result->free();
} else {
    $error_message = "Error fetching posting data: " . $mysqli->error;
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Placements | Coordinator Dashboard</title>
  <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="coordinatordashboard.css">
  <link rel="stylesheet" href="manage_placements.css"> </head>
<body>

<div class="dashboard-container">

  <aside class="sidebar">
    <div class="logo">
      <h2>Career Path</h2>
    </div>

    <nav class="menu">
      <a href="coordinatordashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
      <a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Manage Students</a>
      <a href="manage_companies.php"><i class="fa-solid fa-building"></i> Manage Companies</a>
      <a href="manage_placements.php" class="active"><i class="fa-solid fa-clipboard-list"></i> Placements/OJT</a>
      <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <h1>Manage OJT Placements</h1>
      <p>Oversight of all company OJT postings, including review and approval.</p>
    </header>

    <div class="dashboard-body">

      <div class="table-container">
        <h2><i class="fa-solid fa-table"></i> OJT Posting List (Total: <?php echo count($postings); ?>)</h2>

        <?php if (isset($error_message)): ?>
            <div style="color: red; padding: 10px; border: 1px solid red; border-radius: 5px;"><?php echo $error_message; ?></div>
        <?php elseif (empty($postings)): ?>
            <p>No OJT postings have been created by companies yet.</p>
        <?php else: ?>
            <table class="placement-table">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Requirements/Course</th>
                        <th>Date Posted</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($postings as $posting): 
                        // Normalize status for CSS class (e.g., "Pending Review" -> "pending-review")
                        $statusClass = strtolower(str_replace(' ', '-', $posting['posting_status']));
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($posting['title']); ?></td>
                        <td><?php echo htmlspecialchars($posting['company_name']); ?></td>
                        <td><?php echo htmlspecialchars($posting['requirements']); ?></td> <td><?php echo htmlspecialchars($posting['date_posted_formatted']); ?></td>
                        <td><span class="status-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($posting['posting_status']); ?></span></td>
                        <td><a href="view_placement_coordinator.php?posting_id=<?php echo $posting['posting_id']; ?>" class="action-link">Review</a></td>
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