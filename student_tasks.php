<?php
// student_tasks.php - Student view of assigned tasks

session_start();
require_once 'db_connect.php'; 

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: signinstudent.html"); 
    exit;
}

$user_id = $_SESSION['user_id'];
$student_id = null;
$fullname = "Intern";

// 2. Fetch Student ID and Name
$stmt_student = $mysqli->prepare("SELECT student_id, first_name, last_name FROM student WHERE user_id = ? LIMIT 1");
if ($stmt_student) {
    $stmt_student->bind_param('i', $user_id);
    $stmt_student->execute();
    $result_student = $stmt_student->get_result();
    if ($result_student->num_rows === 1) {
        $data = $result_student->fetch_assoc();
        $student_id = $data['student_id'];
        $fullname = htmlspecialchars($data['first_name'] . ' ' . $data['last_name']);
    }
    $stmt_student->close();
}

if (!$student_id) {
    die("Error: Student profile not found.");
}

// 3. Fetch Assigned Tasks
$tasks = [];
// This query fetches tasks that match the student's ID and links to the company name
$query_tasks = "
    SELECT 
        t.task_id, 
        t.task_title, 
        t.task_description, 
        t.due_date, 
        t.status, 
        t.assigned_at,
        c.company_name
    FROM 
        intern_tasks t
    JOIN 
        company c ON t.company_id = c.company_id
    WHERE 
        t.student_id = ? 
    ORDER BY 
        t.status, t.due_date ASC
";

$stmt_tasks = $mysqli->prepare($query_tasks);
if ($stmt_tasks) {
    $stmt_tasks->bind_param('i', $student_id);
    $stmt_tasks->execute();
    $result_tasks = $stmt_tasks->get_result();
    
    while ($row = $result_tasks->fetch_assoc()) {
        $tasks[] = $row;
    }
    $stmt_tasks->close();
}

$mysqli->close();

// Define status options for the select menu
$status_options = ['Pending', 'In Progress', 'Awaiting Review', 'Completed']; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks | Career Path</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="studentdashboard.css"> 
    <link rel="stylesheet" href="student_tasks.css">
   
</head>
<body>

    <div class="sidebar">
        <div class="logo">Career Path</div>
        <nav class="menu">
            <a href="studentdashboard.php" class="nav-link"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="studentdashboard.php#job_listings" class="nav-link"><i class="fa-solid fa-briefcase"></i> Apply for OJT</a>
            <a href="my_applications.php" class="nav-link"><i class="fa-solid fa-file-contract"></i> My Applications</a>
            <a href="daily_log_submission.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> Daily Log</a> 
            <a href="student_tasks.php" class="nav-link active"><i class="fa-solid fa-list-check"></i> My Tasks</a>
            <a href="#" class="nav-link"><i class="fa-solid fa-user-circle"></i> Profile & Resume</a>
            <a href="index.html" class="nav-link"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="header">
            <h1>My Assigned Tasks</h1>
            <div class="user-info">
                 <i class="fa-solid fa-user-circle"></i> <?php echo $fullname; ?>
            </div>
        </header>

        <div class="dashboard-body task-container">
            
            <?php if (!empty($tasks)): ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card">
                        <h3><?php echo htmlspecialchars($task['task_title']); ?></h3>
                        
                        <div class="task-details">
                            <p><strong>Company:</strong> <?php echo htmlspecialchars($task['company_name']); ?></p>
                            <p><strong>Assigned:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($task['assigned_at']))); ?></p>
                            <p><strong>Due Date:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($task['due_date']))); ?></p>
                            <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($task['task_description'])); ?></p>
                            <p><strong>Current Status:</strong> 
                                <span class="status-badge status-<?php echo str_replace(' ', '-', $task['status']); ?>">
                                    <?php echo htmlspecialchars($task['status']); ?>
                                </span>
                            </p>
                        </div>
                        
                        <form class="status-form" action="update_task_status.php" method="POST">
                            <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                            
                            <label for="status_<?php echo $task['task_id']; ?>">Update Status:</label>
                            <select name="new_status" id="status_<?php echo $task['task_id']; ?>" required>
                                <?php foreach ($status_options as $option): ?>
                                    <option value="<?php echo $option; ?>" <?php echo ($task['status'] === $option ? 'selected disabled' : ''); ?>>
                                        <?php echo $option; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <button type="submit" class="status-update-btn">Update</button>
                        </form>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="empty-tasks">
                    <i class="fa-solid fa-check-circle" style="font-size: 3em; color: #2040e3;"></i>
                    <h2>No Tasks Assigned Yet</h2>
                    <p>Your company hasn't assigned any tasks for your internship yet.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>