<?php
include 'db_connect.php'; // make sure this connects properly

if (isset($_POST['username'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);

    // Check if username already exists in the students table
    $query = "SELECT username FROM students WHERE username = '$username' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        echo "taken";
    } else {
        echo "available";
    }
}
?>
