<?php
session_start();
require_once 'config/db_config.php';

// Session Guard: If a user is not logged in, or if an admin tries to access the patient space, deny access.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: index.html");
    exit();
}

$patient_id = $_SESSION['user_id'];

// Fetch unread notifications for this patient
$notifCountQuery = "SELECT COUNT(*) as unread FROM notifications WHERE patient_id = ? AND is_read = 0";
$nStmt = $conn->prepare($notifCountQuery);
$nStmt->bind_param("i", $patient_id);
$nStmt->execute();
$unreadCount = $nStmt->get_result()->fetch_assoc()['unread'];
$nStmt->close();

// Fetch the actual message text strings
$notifDataQuery = "SELECT id, message FROM notifications WHERE patient_id = ? AND is_read = 0 ORDER BY created_at DESC";
$nDataStmt = $conn->prepare($notifDataQuery);
$nDataStmt->bind_param("i", $patient_id);
$nDataStmt->execute();
$notifResult = $nDataStmt->get_result();

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
            <!-- Live Notification Panel -->
            <?php if ($unreadCount > 0): ?>
                <div id="notification-box-area" style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px; margin-bottom: 20px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="color: #1d4ed8;">🔔 Alerts Dashboard Updates (<?php echo $unreadCount; ?>)</strong>
                        <ul style="margin: 5px 0 0 0; padding-left: 20px; color: #1e3a8a; font-size: 0.9rem;">
                            <?php while($notif = $notifResult->fetch_assoc()): ?>
                                <li style="margin-bottom: 4px;"><?php echo htmlspecialchars($notif['message']); ?></li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                    <button onclick="dismissNotifications()" style="background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 0.8rem; cursor: pointer; font-weight: 600;">Mark as Read</button>
                </div>
            <?php endif; ?>

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
                                <th style="padding: 12px;">Actions</th> </tr>
                        </thead>
                        <tbody>
                            <?php
                            $patient_id = $_SESSION['user_id'];
                            // Added appointments.id to the selection query
                            $fetchApps = "SELECT appointments.id as appointment_id, appointments.appointment_date, appointments.appointment_time, appointments.status, doctors.name, doctors.specialization 
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
                                    $appId = $app['appointment_id'];
                                    echo "<tr style='border-bottom: 1px solid #e2e8f0;'>";
                                    echo "<td style='padding:12px;'><strong>".htmlspecialchars($app['name'])."</strong></td>";
                                    echo "<td style='padding:12px;'>".htmlspecialchars($app['specialization'])."</td>";
                                    echo "<td style='padding:12px;'>".date("M d, Y", strtotime($app['appointment_date']))."</td>";
                                    echo "<td style='padding:12px;'>".date("g:i A", strtotime($app['appointment_time']))."</td>";
                                    // Added id attribute to this status badge so JavaScript can alter it live
                                    echo "<td style='padding:12px;'><span id='patient-status-badge-$appId' class='badge status-".htmlspecialchars($app['status'])."'>".htmlspecialchars($app['status'])."</span></td>";
                                    
                                    // Render the Cancel button column dynamically
                                    echo "<td style='padding:12px;'>";
                                    if ($app['status'] !== 'cancelled') {
                                        echo "<button id='btn-cancel-$appId' class='btn-patient-cancel' onclick='cancelAppointment($appId)'>Cancel</button>";
                                    } else {
                                        echo "<span style='color:#94a3b8; font-size:0.85rem; font-style:italic;'>None</span>";
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' style='padding:20px; text-align:center; color:#64748b;'>You haven't scheduled any appointments yet.</td></tr>";
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

    <script>
    // Tab navigation switching engine
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(panel => panel.classList.add('hidden-panel'));
            const targetId = this.getAttribute('data-target');
            document.getElementById(targetId).classList.remove('hidden-panel');
        });
    });

    // Doctor selection synchronization card processor
    function selectDoctor(id, name) {
        const docIdField = document.getElementById('selected-doctor-id');
        const docNameField = document.getElementById('selected-doctor-name');

        if(docIdField && docNameField) {
           docIdField.value = id;
           docNameField.value = name;
        }

        document.querySelectorAll('.doctor-card').forEach(card => card.classList.remove('active-card'));
        if(event && event.currentTarget) {
           event.currentTarget.classList.add('active-card');
        }

        // Fire checking mechanism when changing doctors
        fetchBookedSlots();
    }

    // Background AJAX filtering logic core engine
    function fetchBookedSlots() {
        const doctorIdField = document.getElementById('selected-doctor-id');
        const dateField = document.getElementById('appointment-date');
        const timeSelectField = document.getElementById('appointment-time');

        // Safety guard: stop if fields are completely missing from DOM layout
        if (!doctorIdField || !dateField || !timeSelectField) {
           console.error("Critical Mismatch: Form elements are missing their expected IDs.");
           return;
        }

        const doctorId = doctorIdField.value;
        const selectedDate = dateField.value;

        // Do not run background request if date field is empty
        if (!doctorId || !selectedDate) return;

        console.log(`Checking availability for Doctor ID: ${doctorId} on Date: ${selectedDate}...`);

        fetch(`handlers/check-slots.php?doctor_id=${doctorId}&date=${selectedDate}`)
            .then(response => response.json())
            .then(bookedSlots => {
                console.log("Booked slots fetched from server database: ", bookedSlots);
                
                Array.from(timeSelectField.options).forEach(option => {
                    if (option.value === "") return; // Skip default message index

                    if (bookedSlots.includes(option.value)) {
                        option.disabled = true;
                        if (!option.text.includes('(Booked)')) {
                            option.text = option.text + " (Booked)";
                        }
                    } else {
                        option.disabled = false;
                        option.text = option.text.replace(" (Booked)", "");
                    }
                });
            })
            .catch(error => console.error('AJAX Failure retrieving slots data:', error));
    }

    // Initialize active element lifecycle monitors 
    document.addEventListener('DOMContentLoaded', () => {
        const dateInputSelector = document.getElementById('appointment-date');
        if (dateInputSelector) {
            // Universal listeners targeting explicit user interaction events
            dateInputSelector.addEventListener('input', fetchBookedSlots);
            dateInputSelector.addEventListener('change', fetchBookedSlots);
            dateInputSelector.addEventListener('blur', fetchBookedSlots);
        }
        // Perform a safe fallback execution call right upon initial layout creation
        fetchBookedSlots();
    });
    
    // Function to let patients cancel an appointment instantly via AJAX
    function cancelAppointment(appointmentId) {
        if (!confirm("Are you sure you want to cancel this appointment?")) return;

        const formData = new FormData();
        formData.append('appointment_id', appointmentId);

        fetch('handlers/cancel-booking.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 1. Update the status badge on the screen instantly
                const badge = document.getElementById(`patient-status-badge-${appointmentId}`);
                if (badge) {
                    badge.innerText = 'cancelled';
                    badge.className = 'badge status-cancelled';
                }

                // 2. Remove the button container and turn it into a text placeholder
                const cancelBtn = document.getElementById(`btn-cancel-${appointmentId}`);
                if (cancelBtn) {
                    cancelBtn.parentElement.innerHTML = "<span style='color:#94a3b8; font-size:0.85rem; font-style:italic;'>None</span>";
                }
                
                // 3. Force re-run the time-slot checking loop in case they are looking at the booking tab
                fetchBookedSlots();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Cancellation AJAX Error:', error);
            alert('An unexpected connection error occurred.');
        });
    }

   function dismissNotifications() {
    fetch('handlers/dismiss-notifs.php')
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            const notifPanel = document.getElementById('notification-box-area');
            if(notifPanel) {
                notifPanel.style.display = 'none'; // Fade out cleanly on user screen
            }
        }
    });
 } 
 </script>
</body>
</html>
<?php $conn->close(); ?>