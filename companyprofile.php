<?php
// companyprofile.php - Handles company profile display and editing
session_start();
require_once 'db_connect.php'; // Your database connection file

// 1. Authentication and Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: signincompany.html"); 
    exit;
}

$user_id = $_SESSION['user_id'];
$company_id = null;
$error = "";
$message = "";

// Initialize variables with default values
$company_name = "Company Name";
$industry = "N/A";
$address = "N/A";
$contact_person = "N/A";
$email = "N/A";
$phone_number = "N/A";
$description = "No description provided.";

// --- Message Handling from URL ---
if (isset($_GET['status']) && isset($_GET['msg'])) {
    $status = $_GET['status'];
    $msg = htmlspecialchars($_GET['msg']);
    
    if ($status === 'success') {
        $message = $msg;
    } elseif ($status === 'error') {
        $error = $msg;
    }
}

// --- 2. Fetch Company Profile Data ---
$stmt_company = $mysqli->prepare("
    SELECT 
        company_id,
        company_name, 
        industry, 
        address, 
        contact_person, 
        email, 
        phone_number,
        description
    FROM 
        company 
    WHERE 
        user_id = ?
    LIMIT 1
");

if ($stmt_company) {
    $stmt_company->bind_param('i', $user_id);
    $stmt_company->execute();
    $result_company = $stmt_company->get_result();
    
    if ($result_company->num_rows === 1) {
        $company_data = $result_company->fetch_assoc();
        $company_id = $company_data['company_id'];
        
        // Assign fetched data (htmlspecialchars used for display)
        $company_name = htmlspecialchars($company_data['company_name']);
        $industry = htmlspecialchars($company_data['industry']);
        $address = htmlspecialchars($company_data['address']);
        $contact_person = htmlspecialchars($company_data['contact_person']);
        $email = htmlspecialchars($company_data['email']);
        $phone_number = htmlspecialchars($company_data['phone_number']);
        $description = htmlspecialchars($company_data['description']);
    } else {
        $error = "Company profile not found. Please contact the administrator.";
    }
    $stmt_company->close();
} else {
    $error = "Database query failed during profile retrieval: " . $mysqli->error;
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile | <?php echo $company_name; ?></title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="companydashboard.css"> 
    <style>
        /* Shared Styles for Profile Cards (Adopted from student version) */
        .section-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        .profile-details p {
            margin: 8px 0;
            line-height: 1.5;
        }
        .profile-details strong {
            display: inline-block;
            width: 150px; /* Aligns the data points */
            color: #555;
        }

        /* Profile Edit Form Styles */
        #edit-profile-form {
            display: none; /* Initially hidden */
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        #edit-profile-form .form-group {
            margin-bottom: 15px;
        }
        #edit-profile-form label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 14px;
        }
        #edit-profile-form input[type="text"], 
        #edit-profile-form input[type="email"], 
        #edit-profile-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-family: inherit;
        }
        #edit-profile-form textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-primary {
            background-color: rgb(32, 64, 227);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: rgb(25, 50, 190);
        }
        .btn-secondary {
            background-color: #f0f0f0;
            color: #555;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn-secondary:hover {
            background-color: #e0e0e0;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">
                <h2>Company Portal</h2>
            </div>
           <nav class="menu">
        <a href="companydashboard.php" ><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="post_internship.php"><i class="fa-solid fa-file-circle-plus"></i> Post Internship</a>
        <a href="manage_applicants.php"><i class="fa-solid fa-users"></i> View Applicants</a>
        <a href="company_log_approval.php"><i class="fa-solid fa-file-signature"></i> Approve Daily Logs</a>
        <a href="intern_progress.php" class="active"><i class="fa-solid fa-chart-line"></i> Intern Progress</a>
   <a href="companyprofile.php"><i class="fa-solid fa-user-circle"></i> Edit Information</a>
        <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
    </nav>
        </aside>

        <div class="main-content">
            <header class="topbar">
                <h1>My Company Profile</h1>
                <p>View and manage your company's information.</p>
            </header>

            <div class="dashboard-body">

                <?php if ($message): ?>
                    <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?php echo $message; ?></div>
                <?php elseif ($error): ?>
                    <div class="alert error"><i class="fa-solid fa-circle-xmark"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <div class="section-container profile-card">
                    <h2><i class="fas fa-id-card"></i> Company Information</h2>
                    
                    <div id="profile-view">
                        <div class="profile-details">
                            <p><strong>Company Name:</strong> <span><?php echo $company_name; ?></span></p>
                            <p><strong>Industry:</strong> <span><?php echo $industry; ?></span></p>
                            <p><strong>Address:</strong> <span><?php echo $address; ?></span></p>
                            <p><strong>Contact Person:</strong> <span><?php echo $contact_person; ?></span></p>
                            <p><strong>Email:</strong> <span><?php echo $email; ?></span></p>
                            <p><strong>Phone:</strong> <span><?php echo $phone_number; ?></span></p>
                            <p><strong>Description:</strong> <span><?php echo nl2br($description); ?></span></p>
                        </div>
                        <button id="btn-edit" class="btn-primary" style="margin-top: 15px;"><i class="fas fa-edit"></i> Edit Information</button>
                    </div>

                    <form id="edit-profile-form" action="update_company_profile.php" method="POST">
                        <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                        
                        <div class="form-group">
                            <label for="company_name">Company Name <span class="required">*</span></label>
                            <input type="text" id="company_name" name="company_name" value="<?php echo $company_name; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="industry">Industry <span class="required">*</span></label>
                            <input type="text" id="industry" name="industry" value="<?php echo $industry; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Address <span class="required">*</span></label>
                            <input type="text" id="address" name="address" value="<?php echo $address; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="contact_person">Contact Person <span class="required">*</span></label>
                            <input type="text" id="contact_person" name="contact_person" value="<?php echo $contact_person; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone_number">Phone Number</label>
                            <input type="text" id="phone_number" name="phone_number" value="<?php echo $phone_number; ?>">
                        </div>
                        <div class="form-group">
                            <label for="description">Company Description</label>
                            <textarea id="description" name="description"><?php echo $description; ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
                            <button type="button" id="btn-cancel" class="btn-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
                </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileView = document.getElementById('profile-view');
            const editForm = document.getElementById('edit-profile-form');
            const btnEdit = document.getElementById('btn-edit');
            const btnCancel = document.getElementById('btn-cancel');

            if (btnEdit && btnCancel) {
                btnEdit.addEventListener('click', function() {
                    profileView.style.display = 'none';
                    editForm.style.display = 'block';
                });

                btnCancel.addEventListener('click', function() {
                    editForm.style.display = 'none';
                    profileView.style.display = 'block';
                });
            }
        });
    </script>
</body>
</html>