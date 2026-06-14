<?php
session_start();
require_once '../config/db_config.php';

// Security Guard: Only allow logged-in administrators
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if the required parameters were sent via POST
if (isset($_POST['appointment_id']) && isset($_POST['status'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $status = trim($_POST['status']);

    // Validate the incoming status value against database ENUM constraints
    if (!in_array($status, ['confirmed', 'cancelled'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        exit();
    }

    // ... inside handlers/update-status.php (where status becomes 'confirmed')
    if ($status === 'confirmed') {
        // 1. First, fetch the patient_id associated with this appointment
        $patientQuery = "SELECT patient_id, doctor_id FROM appointments WHERE id = ?";
        $pStmt = $conn->prepare($patientQuery);
        $pStmt->bind_param("i", $appointment_id);
        $pStmt->execute();
        $pResult = $pStmt->get_result()->fetch_assoc();
        $pStmt->close();

        if ($pResult) {
            $patient_id = $pResult['patient_id'];
            $doctor_id = $pResult['doctor_id'];

            // 2. Fetch the doctor's name to make the notification message detailed
            $docQuery = "SELECT name FROM doctors WHERE id = ?";
            $dStmt = $conn->prepare($docQuery);
            $dStmt->bind_param("i", $doctor_id);
            $dStmt->execute();
            $docName = $dStmt->get_result()->fetch_assoc()['name'];
            $dStmt->close();

            // 3. Inject the notification record into the database
            $msg = "Your appointment request with " . $docName . " has been successfully confirmed!";
            $notifQuery = "INSERT INTO notifications (patient_id, message) VALUES (?, ?)";
            $nStmt = $conn->prepare($notifQuery);
            $nStmt->bind_param("is", $patient_id, $msg);
            $nStmt->execute();
            $nStmt->close();
        }
    }

    // Update the status in the database
    $updateQuery = "UPDATE appointments SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $status, $appointment_id);

    if ($stmt->execute()) {
        // Return a clean JSON success message back to our JavaScript file
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update execution failed']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
}
?>