<?php
session_start();
require_once 'config/db_config.php';

// Session Guard: Only allow users with the 'admin' role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location:index.html");
    exit();
}

// 1. Fetch appointments for Tab 1
$adminQuery = "SELECT 
                appointments.id AS appointment_id,
                users.name AS patient_name,
                users.email AS patient_email,
                doctors.name AS doctor_name,
                appointments.appointment_date,
                appointments.appointment_time,
                appointments.status
               FROM appointments
               INNER JOIN users ON appointments.patient_id = users.id
               INNER JOIN doctors ON appointments.doctor_id = doctors.id
               ORDER BY appointments.appointment_date ASC, appointments.appointment_time ASC";
$appointmentsResult = $conn->query($adminQuery);

// 2. Fetch doctors for Tab 2
$doctorsQuery = "SELECT id, name, specialization, bio FROM doctors";
$doctorsResult = $conn->query($doctorsQuery);

// 3. Fetch patients for Tab 3
$patientsQuery = "SELECT id, name, email, created_at FROM users WHERE role = 'patient' ORDER BY created_at DESC";
$patientsResult = $conn->query($patientsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management Suite - ClinicCare</title>
    <link rel="stylesheet" href="assets/css/dashboard-style.css">
    <style>
        .table-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background-color: #f1f5f9;
            color: #475569;
            padding: 14px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.95rem;
        }

        tr:hover {
            background-color: #f8fafc;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending { background-color: #fef3c7; color: #d97706; }
        .status-confirmed { background-color: #dcfce7; color: #16a34a; }
        .status-cancelled { background-color: #fee2e2; color: #dc2626; }

        /* Tabs styling */
        .tab-content { animation: fadeIn 0.3s ease; }
        .hidden-panel { display: none !important; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="logo-area">
                <h2>ClinicCare</h2>
            </div>
            <nav class="nav-links">
                <a href="#" class="nav-tab active" data-target="schedule-section">Master Schedule</a>
                <a href="#" class="nav-tab" data-target="doctors-section">Manage Doctors</a>
                <a href="#" class="nav-tab" data-target="patients-section">Patient Records</a>
            </nav>
            <div class="sidebar-footer">
                <p>Role: <strong>Administrator</strong></p>
                <a href="index.html" class="btn-logout">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1>Welcome to the Clinic Management Suite</h1>
                <p>Review metrics, update staff lists, and monitor medical appointments.</p>
            </header>

            <div id="schedule-section" class="tab-content">
                <div class="table-container">
                    <h3>Scheduled Appointments</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient Name</th>
                                <th>Email Address</th>
                                <th>Assigned Doctor</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($appointmentsResult && $appointmentsResult->num_rows > 0) {
                                while ($row = $appointmentsResult->fetch_assoc()) {
                                    $formattedTime = date("g:i A", strtotime($row['appointment_time']));
                                    $formattedDate = date("F j, Y", strtotime($row['appointment_date']));
                                    
                                    echo "<tr>";
                                    echo "<td>" . $row['appointment_id'] . "</td>";
                                    echo "<td><strong>" . htmlspecialchars($row['patient_name']) . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($row['patient_email']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
                                    echo "<td>" . $formattedDate . "</td>";
                                    echo "<td>" . $formattedTime . "</td>";
                                    echo "<td><span class='status-badge status-" . $row['status'] . "'>" . $row['status'] . "</span></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' style='text-align:center; color:#64748b;'>No appointments scheduled at this time.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="doctors-section" class="tab-content hidden-panel">
                <div class="table-container">
                    <h3>Registered Clinic Specialists</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Doctor Name</th>
                                <th>Specialization</th>
                                <th>Biography / Profile</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($doctorsResult && $doctorsResult->num_rows > 0) {
                                while ($doc = $doctorsResult->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $doc['id'] . "</td>";
                                    echo "<td><strong>" . htmlspecialchars($doc['name']) . "</strong></td>";
                                    echo "<td><span class='badge' style='background:#e0f2fe; color:#0369a1; padding:4px 10px; border-radius:20px; font-size:0.8rem; font-weight:600;'>" . htmlspecialchars($doc['specialization']) . "</span></td>";
                                    echo "<td style='color:#64748b; font-size:0.9rem; max-width:400px;'>" . htmlspecialchars($doc['bio']) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' style='text-align:center;'>No doctors registered in the system database.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="patients-section" class="tab-content hidden-panel">
                <div class="table-container">
                    <h3>Registered Patient Database</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Patient Name</th>
                                <th>Email Address</th>
                                <th>Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($patientsResult && $patientsResult->num_rows > 0) {
                                while ($pat = $patientsResult->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $pat['id'] . "</td>";
                                    echo "<td><strong>" . htmlspecialchars($pat['name']) . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($pat['email']) . "</td>";
                                    echo "<td>" . date("F j, Y, g:i A", strtotime($pat['created_at'])) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' style='text-align:center;'>No patients registered yet.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script src="assets/js/admin-dashboard.js"></script>
</body>
</html>
<?php $conn->close(); ?>