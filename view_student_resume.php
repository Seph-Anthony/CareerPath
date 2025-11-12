<?php
session_start();
require_once 'db_connect.php';

// 1. Authorization Check (Only company users should view this)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html");
    exit;
}

// 2. Get Student ID from URL
if (!isset($_GET['student_id']) || !is_numeric($_GET['student_id'])) {
    die("Error: Student ID not provided or invalid.");
}
$student_id = (int)$_GET['student_id'];

// --- Database Logic ---

// *** FIX 1: Change document_type from 'Resume' to 'Information' ***
$stmt = $mysqli->prepare("
    SELECT 
        s.first_name, 
        s.last_name, 
        sr.file_path, 
        sr.approval_status
    FROM 
        student s 
    LEFT JOIN 
        student_requirements sr ON s.student_id = sr.student_id AND sr.document_type = 'Information'
    WHERE 
        s.student_id = ?
    ORDER BY
        sr.upload_date DESC
    LIMIT 1
");

$full_name = "Unknown Student";
$document_path = null; // Renamed variable
$approval_status = 'Not Uploaded'; // Default status if no record is found in student_requirements

if ($stmt) {
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows >= 1) {
        $student_data = $result->fetch_assoc();
        $full_name = htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']);
        
        // Check if a document record was found via the LEFT JOIN
        if ($student_data['file_path'] !== null) {
            $document_path = $student_data['file_path']; 
            $approval_status = $student_data['approval_status'];
        }
    } else {
        die("Error: Student not found.");
    }
    $stmt->close();
} else {
    die("Database error: " . $mysqli->error);
}

$mysqli->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- FIX 2: Update Title Text -->
    <title>Required Information for <?php echo $full_name; ?></title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: rgb(247, 250, 252);
            color: #333;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        .resume-container {
            width: 100%;
            max-width: 900px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 30px;
        }
        .status-message {
            padding: 40px;
            text-align: center;
            border: 2px solid #ffcc00;
            border-radius: 8px;
            background-color: #fff8e1;
            color: #795548;
            margin-bottom: 20px;
        }
        .status-message h2 {
            margin-top: 0;
            font-size: 24px;
            color: #ff9800;
        }
        .status-message p {
            font-size: 16px;
            line-height: 1.6;
        }
        .document-iframe { /* Renamed class for clarity */
            width: 100%;
            height: 80vh;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn-back {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 15px;
            background-color: rgb(32, 64, 227);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn-back:hover {
            background-color: rgb(25, 50, 190);
        }
        /* Style for Download Link (New Addition) */
        .btn-download {
            display: inline-block;
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 15px;
            transition: background-color 0.3s;
        }
        .btn-download:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="resume-container">
        <a href="javascript:history.back()" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to Applicants
        </a>
        
        <!-- FIX 3: Update Header Text -->
        <h1>Required Information Document for <?php echo $full_name; ?></h1>

        <?php if ($approval_status === 'Approved' && !empty($document_path)): ?>
            <p>Coordinator has approved this document. You can view it below or download it directly.</p>
            
            <!-- Link to download the file directly, crucial if iframe fails or file is doc/docx -->
            <a href="<?php echo htmlspecialchars($document_path); ?>" download class="btn-download">
                <i class="fa-solid fa-download"></i> Download File
            </a>

            <!-- FIX 4: Use $document_path and update class name -->
            <iframe src="<?php echo htmlspecialchars($document_path); ?>" class="document-iframe"></iframe>

        <?php elseif ($approval_status === 'Pending'): ?>
            <div class="status-message">
                <h2><i class="fa-solid fa-hourglass-half"></i> Awaiting Coordinator Review</h2>
                <p>This student's document is currently under review by the OJT Coordinator. Once approved, the document will become visible to you here.</p>
            </div>

        <?php elseif ($approval_status === 'Rejected'): ?>
            <div class="status-message" style="border-color: #ff6666; background-color: #ffeaea; color: #8d0000;">
                <h2><i class="fa-solid fa-xmark-circle"></i> Requirements Rejected</h2>
                <p>The coordinator has indicated that the student's document needs revision and has not yet approved it for viewing.</p>
            </div>

        <?php else: ?>
            <div class="status-message" style="border-color: #888; background-color: #f0f0f0; color: #555;">
                <h2><i class="fa-solid fa-info-circle"></i> Document Not Uploaded</h2>
                <p>The student has not uploaded their required document or the document status is currently indeterminate.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>