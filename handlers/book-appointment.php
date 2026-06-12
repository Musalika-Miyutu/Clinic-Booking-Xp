<?php
// Start the session so we know who is currently logged in
session_start();

// Include our database connection profile
require_once '../config/db_config.php';

// Route back to login if the user isn't logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../index.html");
    exit();
}

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Retrieve and clean form variables
    $patient_id       = $_SESSION['user_id']; // Sourced securely from the active session
    $doctor_id        = intval($_POST['doctor_id']); 
    $appointment_date = trim($_POST['appointment_date']);
    $appointment_time = trim($_POST['appointment_time']);
    $status           = 'pending'; // New appointments default to pending status

    // Validation: Make sure inputs aren't empty
    if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time)) {
        echo "<script>
                alert('All form fields are required.');
                window.location.href = '../dashboard.php';
              </script>";
        exit();
    }

    // Step 1: Check for Double-Booking (Conflict Resolution)
    // Check if this specific doctor already has a booking at this exact date and time
    $checkConflictQuery = "SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'";
    $stmt = $conn->prepare($checkConflictQuery);
    $stmt->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // The doctor is busy at this time
        echo "<script>
                alert('Sorry, this time slot has already been booked for this doctor. Please choose a different time or date.');
                window.location.href = '../dashboard.php';
              </script>";
        $stmt->close();
        exit();
    }
    $stmt->close();

    // Step 2: Save the Appointment
    // If no conflict exists, proceed with inserting the booking record
    $insertAppointmentQuery = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertAppointmentQuery);
    $stmt->bind_param("iisss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $status);

    if ($stmt->execute()) {
        // Success! Alert the user and refresh the dashboard
        echo "<script>
                alert('Success! Your appointment has been requested.');
                window.location.href = '../dashboard.php';
              </script>";
    } else {
        echo "Error saving appointment to database: " . $stmt->error;
    }

    // Close remaining connections
    $stmt->close();
    $conn->close();

} else {
    // Redirect if someone tries to look at this page directly via URL
    header("Location: ../dashboard.php");
    exit();
}
?>