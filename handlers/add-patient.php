<?php
session_start();
require_once '../config/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']); // Using raw input matching your current seed state

    if (!empty($name) && !empty($email) && !empty($password)) {
        $query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'patient')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $name, $email, $password);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: ../admin-dashboard.php");
    exit();
}
?>