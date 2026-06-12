<?php
// Start the session framework
session_start();

// Include our secure database connection profile
require_once '../config/db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitize user inputs
    $email = trim(htmlspecialchars($_POST['email']));
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        echo "Please fill in both email and password fields.";
        exit();
    }

    // Step 1: Query the database for a record matching the input email
    $loginQuery = "SELECT id, name, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($loginQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Step 2: Check if a matching user row exists
    if ($result->num_rows === 1) {
        // Fetch the data row as an associative array
        $user = $result->fetch_assoc();

        // Step 3: Verify the plain text input against our encrypted database hash
        if (password_verify($password, $user['password'])) {
            
            // Credentials are valid! Store critical info in global session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $user['role'];

            // Step 4: Route the logged-in user to the correct dashboard based on their role
            if ($user['role'] === 'admin') {
                header("Location: ../admin-dashboard.php"); // We will build this later
            } else {
                header("Location: ../dashboard.php"); // Route patients to the booking page
            }
            exit();
            
        } else {
            // Password did not match
            echo "<script>
                    alert('Invalid password. Please try again.');
                    window.location.href = '../index.html';
                  </script>";
            exit();
        }
    } else {
        // No user found with that email address
        echo "<script>
                alert('No account found with that email address.');
                window.location.href = '../index.html';
              </script>";
        exit();
    }

    // Close open handles
    $stmt->close();
    $conn->close();
} else {
    // Redirect if someone attempts direct browser URL access
    header("Location: ../index.html");
    exit();
}
?>