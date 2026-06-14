<?php
session_start();
require_once '../config/db_config.php';

if (isset($_SESSION['user_id'])) {
    $patient_id = $_SESSION['user_id'];
    $clearQuery = "UPDATE notifications SET is_read = 1 WHERE patient_id = ?";
    $stmt = $conn->prepare($clearQuery);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
}
?>