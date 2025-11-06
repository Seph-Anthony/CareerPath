<?php
// db_connect.php
// Put this file where your PHP can include/require it and update credentials.

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = ''; // or your XAMPP password
$DB_NAME = 'ojtfind';
$DB_PORT = 3306; // change to 3306 if default

// create connection
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, (int)$DB_PORT);

// check connection
if ($mysqli->connect_errno) {
    // In production, log error and show a generic message
    die("Database connection failed: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
}

// set charset
$mysqli->set_charset("utf8mb4");
