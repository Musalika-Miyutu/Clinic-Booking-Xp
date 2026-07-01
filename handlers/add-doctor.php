<?php
session_start();
require_once '../config/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $specialization = trim($_POST['specialization']);
    $bio = trim($_POST['bio']);

    if (!empty($name) && !empty($specialization)) {
        $query = "INSERT INTO doctors (name, specialization, bio) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $name, $specialization, $bio);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: ../admin-dashboard.php");
    exit();
}
?>