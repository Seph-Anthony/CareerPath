<?php
session_start();
require_once'db_connect.php';
if(!isset($_SESSION['user_id'])||$_SESSION['role']!=='company'){
header("Location:signincompany.html");
exit;
}

$user_id=$_SESSION['user_id'];
$company_name="Company Representative";
$company_id=null;
$error_message="";
$stmt_company=$mysqli->prepare("SELECT company_id,company_name FROM company WHERE user_id=? LIMIT 1");
if($stmt_company){
$stmt_company->bind_param('i',$user_id);
$stmt_company->execute();
$result_company=$stmt_company->get_result();
if($result_company->num_rows===1){
$data=$result_company->fetch_assoc();
$company_name=htmlspecialchars($data['company_name']);
$company_id=$data['company_id'];
}
$stmt_company->close();
}
if(!$company_id){
die("Error: Company profile not found.");
}// 3. Main Query: Get ALL Active Postings and their Applicants
// This query uses the correct 'intern_application' table and columns.
$applicant_data = [];

$query = "
 SELECT 
 ip.posting_id, 
 ip.title AS post_title,
 ip.slot_available,
 s.student_id, 
 s.first_name, 
 s.last_name, 
 s.course,
 ia.status AS application_status,
 ia.application_date AS applied_at,
        ia.application_id
 FROM 
 intern_posting ip
 JOIN 
 intern_application ia ON ip.posting_id = ia.posting_id
 JOIN 
 student s ON ia.student_id = s.student_id
 WHERE
 ip.company_id = ? 
 ORDER BY 
 ip.posting_id DESC, ia.application_date DESC
";

$stmt = $mysqli->prepare($query);

if ($stmt) {
$stmt->bind_param('i',$company_id);
$stmt->execute();
$result=$stmt->get_result();
while($row=$result->fetch_assoc()){
$posting_id=$row['posting_id']; // Group applicants under their respective post
 if (!isset($applicant_data[$posting_id])) {
 $applicant_data[$posting_id] = [
 'post_title' => htmlspecialchars($row['post_title']),
 'slot_available' => htmlspecialchars($row['slot_available']),
 'applicants' => []
 ];
 }
 

$applicant_data[$posting_id]['applicants'][] = [
 'student_id' => htmlspecialchars($row['student_id']),
 'full_name' => htmlspecialchars($row['first_name'] . ' ' . $row['last_name']),
 'course' => htmlspecialchars($row['course']),
 'status' => htmlspecialchars($row['application_status']),
 'applied_at' => date('M d, Y', strtotime($row['applied_at'])), 
            'application_id' => htmlspecialchars($row['application_id'])
];
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
  <title>Manage Applicants | Company Dashboard</title>
  <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="companydashboard.css"> 
  <link rel="stylesheet" href="manage_applicants.css"> 
</head>
<body>

<div class="dashboard-container">

  <aside class="sidebar">
    <div class="logo"><h2>Career Path</h2></div>
    <nav class="menu">
        <a href="companydashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="post_internship.php"><i class="fa-solid fa-file-circle-plus"></i> Post Internship</a>
        <a href="manage_applicants.php" class="active"><i class="fa-solid fa-users"></i> View Applicants</a>
        <a href="intern_progress.php"><i class="fa-solid fa-chart-line"></i> Intern Progress</a>
        <a href="company_profile.php"><i class="fa-solid fa-user-circle"></i> Profile</a>
        <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>

  <main class="main-content">
    <header class="topbar">
        <h1>Manage Internship Applicants</h1>
        <div class="user-section">
            <div class="user-profile">
                <span><?php echo $company_name; ?></span>
            </div>
        </div>
    </header>

    <div class="dashboard-body">

        <?php if (!empty($error_message)): ?>
            <div class="alert error" style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 20px; border-radius: 5px;"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (empty($applicant_data)): ?>
            <div class="no-data-message" style="text-align: center; padding: 40px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <h2><i class="fa-solid fa-info-circle"></i> No Applications Found</h2>
                <p>There are currently no students who have applied to your active internship postings.</p>
                <a href="post_internship.php" class="cta-button" style="margin-top: 15px; display: inline-block;">Post a New Internship</a>
            </div>
        <?php else: ?>
            
            <?php foreach ($applicant_data as $posting_id => $data): ?>
            
                <div class="applicants-card">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-briefcase"></i> Post: <?php echo $data['post_title']; ?></h2>
                        <span class="slot-info">Slots Available: **<?php echo $data['slot_available']; ?>**</span>
                    </div>

                    <div class="table-responsive">
                        <table class="applicants-table">
                            <thead>
                                <tr>
                                    <th>Applicant Name</th>
                                    <th>Course</th>
                                    <th>Applied On</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['applicants'] as $applicant): ?>
                                <tr>
                                    <td>
                                        <i class="fa-solid fa-user"></i> 
                                        <?php echo $applicant['full_name']; ?> 
                                        <small>(ID: <?php echo $applicant['student_id']; ?>)</small>
                                    </td>
                                    <td><?php echo $applicant['course']; ?></td>
                                    <td><?php echo $applicant['applied_at']; ?></td>
                                    <td><span class="status-tag status-<?php echo strtolower($applicant['status']); ?>"><?php echo $applicant['status']; ?></span></td>
                                    <td>
                                        <a href="view_applicant_details.php?student_id=<?php echo $applicant['student_id']; ?>&posting_id=<?php echo $posting_id; ?>&application_id=<?php echo $applicant['application_id']; ?>" class="btn-detail">
                                            <i class="fa-solid fa-eye"></i> Review
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            
            <?php endforeach; ?>
            
        <?php endif; ?>

    </div>
  </main>
</div>

</body>
</html>