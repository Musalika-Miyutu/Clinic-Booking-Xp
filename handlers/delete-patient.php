<?php
session_start();
require_once '../config/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (isset($_POST['patient_id'])) {
    $patient_id = intval($_POST['patient_id']);

    // Protect against self-deletion
    if ($patient_id === $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own admin account.']);
        exit();
    }

    $query = "DELETE FROM users WHERE id = ? AND role = 'patient'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $patient_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete user record.']);
    }
    $stmt->close();
    $conn->close();
}
?>