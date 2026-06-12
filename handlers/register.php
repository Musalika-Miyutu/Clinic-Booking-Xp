<?php
// Start a secure session
session_start();

// Include our database connection credentials
require_once '../config/db_config.php';

// Check if the form was actually submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Clean and sanitize the user inputs to strip away any malicious scripts
    $name = trim(htmlspecialchars($_POST['name']));
    $email = trim(htmlspecialchars($_POST['email']));
    $password = trim($_POST['password']);
    
    // Default system role for new signups
    $role = 'patient'; 

    // Basic Validation: Ensure fields aren't empty
    if (empty($name) || empty($email) || empty($password)) {
        echo "All fields are required. Please go back and try again.";
        exit();
    }

    // Step 1: Check if the email address is already registered
    $checkEmailQuery = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($checkEmailQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Email already exists
        echo "<script>
                alert('This email is already registered. Please log in.');
                window.location.href = '../index.html';
              </script>";
        $stmt->close();
        exit();
    }
    $stmt->close();

    // Step 2: Encrypt the password using modern BCRYPT hashing
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Step 3: Insert the new record into the database
    $insertQuery = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    
    // 'ssss' maps to four  string variables: name, email, hashed_password, and role
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        // Success! Redirect the user to the login screen with a friendly confirmation
        echo "<script>
                alert('Registration successful! You can now log in.');
                window.location.href = '../index.html';
              </script>";
    } else {
        echo "Something went wrong execution-wise: " . $stmt->error;
    }

    // Close open resources
    $stmt->close();
    $conn->close();
} else {
    // If someone tries to access register.php directly via URL, kick them back to index.html
    header("Location: ../index.html");
    exit();
}
?>