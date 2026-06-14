<?php
session_start();
require_once '../config/db_config.php';

// Security Guard: Only allow logged-in patients
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (isset($_POST['appointment_id'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $patient_id = $_SESSION['user_id'];

    // Extra Security Check: Ensure this appointment actually belongs to the logged-in patient
    // and that its current status is still 'pending' or 'confirmed'
    $checkQuery = "SELECT id FROM appointments WHERE id = ? AND patient_id = ? AND status != 'cancelled'";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $appointment_id, $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found or already cancelled.']);
        exit();
    }
    $stmt->close();

    // Perform the update
    $updateQuery = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("i", $appointment_id);

    if ($updateStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update database.']);
    }

    $updateStmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Missing parameter data.']);
}
?>