<?php
// auth_check.php
session_start();

// 1. Check if the user is logged in at all
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // If not logged in, kill the session and redirect to the main index page
    session_unset();
    session_destroy();
    header("Location: index.html");
    exit;
}

// Note: Specific role checking (e.g., must be a 'student') 
// is handled in the individual dashboard files where this is included.

?>