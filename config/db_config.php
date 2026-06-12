<?php
// Database connection variables
$host = "localhost";
$username = "root";  // Default XAMPP username
$password = "";      // Default XAMPP password is empty
$dbname = "clinic_db";

// Create connection using MySQLi extension
$conn = new mysqli($host, $username, $password, $dbname);

// Check if the connection established successfully
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// System-wide encoding settings
$conn->set_charset("utf8mb4");
?>