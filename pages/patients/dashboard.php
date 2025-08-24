<?php
/*****************************************************************
 * pages/patients/dashboard.php
 * Patient Dashboard - Main portal for patient access
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

// Ensure only patients can access this
if (!isset($_SESSION['role']) || (int)$_SESSION['role'] !== 4) {
    flash('Access denied. Patients only.', 'error');
    header('Location: ../../auth/patient_portal.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'] ?? 'Patient';

// Get patient information
$patient_query = $conn->prepare("
    SELECT first_name, last_name, email, phone, dob 
    FROM patient 
    WHERE email = ?
");
$patient_query->bind_param("s", $_SESSION['email']);
$patient_query->execute();
$result = $patient_query->get_result();
$patient = $result->fetch_assoc();

if (!$patient) {
    // Create basic patient record if it doesn't exist
    $patient = [
        'first_name' => 'Patient',
        'last_name' => '',
        'email' => $_SESSION['email'],
        'phone' => '',
        'dob' => ''
    ];
}

// Get recent appointments
$appointments_query = $conn->prepare("\n    SELECT a.appointment_id, a.appointment_dt, a.status, a.notes,\n           u.email AS dentist_email\n    FROM appointment a\n    LEFT JOIN dentist d ON a.dentist_id = d.dentist_id\n    LEFT JOIN usertbl u ON d.user_id = u.user_id\n    LEFT JOIN patient p ON a.patient_id = p.patient_id\n    WHERE p.email = ?\n    ORDER BY a.appointment_dt DESC\n    LIMIT 5\n");
$appointments_query->bind_param("s", $_SESSION['email']);
$appointments_query->execute();
$appointments_result = $appointments_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - DentoSys</title>
    <link rel="icon" type="image/png" href="../../assets/images/DentoSys_Logo.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .content {
            padding: 2rem;
        }

        .welcome-section {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .welcome-section h2 {
            color: #1976d2;
            margin-bottom: 1rem;
            font-size: 2rem;
        }

        .patient-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            border-left: 4px solid #4facfe;
        }

        .info-card h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-card p {
            color: #34495e;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .action-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            border-color: #4facfe;
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(79, 172, 254, 0.2);
        }

        .action-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        .action-card h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }

        .action-card p {
            color: #7f8c8d;
            line-height: 1.5;
        }

        .recent-appointments {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
        }

        .recent-appointments h3 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .appointment-item {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #4facfe;
        }

        .appointment-date {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .appointment-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-approved { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-scheduled { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-complete { background: #d1ecf1; color: #0c5460; }

        .logout-section {
            text-align: center;
            margin: 2rem 0;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
        }

        .flash-message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .flash-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .flash-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .flash-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .patient-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <h1>🦷 Patient Portal</h1>
            <p>Welcome to your personal dental care dashboard</p>
        </div>

        <div class="content">
            <?php
            // Display flash messages
            if (!empty($_SESSION['flash'])) {
                $flash_type = $_SESSION['flash_type'] ?? 'info';
                echo "<div class='flash-message flash-{$flash_type}'>{$_SESSION['flash']}</div>";
                unset($_SESSION['flash'], $_SESSION['flash_type']);
            }
            ?>

            <div class="welcome-section">
                <h2>Welcome back, <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>!</h2>
                <p>Manage your dental care and stay connected with our practice</p>
            </div>

            <div class="patient-info">
                <div class="info-card">
                    <h3>Full Name</h3>
                    <p><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
                </div>
                <div class="info-card">
                    <h3>Email</h3>
                    <p><?php echo htmlspecialchars($patient['email']); ?></p>
                </div>
                <div class="info-card">
                    <h3>Phone</h3>
                    <p><?php echo htmlspecialchars($patient['phone'] ?: 'Not provided'); ?></p>
                </div>
                <div class="info-card">
                    <h3>Date of Birth</h3>
                    <p><?php echo $patient['dob'] ? date('F j, Y', strtotime($patient['dob'])) : 'Not provided'; ?></p>
                </div>
            </div>

            <div class="quick-actions">
                <a href="book_appointment.php" class="action-card">
                    <span class="action-icon">📅</span>
                    <h3>Book Appointment</h3>
                    <p>Schedule your next dental visit with our team</p>
                </a>

                <a href="my_appointments.php" class="action-card">
                    <span class="action-icon">🗓️</span>
                    <h3>My Appointments</h3>
                    <p>View and manage your upcoming appointments</p>
                </a>

                <a href="my_records.php" class="action-card">
                    <span class="action-icon">📋</span>
                    <h3>Medical Records</h3>
                    <p>Access your treatment history and records</p>
                </a>

                <a href="my_prescriptions.php" class="action-card">
                    <span class="action-icon">💊</span>
                    <h3>Prescriptions</h3>
                    <p>View your current and past prescriptions</p>
                </a>

                <a href="my_billing.php" class="action-card">
                    <span class="action-icon">💳</span>
                    <h3>Billing & Payments</h3>
                    <p>Manage your invoices and payment methods</p>
                </a>

                <a href="my_profile.php" class="action-card">
                    <span class="action-icon">👤</span>
                    <h3>My Profile</h3>
                    <p>Update your personal information and preferences</p>
                </a>
            </div>

            <div class="recent-appointments">
                <h3>Recent Appointments</h3>
                <?php if ($appointments_result && $appointments_result->num_rows > 0): ?>
                    <?php while ($appointment = $appointments_result->fetch_assoc()): ?>
                        <div class="appointment-item">
                            <div class="appointment-date">
                                <?php echo date('F j, Y \a\t g:i A', strtotime($appointment['appointment_dt'])); ?>
                            </div>
                            <div>
                                <strong>Dr. <?php echo htmlspecialchars($appointment['dentist_email'] ?: 'Dentist'); ?></strong>
                                <span class="appointment-status status-<?php echo strtolower($appointment['status']); ?>">
                                    <?php echo htmlspecialchars($appointment['status']); ?>
                                </span>
                            </div>
                            <?php if ($appointment['notes']): ?>
                                <p style="margin-top: 0.5rem; color: #6c757d;">
                                    <?php echo htmlspecialchars($appointment['notes']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #6c757d; padding: 2rem;">
                        No appointments found. <a href="book_appointment.php" style="color: #4facfe;">Book your first appointment</a>
                    </p>
                <?php endif; ?>
            </div>

            <div class="logout-section">
                <a href="../../auth/logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </div>
    </div>
</body>
</html>
