<?php
// process_post.php - Handles new post creation and post deletion/status change for Company
session_start();
require_once 'db_connect.php'; 
require_once 'activity_logger.php'; // NEW: Include the logging utility

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
$username = $_SESSION['username'] ?? 'unknown_user'; // For log
$company_id = null;
$company_name = 'Unknown Company'; // For log
$alert_msg = "An unexpected error occurred.";
$redirect_to = 'companydashboard.php';

// --- 2. Find the Company's ID, Status, and Name ---
$stmt_company = $mysqli->prepare("
    SELECT c.company_id, c.company_name, u.status 
    FROM company c 
    JOIN users u ON c.user_id = u.user_id 
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
        $company_name = htmlspecialchars($data['company_name']); // Set company name
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
            (company_id, title, description, requirements, slot_available, status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        
        $stmt_insert = $mysqli->prepare($insert_query);
        
        if ($stmt_insert) {
            // NOTE: Changing 'slots' column name in the query to 'slot_available' to match the database schema
            $stmt_insert->bind_param('isssis', $company_id, $title, $description, $requirements, $slots, $initial_status);
            
            if ($stmt_insert->execute()) {
                
                // --- START LOGGING: Post Creation ---
                $safe_title = htmlspecialchars($title);
                $log_message = "Company **{$company_name}** (User: **{$username}**) submitted a new internship post **'{$safe_title}'** for **{$slots}** slots. Status: **Pending Review**.";
                log_activity($mysqli, $log_message);
                // --- END LOGGING ---

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
    $redirect_to = 'companyposts.php'; // Redirect to the list of posts after deletion attempt

    if ($posting_id) {
        // Fetch the title before deleting (for the log)
        $title_to_delete = "ID: {$posting_id}";
        $stmt_fetch_title = $mysqli->prepare("SELECT title FROM intern_posting WHERE posting_id = ? AND company_id = ? LIMIT 1");
        if ($stmt_fetch_title) {
             $stmt_fetch_title->bind_param('ii', $posting_id, $company_id);
             $stmt_fetch_title->execute();
             $result_title = $stmt_fetch_title->get_result();
             if ($result_title->num_rows > 0) {
                 $title_to_delete = htmlspecialchars($result_title->fetch_assoc()['title']);
             }
             $stmt_fetch_title->close();
        }


        // Only allow deletion of posts belonging to this company
        $delete_query = "DELETE FROM intern_posting WHERE posting_id = ? AND company_id = ?";
        $stmt_delete = $mysqli->prepare($delete_query);

        if ($stmt_delete) {
            $stmt_delete->bind_param('ii', $posting_id, $company_id);
            if ($stmt_delete->execute()) {
                if ($stmt_delete->affected_rows > 0) {
                    
                    // --- START LOGGING: Post Deletion ---
                    $log_message = "Company **{$company_name}** (User: **{$username}**) **deleted** the internship post titled **'{$title_to_delete}'** (ID: {$posting_id}).";
                    log_activity($mysqli, $log_message);
                    // --- END LOGGING ---

                    $alert_msg = "Internship post '{$title_to_delete}' successfully deleted.";
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