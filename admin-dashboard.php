<?php
session_start();
require_once 'config/db_config.php';

// Session Guard: Only allow users with the 'admin' role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location:index.html");
    exit();
}

// 1. Fetch Total Bookings Count
$totalQuery = "SELECT COUNT(*) as total FROM appointments";
$totalResult = $conn->query($totalQuery);
$totalBookings = ($totalResult) ? $totalResult->fetch_assoc()['total'] : 0;

// 2. Fetch Pending Approvals Count
$pendingQuery = "SELECT COUNT(*) as pending FROM appointments WHERE status = 'pending'";
$pendingResult = $conn->query($pendingQuery);
$pendingApprovals = ($pendingResult) ? $pendingResult->fetch_assoc()['pending'] : 0;

// 3. Fetch Active Specialists Count
$doctorsQuery = "SELECT COUNT(*) as total_docs FROM doctors";
$doctorsResult = $conn->query($doctorsQuery);
$activeDoctors = ($doctorsResult) ? $doctorsResult->fetch_assoc()['total_docs'] : 0;

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
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card-metric {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            border: 1px solid #e2e8f0;
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .metric-details h3 {
            font-size: 1.75rem;
            margin: 0;
            color: #1e293b;
            font-weight: 700;
        }

        .metric-details p {
            margin: 4px 0 0 0;
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-confirm {
            background-color: #22c55e;
            color: white;
        }

        .btn-confirm:hover { background-color: #16a34a; }
        .btn-cancel {
            background-color: #ef4444;
            color: white;
        }
        .btn-cancel:hover { background-color: #dc2626; }

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

        /* Form styling helper */
        .booking-box {
            background: white;
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #334155;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            box-sizing: border-box;
        }
        .btn-submit-booking {
            width: 100%;
            padding: 12px;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }

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
                <a href="#" class="nav-tab active" data-target="appointments-section">Master Schedule</a>
                <a href="#" class="nav-tab" data-target="doctors-section">Manage Doctors</a>
                <a href="#" class="nav-tab" data-target="patients-section">Manage Patients</a>
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

            <div class="metrics-grid">
                <div class="card-metric">
                    <div class="metric-icon" style="background: #e0f2fe; color: #0284c7;">📅</div>
                    <div class="metric-details">
                        <h3><?php echo $totalBookings; ?></h3>
                        <p>Total Bookings</p>
                    </div>
                </div>

                <div class="card-metric">
                    <div class="metric-icon" style="background: #fef3c7; color: #d97706;">⏳</div>
                    <div class="metric-details">
                        <h3 id="stat-pending"><?php echo $pendingApprovals; ?></h3>
                        <p>Pending Approvals</p>
                    </div>
                </div>

                <div class="card-metric">
                    <div class="metric-icon" style="background: #dcfce7; color: #16a34a;">🩺</div>
                    <div class="metric-details">
                        <h3><?php echo $activeDoctors; ?></h3>
                        <p>Active Specialists</p>
                    </div>
                </div>
            </div>

            <div id="appointments-section" class="tab-content">
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                           <?php
                           if ($appointmentsResult && $appointmentsResult->num_rows > 0) {
                              while ($row = $appointmentsResult->fetch_assoc()) {
                                 $formattedTime = date("g:i A", strtotime($row['appointment_time']));
                                 $formattedDate = date("F j, Y", strtotime($row['appointment_date']));
                                 $appId = $row['appointment_id'];
            
                                 echo "<tr id='row-$appId'>";
                                 echo "<td>" . $appId . "</td>";
                                 echo "<td><strong>" . htmlspecialchars($row['patient_name']) . "</strong></td>";
                                 echo "<td>" . htmlspecialchars($row['patient_email']) . "</td>";
                                 echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
                                 echo "<td>" . $formattedDate . "</td>";
                                 echo "<td>" . $formattedTime . "</td>";
                                 echo "<td><span id='status-badge-$appId' class='status-badge status-" . $row['status'] . "'>" . $row['status'] . "</span></td>";
            
                                 echo "<td>";
                                 if ($row['status'] === 'pending') {
                                     echo "<div id='actions-$appId' style='display:flex; gap:8px;'>";
                                     echo "<button class='btn-action btn-confirm' onclick='updateStatus($appId, \"confirmed\")'>Confirm</button>";
                                     echo "<button class='btn-action btn-cancel' onclick='updateStatus($appId, \"cancelled\")'>Cancel</button>";
                                     echo "</div>";
                                 } else {
                                     echo "<span style='color:#94a3b8; font-size:0.85rem; font-style:italic;'>Processed</span>";
                                 }
                                 echo "</td>";
                                 echo "</tr>";
                              }
                           } else {
                               echo "<tr><td colspan='8' style='text-align:center; color:#64748b;'>No appointments scheduled at this time.</td></tr>";
                           }
                           ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="doctors-section" class="tab-content hidden-panel">
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start; margin-top: 20px;">
                    
                    <div class="booking-box">
                        <h3>Register New Specialist</h3>
                        <form action="handlers/add-doctor.php" method="POST">
                            <div class="form-group">
                                <label>Doctor Full Name</label>
                                <input type="text" name="name" required placeholder="e.g. Dr. Jane Phiri">
                            </div>
                            <div class="form-group">
                                <label>Medical Specialization</label>
                                <input type="text" name="specialization" required placeholder="e.g. Cardiologist">
                            </div>
                            <div class="form-group">
                                <label>Professional Biography Summary</label>
                                <textarea name="bio" rows="4" placeholder="Brief summary of experience..." style="width:100%; padding:10px; border-radius:6px; border:1px solid #cbd5e1; font-family:inherit; box-sizing:border-box;"></textarea>
                            </div>
                            <button type="submit" class="btn-submit-booking" style="background:#10b981; margin-top:10px;">Add Doctor</button>
                        </form>
                    </div>

                    <div class="table-container" style="margin-top:0;">
                        <h3>Registered Clinic Specialists</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Doctor Name</th>
                                    <th>Specialization</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $docFetch = $conn->query("SELECT id, name, specialization FROM doctors ORDER BY id DESC");
                                if ($docFetch && $docFetch->num_rows > 0) {
                                    while ($dRow = $docFetch->fetch_assoc()) {
                                        $dId = $dRow['id'];
                                        echo "<tr id='doc-row-$dId'>";
                                        echo "<td>" . $dId . "</td>";
                                        echo "<td><strong>" . htmlspecialchars($dRow['name']) . "</strong></td>";
                                        echo "<td><span style='background:#e0f2fe; color:#0369a1; padding:4px 10px; border-radius:20px; font-size:0.8rem; font-weight:600;'>" . htmlspecialchars($dRow['specialization']) . "</span></td>";
                                        echo "<td><button onclick='removeDoctor($dId)' class='btn-action btn-cancel' style='padding:4px 8px;'>Remove</button></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' style='text-align:center; color:#64748b;'>No doctors registered in the system database.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="patients-section" class="tab-content hidden-panel">
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start; margin-top: 20px;">
                    
                    <div class="booking-box">
                        <h3>Register New Patient</h3>
                        <form action="handlers/add-patient.php" method="POST">
                            <div class="form-group">
                                <label>Full Patient Name</label>
                                <input type="text" name="name" required placeholder="Full Name">
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" required placeholder="email@domain.com">
                            </div>
                            <div class="form-group">
                                <label>Temporary Account Password</label>
                                <input type="password" name="password" required placeholder="••••••••">
                            </div>
                            <button type="submit" class="btn-submit-booking" style="background:#10b981; margin-top:10px;">Create Account</button>
                        </form>
                    </div>

                    <div class="table-container" style="margin-top:0;">
                        <h3>Registered Patient Database</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Patient Name</th>
                                    <th>Email Address</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $patFetch = $conn->query("SELECT id, name, email FROM users WHERE role = 'patient' ORDER BY id DESC");
                                if ($patFetch && $patFetch->num_rows > 0) {
                                    while ($pRow = $patFetch->fetch_assoc()) {
                                        $pId = $pRow['id'];
                                        echo "<tr id='pat-row-$pId'>";
                                        echo "<td>" . $pId . "</td>";
                                        echo "<td><strong>" . htmlspecialchars($pRow['name']) . "</strong></td>";
                                        echo "<td>" . htmlspecialchars($pRow['email']) . "</td>";
                                        echo "<td><button onclick='removePatient($pId)' class='btn-action btn-cancel' style='padding:4px 8px;'>Remove</button></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' style='text-align:center; color:#64748b;'>No patients registered yet.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Tab switching engine
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

    // AJAX database status updater
    function updateStatus(appointmentId, newStatus) {
        const formData = new FormData();
        formData.append('appointment_id', appointmentId);
        formData.append('status', newStatus);

        fetch('handlers/update-status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById(`status-badge-${appointmentId}`);
                badge.innerText = newStatus;
                badge.className = `status-badge status-${newStatus}`;
                
                const pendingStatCard = document.getElementById('stat-pending');
                if (pendingStatCard) {
                    let currentPendingCount = parseInt(pendingStatCard.innerText);
                    if (currentPendingCount > 0) {
                        pendingStatCard.innerText = currentPendingCount - 1;
                    }
                }

                const actionsContainer = document.getElementById(`actions-${appointmentId}`);
                if (actionsContainer) {
                    actionsContainer.parentElement.innerHTML = "<span style='color:#94a3b8; font-size:0.85rem; font-style:italic;'>Processed</span>";
                }
            } else {
                alert('Error updating status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            alert('An unexpected error occurred connection-wise.');
        });
    }

    function removeDoctor(doctorId) {
        if (!confirm("Are you sure you want to permanently remove this specialist profile from clinical rosters?")) return;

        const fd = new FormData();
        fd.append('doctor_id', doctorId);

        fetch('handlers/delete-doctor.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById(`doc-row-${doctorId}`).remove();
            } else {
                alert("Execution Denied: " + data.message);
            }
        });
    }

    function removePatient(patientId) {
        if (!confirm("Are you sure you want to completely drop this patient portfolio and wipe credentials?")) return;

        const fd = new FormData();
        fd.append('patient_id', patientId);

        fetch('handlers/delete-patient.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById(`pat-row-${patientId}`).remove();
            } else {
                alert("Execution Denied: " + data.message);
            }
        });
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>