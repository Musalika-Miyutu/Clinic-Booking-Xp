<?php
require_once '../config/db_config.php';

// Check if doctor ID and date are provided
if (isset($_GET['doctor_id']) && isset($_GET['date'])) {
    $doctor_id = intval($_GET['doctor_id']);
    $date = trim($_GET['date']);

    // Query to find all time slots that are NOT cancelled for this doctor on this day
    $query = "SELECT appointment_time FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $doctor_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $booked_slots = [];
    while ($row = $result->fetch_assoc()) {
        // Store just the time string (e.g., "09:00:00") into our array
        $booked_slots[] = $row['appointment_time'];
    }

    $stmt->close();
    $conn->close();

    // Return the array of taken slots cleanly as a JSON response back to JavaScript
    header('Content-Type: application/json');
    echo json_encode($booked_slots);
} else {
    echo json_encode([]);
}
?>