<?php
session_start();
require_once '../config/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (isset($_POST['doctor_id'])) {
    $doctor_id = intval($_POST['doctor_id']);

    $query = "DELETE FROM doctors WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $doctor_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database constraint violation.']);
    }
    $stmt->close();
    $conn->close();
}
?>