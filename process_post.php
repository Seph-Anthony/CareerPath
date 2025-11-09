<?php
// process_post.php - Handles new post creation and post deletion/status change for Company
session_start();
require_once 'db_connect.php'; 

// --- Helper Function for Errors and Success ---
function redirect_with_alert($msg, $mysqli, $redirect_page = 'companydashboard.php') {
    if ($mysqli && $mysqli->ping()) {
        $mysqli->close();
    }
    // Use addslashes to ensure the message is safe for JavaScript alert
    $js_msg = addslashes($msg); 
    die("<script>alert('{$js_msg}'); window.location.href='{$redirect_page}';</script>");
}

// --- 1. Security Checks ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html"); 
    exit;
}

$user_id = $_SESSION['user_id'];
$company_id = null;
$alert_msg = "An unexpected error occurred.";
$redirect_to = 'companydashboard.php';

// --- 2. Find the Company's ID and Status ---
$stmt_company = $mysqli->prepare("
    SELECT company_id, u.status 
    FROM company c JOIN users u ON c.user_id = u.user_id 
    WHERE c.user_id = ? 
    LIMIT 1
");
if ($stmt_company) {
    $stmt_company->bind_param('i', $user_id);
    $stmt_company->execute();
    $result = $stmt_company->get_result();
    $data = $result->fetch_assoc();
    $stmt_company->close();
    
    if ($data && $data['status'] === 'Active') {
        $company_id = $data['company_id'];
    } else {
        redirect_with_alert("Account is inactive or not found.", $mysqli);
    }
} else {
    redirect_with_alert("Database connection error.", $mysqli);
}


// =======================================================================
// --- POST (Creation) Logic: Triggered by post_internship.php form ---
// =======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $slots = filter_input(INPUT_POST, 'slot_available', FILTER_VALIDATE_INT);

    if (empty($title) || empty($description) || empty($requirements) || $slots === false || $slots <= 0) {
        $alert_msg = "Please fill in all required fields and ensure slots are greater than 0.";
        $redirect_to = "post_internship.php";
    } else {
        // IMPORTANT: Set initial status to Pending Review for Coordinator approval
        $initial_status = 'Pending Review'; 
        
        $insert_query = "
            INSERT INTO intern_posting 
            (company_id, title, description, requirements, slots, status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        
        $stmt_insert = $mysqli->prepare($insert_query);
        
        if ($stmt_insert) {
            $stmt_insert->bind_param('isssis', $company_id, $title, $description, $requirements, $slots, $initial_status);
            
            if ($stmt_insert->execute()) {
                $alert_msg = "Internship posting titled '{$title}' submitted successfully. It is now **Pending Review** by the Coordinator.";
            } else {
                $alert_msg = "Database Error: Could not create posting. " . $mysqli->error;
            }
            $stmt_insert->close();
        } else {
            $alert_msg = "Database Preparation Error: " . $mysqli->error;
        }
    }
} 


// =======================================================================
// --- GET (Deletion) Logic: Triggered by the delete link on edit_post.php ---
// =======================================================================
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $posting_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($posting_id) {
        // Only allow deletion of posts belonging to this company
        $delete_query = "DELETE FROM intern_posting WHERE posting_id = ? AND company_id = ?";
        $stmt_delete = $mysqli->prepare($delete_query);

        if ($stmt_delete) {
            $stmt_delete->bind_param('ii', $posting_id, $company_id);
            if ($stmt_delete->execute()) {
                if ($stmt_delete->affected_rows > 0) {
                    $alert_msg = "Internship post successfully deleted.";
                } else {
                    $alert_msg = "Post not found or you don't have permission to delete it.";
                }
            } else {
                $alert_msg = "Database Error on deletion: " . $mysqli->error;
            }
            $stmt_delete->close();
        } else {
            $alert_msg = "Database Preparation Error: " . $mysqli->error;
        }
    } else {
        $alert_msg = "Invalid Post ID for deletion.";
    }
}
// =======================================================================


// Final Redirect with message
redirect_with_alert($alert_msg, $mysqli, $redirect_to);
?>