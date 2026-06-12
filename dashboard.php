<?php
session_start();
require_once 'config/db_config.php';

// Session Guard: If a user is not logged in, or if an admin tries to access the patient space, deny access.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../index.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - ClinicCare</title>
    <link rel="stylesheet" href="assets/css/dashboard-style.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="logo-area">
                <h2>ClinicCare</h2>
            </div>
            <nav class="nav-links">
                <a href="#" class="nav-tab active" data-target="book-section">Book Appointment</a>
                <a href="#" class="nav-tab" data-target="appointments-section">My Appointments</a>
                <a href="#" class="nav-tab" data-target="profile-section">Profile Settings</a>
            </nav>
            <div class="sidebar-footer">
                <p>Logged in as: <br><strong id="user-email"><?php echo htmlspecialchars($_SESSION['user_email']); ?></strong></p>
                <a href="index.html" class="btn-logout">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1>Welcome Back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
                <p>Select a specialist and choose a date to secure your appointment.</p>
            </header>

            <div id="book-section" class="tab-content">
                <div class="dashboard-grid">
                    
                    <section class="doctors-section">
                        <h3>Available Specialists</h3>
                        <div class="doctors-list">
                            <?php
                            // Query to pull all doctors from the database
                            $getDoctorsQuery = "SELECT id, name, specialization, bio FROM doctors";
                            $result = $conn->query($getDoctorsQuery);

                            // Track the first iteration to set defaults for the form block
                            $isFirst = true;
                            $defaultDoctorId = 1;
                            $defaultDoctorName = "";

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $cardClass = $isFirst ? 'doctor-card active-card' : 'doctor-card';
                                    if ($isFirst) {
                                        $defaultDoctorId = $row['id'];
                                        $defaultDoctorName = $row['name'];
                                        $isFirst = false;
                                    }
                                    ?>
                                    <div class="<?php echo $cardClass; ?>" onclick="selectDoctor(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>')">
                                        <div class="doc-info">
                                            <h4><?php echo htmlspecialchars($row['name']); ?></h4>
                                            <span class="badge"><?php echo htmlspecialchars($row['specialization']); ?></span>
                                            <p><?php echo htmlspecialchars($row['bio']); ?></p>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo "<p>No doctors are currently available at this clinic.</p>";
                            }
                            ?>
                        </div>
                    </section>

                    <section class="booking-section">
                        <h3>Schedule Your Visit</h3>
                        <div class="booking-box">
                            <form action="handlers/book-appointment.php" method="POST" id="appointmentForm">
                                <input type="hidden" id="selected-doctor-id" name="doctor_id" value="<?php echo $defaultDoctorId; ?>">

                                <div class="form-group">
                                    <label>Selected Specialist</label>
                                    <input type="text" id="selected-doctor-name" class="disabled-input" value="<?php echo htmlspecialchars($defaultDoctorName); ?>" readonly>
                                </div>

                                <div class="form-group">
                                    <label for="appointment-date">Choose Date</label>
                                    <input type="date" id="appointment-date" name="appointment_date" required>
                                </div>

                                <div class="form-group">
                                    <label for="appointment-time">Choose Time Slot</label>
                                    <select id="appointment-time" name="appointment_time" required>
                                        <option value="" disabled selected>Select an available time</option>
                                        <option value="09:00:00">09:00 AM</option>
                                        <option value="10:30:00">10:30 AM</option>
                                        <option value="11:00:00">11:00 AM</option>
                                        <option value="14:00:00">02:00 PM</option>
                                        <option value="15:30:00">03:30 PM</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn-submit-booking">Confirm Booking</button>
                            </form>
                        </div>
                    </section>

                </div>
            </div>

            <div id="appointments-section" class="tab-content hidden-panel">
                <h3>My Scheduled Appointments</h3>
                <div class="booking-box">
                    <table style="width:100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="background:#f1f5f9; border-bottom: 2px solid #e2e8f0;">
                                <th style="padding: 12px;">Doctor</th>
                                <th style="padding: 12px;">Specialization</th>
                                <th style="padding: 12px;">Date</th>
                                <th style="padding: 12px;">Time</th>
                                <th style="padding: 12px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $patient_id = $_SESSION['user_id'];
                            $fetchApps = "SELECT appointments.appointment_date, appointments.appointment_time, appointments.status, doctors.name, doctors.specialization 
                                          FROM appointments 
                                          INNER JOIN doctors ON appointments.doctor_id = doctors.id 
                                          WHERE appointments.patient_id = ? 
                                          ORDER BY appointment_date DESC";
                            
                            $appStmt = $conn->prepare($fetchApps);
                            $appStmt->bind_param("i", $patient_id);
                            $appStmt->execute();
                            $appResult = $appStmt->get_result();
                            
                            if ($appResult->num_rows > 0) {
                                while ($app = $appResult->fetch_assoc()) {
                                    echo "<tr style='border-bottom: 1px solid #e2e8f0;'>";
                                    echo "<td style='padding:12px;'><strong>".htmlspecialchars($app['name'])."</strong></td>";
                                    echo "<td style='padding:12px;'>".htmlspecialchars($app['specialization'])."</td>";
                                    echo "<td style='padding:12px;'>".date("M d, Y", strtotime($app['appointment_date']))."</td>";
                                    echo "<td style='padding:12px;'>".date("g:i A", strtotime($app['appointment_time']))."</td>";
                                    echo "<td style='padding:12px;'><span class='badge'>".htmlspecialchars($app['status'])."</span></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' style='padding:20px; text-align:center; color:#64748b;'>You haven't scheduled any appointments yet.</td></tr>";
                            }
                            $appStmt->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="profile-section" class="tab-content hidden-panel">
                <h3>Profile Settings</h3>
                <div class="booking-box" style="max-width: 500px;">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="disabled-input" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Registered Email Address</label>
                        <input type="text" class="disabled-input" value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Account Hierarchy Tier</label>
                        <input type="text" class="disabled-input" value="Standard Patient Access Platform" readonly>
                    </div>
                    <p style="font-size:0.85rem; color:#64748b; margin-top:10px;">Profile modification functions are managed securely by the medical records administrative team.</p>
                </div>
            </div>

        </main>
    </div>

    <script src="assets/js/dashboard.js"></script>
</body>
</html>