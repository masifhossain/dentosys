<?php
/*****************************************************************
 * pages/dashboard_enhanced.php
 * ---------------------------------------------------------------
 * Enhanced Clinic Dashboard with Figma Design
 *  • Modern KPI cards with icons and gradients
 *  • Interactive charts and widgets
 *  • Qu                          <a href="/dentosys/pages/records/add_note.php" class="btn-secondary-enhanced">
                            📝 Add Clinical Note
                        </a>                   <a href="/dentosys/pages/records/add_note.php" class="btn-secondary-enhanced">
                            📝 Add Clinical Note
                        </a> actions and recent activity
 *  • Responsive design with grid layout
 ***********************************************                    </div>
                </div>
            </div>
        </div>
    </div>*********/
require_once dirname(__DIR__) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

// Check if user is a patient - redirect them to patient portal
if (isset($_SESSION['role']) && (int)$_SESSION['role'] === 4) {
    flash('Patients should use the patient portal.');
    redirect('patients/dashboard.php');
}

// Helper function to get user display name
function get_user_display_name() {
    return $_SESSION['first_name'] ?? 'User';
}

/* --------------------------------------------------------------
 * 1. Enhanced KPI queries with role-based filtering
 * ------------------------------------------------------------ */
$today = date('Y-m-d');
$thisMonth = date('Y-m');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$lastMonth = date('Y-m', strtotime('-1 month'));

// Build filtering conditions for dentists
$dentist_filter = '';
$patient_filter = '';
if (is_dentist()) {
    $current_dentist_id = get_current_dentist_id();
    if ($current_dentist_id) {
        $dentist_filter = "AND a.dentist_id = $current_dentist_id";
        
        // Get patient IDs for this dentist
        $patient_ids = get_dentist_patient_ids();
        if (!empty($patient_ids)) {
            $patient_ids_str = implode(',', $patient_ids);
            $patient_filter = "AND patient_id IN ($patient_ids_str)";
        } else {
            $patient_filter = "AND 1 = 0"; // No patients
        }
    } else {
        $dentist_filter = "AND 1 = 0"; // Show no data if dentist not found
        $patient_filter = "AND 1 = 0";
    }
}

// Today's appointments
$apptsToday = $conn->query(
    "SELECT COUNT(*) AS c
     FROM appointment a
     WHERE DATE(a.appointment_dt) = '$today'
       AND a.status IN ('Scheduled','Pending','Approved')
       $dentist_filter"
)->fetch_assoc()['c'] ?? 0;

// Yesterday's appointments for comparison
$apptsYesterday = $conn->query(
    "SELECT COUNT(*) AS c
     FROM appointment a
     WHERE DATE(a.appointment_dt) = '$yesterday'
       AND a.status IN ('Scheduled','Pending','Approved')
       $dentist_filter"
)->fetch_assoc()['c'] ?? 0;

// Total patients (role-based)
if (is_dentist()) {
    $totalPatients = !empty($patient_ids) ? count($patient_ids) : 0;
} else {
    $totalPatients = $conn->query(
        "SELECT COUNT(*) AS c FROM patient"
    )->fetch_assoc()['c'] ?? 0;
}

// New patients this month
$newPatientsMonth = 0;
$checkColumn = $conn->query("SHOW COLUMNS FROM patient LIKE 'created_at'");
if ($checkColumn && $checkColumn->num_rows > 0) {
    if (is_dentist()) {
        if (!empty($patient_ids)) {
            $patient_ids_str = implode(',', $patient_ids);
            $newPatientsMonth = $conn->query(
                "SELECT COUNT(*) AS c FROM patient 
                 WHERE DATE_FORMAT(created_at, '%Y-%m') = '$thisMonth' 
                 AND patient_id IN ($patient_ids_str)"
            )->fetch_assoc()['c'] ?? 0;
        }
    } else {
        $newPatientsMonth = $conn->query(
            "SELECT COUNT(*) AS c FROM patient WHERE DATE_FORMAT(created_at, '%Y-%m') = '$thisMonth'"
        )->fetch_assoc()['c'] ?? 0;
    }
} else {
    // Fallback if created_at column doesn't exist
    if (is_dentist()) {
        $newPatientsMonth = $totalPatients; // Use total patients as fallback
    } else {
        $newPatientsMonth = $conn->query(
            "SELECT COUNT(*) AS c FROM patient WHERE patient_id > 0"
        )->fetch_assoc()['c'] ?? 0;
    }
}

// Last month's new patients for comparison
$newPatientsLastMonth = 0;
if ($checkColumn && $checkColumn->num_rows > 0) {
    if (is_dentist()) {
        if (!empty($patient_ids)) {
            $patient_ids_str = implode(',', $patient_ids);
            $newPatientsLastMonth = $conn->query(
                "SELECT COUNT(*) AS c FROM patient 
                 WHERE DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth' 
                 AND patient_id IN ($patient_ids_str)"
            )->fetch_assoc()['c'] ?? 0;
        }
    } else {
        $newPatientsLastMonth = $conn->query(
            "SELECT COUNT(*) AS c FROM patient WHERE DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth'"
        )->fetch_assoc()['c'] ?? 0;
    }
}

// Outstanding invoices (role-based)
if (is_dentist()) {
    if (!empty($patient_ids)) {
        $patient_ids_str = implode(',', $patient_ids);
        $outstanding = $conn->query(
            "SELECT COUNT(*) AS cnt,
                    COALESCE(SUM(total_amount),0) AS amt
             FROM Invoice WHERE status = 'Unpaid' AND patient_id IN ($patient_ids_str)"
        )->fetch_assoc();
    } else {
        $outstanding = ['cnt' => 0, 'amt' => 0];
    }
} else {
    $outstanding = $conn->query(
        "SELECT COUNT(*) AS cnt,
                COALESCE(SUM(total_amount),0) AS amt
         FROM Invoice WHERE status = 'Unpaid'"
    )->fetch_assoc();
}

// Monthly revenue (role-based)
$monthlyRevenue = 0;
$checkInvoiceColumn = $conn->query("SHOW COLUMNS FROM Invoice LIKE 'created_at'");
if ($checkInvoiceColumn && $checkInvoiceColumn->num_rows > 0) {
    if (is_dentist()) {
        if (!empty($patient_ids)) {
            $patient_ids_str = implode(',', $patient_ids);
            $monthlyRevenue = $conn->query(
                "SELECT COALESCE(SUM(total_amount),0) AS amt
                 FROM Invoice 
                 WHERE status = 'Paid' 
                 AND DATE_FORMAT(created_at, '%Y-%m') = '$thisMonth'
                 AND patient_id IN ($patient_ids_str)"
            )->fetch_assoc()['amt'] ?? 0;
        }
    } else {
        $monthlyRevenue = $conn->query(
            "SELECT COALESCE(SUM(total_amount),0) AS amt
             FROM Invoice 
             WHERE status = 'Paid' 
             AND DATE_FORMAT(created_at, '%Y-%m') = '$thisMonth'"
        )->fetch_assoc()['amt'] ?? 0;
    }
} else {
    // Fallback - get all paid invoices
    if (is_dentist()) {
        if (!empty($patient_ids)) {
            $patient_ids_str = implode(',', $patient_ids);
            $monthlyRevenue = $conn->query(
                "SELECT COALESCE(SUM(total_amount),0) AS amt
                 FROM Invoice 
                 WHERE status = 'Paid' AND patient_id IN ($patient_ids_str)"
            )->fetch_assoc()['amt'] ?? 0;
        }
    } else {
        $monthlyRevenue = $conn->query(
            "SELECT COALESCE(SUM(total_amount),0) AS amt
             FROM Invoice 
             WHERE status = 'Paid'"
        )->fetch_assoc()['amt'] ?? 0;
    }
}

// Last month's revenue for comparison (role-based)
$lastMonthRevenue = 0;
if ($checkInvoiceColumn && $checkInvoiceColumn->num_rows > 0) {
    if (is_dentist()) {
        if (!empty($patient_ids)) {
            $patient_ids_str = implode(',', $patient_ids);
            $lastMonthRevenue = $conn->query(
                "SELECT COALESCE(SUM(total_amount),0) AS amt
                 FROM Invoice 
                 WHERE status = 'Paid' 
                 AND DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth'
                 AND patient_id IN ($patient_ids_str)"
            )->fetch_assoc()['amt'] ?? 0;
        }
    } else {
        $lastMonthRevenue = $conn->query(
            "SELECT COALESCE(SUM(total_amount),0) AS amt
             FROM Invoice 
             WHERE status = 'Paid' 
             AND DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth'"
        )->fetch_assoc()['amt'] ?? 0;
    }
}

// Unread feedback
$feedbackNew = $conn->query(
    "SELECT COUNT(*) AS c FROM Feedback WHERE status = 'New'"
)->fetch_assoc()['c'] ?? 0;

/* --------------------------------------------------------------
 * 2. Recent activity queries with role-based filtering
 * ------------------------------------------------------------ */
// Next appointments today
$nextAppts = $conn->query(
    "SELECT DATE_FORMAT(a.appointment_dt,'%H:%i') AS atime,
            CONCAT(p.first_name,' ',p.last_name) AS patient,
            a.status,
            COALESCE(ut.email, 'Unassigned') as dentist_name
     FROM appointment a
     JOIN patient p ON p.patient_id = a.patient_id
     LEFT JOIN dentist d ON d.dentist_id = a.dentist_id
     LEFT JOIN UserTbl ut ON ut.user_id = d.user_id
     WHERE DATE(a.appointment_dt) = '$today'
     $dentist_filter
     ORDER BY a.appointment_dt
     LIMIT 5"
);

// Recent patients (role-based)
if (is_dentist()) {
    if (!empty($patient_ids)) {
        $patient_ids_str = implode(',', $patient_ids);
        $recentPatients = $conn->query(
            "SELECT CONCAT(first_name,' ',last_name) AS name,
                    'Recently added' AS joined_date,
                    phone
             FROM patient 
             WHERE patient_id IN ($patient_ids_str)
             ORDER BY patient_id DESC 
             LIMIT 5"
        );
    } else {
        $recentPatients = null; // No patients for this dentist
    }
} else {
    $recentPatients = $conn->query(
        "SELECT CONCAT(first_name,' ',last_name) AS name,
                'Recently added' AS joined_date,
                phone
         FROM patient 
         ORDER BY patient_id DESC 
         LIMIT 5"
    );
}

// Calculate growth percentages
function calculateGrowth($current, $previous) {
    if ($previous == 0) return $current > 0 ? 100 : 0;
    return round((($current - $previous) / $previous) * 100);
}

$apptGrowth = calculateGrowth($apptsToday, $apptsYesterday);
$patientGrowth = calculateGrowth($newPatientsMonth, $newPatientsLastMonth);
$revenueGrowth = calculateGrowth($monthlyRevenue, $lastMonthRevenue);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>

<main class="main-content-enhanced">
    <!-- Header Section -->
    <div class="content-header">
        <h1>📊 Dashboard</h1>
        <div class="breadcrumb">
            Welcome back, <?= get_user_display_name(); ?> • <?= date('l, F j, Y'); ?>
        </div>
    </div>

    <div class="content-body">
        <!-- Quick Actions Section -->
        <div class="dashboard-section">
            <div class="card-enhanced" style="margin-bottom: 20px;">
                <div class="card-header">
                    <h3>🚀 Quick Actions</h3>
                </div>
                <div class="card-body" style="padding: 24px;">
                    <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                        <?php if (!is_dentist()): ?>
                        <a href="/dentosys/pages/appointments/book.php" class="btn-primary-enhanced">
                            📅 Book Appointment
                        </a>
                        <a href="/dentosys/pages/patients/add.php" class="btn-secondary-enhanced">
                            👤 Add Patient
                        </a>
                        <a href="/dentosys/pages/billing/create_invoice.php" class="btn-secondary-enhanced">
                            💰 Create Invoice
                        </a>
                        <?php else: ?>
                        <a href="/dentosys/pages/records/add_prescription.php" class="btn-primary-enhanced">
                            &#128138; New Prescription
                        </a>
                        <a href="/dentosys/pages/records/add_note.php" class="btn-secondary-enhanced">
                            &#128221; Add Clinical Note
                        </a>
                        <a href="/dentosys/pages/appointments/calendar.php" class="btn-secondary-enhanced">
                            &#128197; View My Schedule
                        </a>
                        <a href="/dentosys/pages/patients/list.php" class="btn-secondary-enhanced">
                            &#128101; My Patients
                        </a>
                        <?php endif; ?>
                        <?php if (is_admin()): ?>
                        <a href="/dentosys/pages/records/add_prescription.php" class="btn-secondary-enhanced">
                            � New Prescription
                        </a>
                        <?php endif; ?>
                        <?php if (is_dentist()): ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Stats Section -->
        <div class="dashboard-section">
            <div class="card-enhanced" style="margin-bottom: 20px;">
                <div class="card-header">
                    <h3>📊 <?= is_dentist() ? 'My Performance Indicators' : 'Key Performance Indicators' ?></h3>
                </div>
                <div class="card-body" style="padding: 24px;">
                    <div class="grid grid-cols-4 gap-4">
                        <!-- Today's Appointments -->
                        <div class="kpi-card-clean">
                            <div class="kpi-icon">📅</div>
                            <div class="kpi-value"><?= $apptsToday; ?></div>
                            <div class="kpi-label"><?= is_dentist() ? "Today's Appointments" : "Today's Appointments"; ?></div>
                            <?php if ($apptGrowth != 0): ?>
                                <div style="margin-top: 8px; font-size: 12px; color: <?= $apptGrowth > 0 ? '#10B981' : '#EF4444'; ?>;">
                                    <?= $apptGrowth > 0 ? '↗' : '↘'; ?> <?= abs($apptGrowth); ?>% vs yesterday
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Total Patients -->
                        <div class="kpi-card-clean">
                            <div class="kpi-icon">👥</div>
                            <div class="kpi-value"><?= $totalPatients; ?></div>
                            <div class="kpi-label"><?= is_dentist() ? "My Patients" : "Total Patients"; ?></div>
                            <div style="margin-top: 8px; font-size: 12px; color: #64748B;">
                                +<?= $newPatientsMonth; ?> this month
                            </div>
                        </div>

                        <!-- Monthly Revenue -->
                        <div class="kpi-card-clean">
                            <div class="kpi-icon">💰</div>
                            <div class="kpi-value">$<?= number_format($monthlyRevenue, 0); ?></div>
                            <div class="kpi-label"><?= is_dentist() ? "My Revenue" : "Monthly Revenue"; ?></div>
                            <?php if ($revenueGrowth != 0): ?>
                                <div style="margin-top: 8px; font-size: 12px; color: <?= $revenueGrowth > 0 ? '#10B981' : '#EF4444'; ?>;">
                                    <?= $revenueGrowth > 0 ? '↗' : '↘'; ?> <?= abs($revenueGrowth); ?>% vs last month
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Outstanding Invoices -->
                        <div class="kpi-card-clean">
                            <div class="kpi-icon">⚠️</div>
                            <div class="kpi-value"><?= $outstanding['cnt']; ?></div>
                            <div class="kpi-label">Outstanding Invoices</div>
                            <div style="margin-top: 8px; font-size: 12px; color: #F59E0B;">
                                $<?= number_format($outstanding['amt'], 0); ?> pending
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Section -->
        <div class="dashboard-section">
            <div class="card-enhanced" style="margin-bottom: 20px;">
                <div class="card-header">
                    <h3>📋 Today's Overview</h3>
                </div>
                <div class="card-body" style="padding: 24px;">
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Today's Schedule -->
                        <div class="inner-card">
                            <div class="inner-card-header">
                                <h4>📅 Today's Schedule</h4>
                            </div>
                            <div class="inner-card-body">
                                <?php if ($nextAppts->num_rows === 0): ?>
                                    <div style="text-align: center; padding: 40px 20px; color: #64748B;">
                                        <div style="font-size: 48px; margin-bottom: 16px;">🗓️</div>
                                        <p>No appointments scheduled for today</p>
                                        <?php if (!is_dentist()): ?>
                                            <a href="/dentosys/pages/appointments/book.php" class="btn-primary-enhanced" style="margin-top: 16px;">
                                                Book First Appointment
                                            </a>
                                        <?php endif; ?>
                                        <?php if (is_dentist()): ?>
                                            <p style="font-size: 14px; margin-top: 8px; color: #64748B;">Your schedule is clear today. Time to catch up on clinical notes!</p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="space-y: 16px;">
                                        <?php while ($appt = $nextAppts->fetch_assoc()): ?>
                                            <div style="display: flex; align-items: center; padding: 16px; background: #F8FAFC; border-radius: 8px; margin-bottom: 12px;">
                                                <div style="font-weight: 600; color: #0066CC; margin-right: 16px; min-width: 60px;">
                                                    <?= $appt['atime']; ?>
                                                </div>
                                                <div style="flex: 1;">
                                                    <div style="font-weight: 500; color: #1E293B;">
                                                        <?= htmlspecialchars($appt['patient']); ?>
                                                    </div>
                                                    <?php if ($appt['dentist_name']): ?>
                                                        <div style="font-size: 12px; color: #64748B;">
                                                            Dr. <?= htmlspecialchars($appt['dentist_name']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="badge-enhanced badge-<?= $appt['status'] === 'Approved' ? 'success' : 'warning'; ?>-enhanced">
                                                    <?= $appt['status']; ?>
                                                </span>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                    <div style="text-align: center; margin-top: 20px;">
                                        <a href="/dentosys/pages/appointments/calendar.php" class="btn-secondary-enhanced">
                                            View Full Calendar
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Recent Patients -->
                        <div class="inner-card">
                            <div class="inner-card-header">
                                <h4>👥 Recent Patients</h4>
                            </div>
                            <div class="inner-card-body">
                                <?php if (!$recentPatients || $recentPatients->num_rows === 0): ?>
                                    <div style="text-align: center; padding: 40px 20px; color: #64748B;">
                                        <div style="font-size: 48px; margin-bottom: 16px;">👤</div>
                                        <?php if (is_dentist()): ?>
                                            <p>No patients assigned yet</p>
                                            <p style="font-size: 14px; margin-top: 8px;">Patients will appear here once you have appointments scheduled with them.</p>
                                        <?php else: ?>
                                            <p>No patients registered yet</p>
                                            <a href="/dentosys/pages/patients/add.php" class="btn-primary-enhanced" style="margin-top: 16px;">
                                                Add First Patient
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="space-y: 12px;">
                                        <?php while ($patient = $recentPatients->fetch_assoc()): ?>
                                            <div style="display: flex; align-items: center; padding: 12px; border-bottom: 1px solid #F3F4F6;">
                                                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #0066CC, #004A99); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; margin-right: 12px;">
                                                    <?= strtoupper(substr($patient['name'], 0, 1)); ?>
                                                </div>
                                                <div style="flex: 1;">
                                                    <div style="font-weight: 500; color: #1E293B;">
                                                        <?= htmlspecialchars($patient['name']); ?>
                                                    </div>
                                                    <div style="font-size: 12px; color: #64748B;">
                                                        Joined <?= $patient['joined_date']; ?>
                                                    </div>
                                                </div>
                                                <div style="font-size: 12px; color: #64748B;">
                                                    <?= htmlspecialchars($patient['phone']); ?>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                    <div style="text-align: center; margin-top: 20px;">
                                        <a href="/dentosys/pages/patients/list.php" class="btn-secondary-enhanced">
                                            <?= is_dentist() ? 'View My Patients' : 'View All Patients' ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats Row -->
        <div class="grid grid-cols-3 gap-4 dashboard-bottom-row" style="margin-top: 30px;">
            <!-- Monthly Performance -->
            <div class="card-enhanced with-bottom-line">
                <div class="card-header">
                    <h3>📈 Monthly Performance</h3>
                </div>
                <div class="card-body">
                    <div style="margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-size: 14px; color: #64748B;">New Patients</span>
                            <span style="font-weight: 600;"><?= $newPatientsMonth; ?></span>
                        </div>
                        <div style="width: 100%; height: 6px; background: #F1F5F9; border-radius: 3px;">
                            <div style="width: <?= min(100, ($newPatientsMonth / max(1, $newPatientsLastMonth)) * 100); ?>%; height: 100%; background: #10B981; border-radius: 3px;"></div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-size: 14px; color: #64748B;">Revenue</span>
                            <span style="font-weight: 600;">$<?= number_format($monthlyRevenue, 0); ?></span>
                        </div>
                        <div style="width: 100%; height: 6px; background: #F1F5F9; border-radius: 3px;">
                            <div style="width: <?= min(100, ($monthlyRevenue / max(1, $lastMonthRevenue)) * 100); ?>%; height: 100%; background: #0066CC; border-radius: 3px;"></div>
                        </div>
                    </div>

                    <?php if ($feedbackNew > 0): ?>
                        <div style="margin-top: 20px; padding: 12px; background: #FEF3C7; border-radius: 8px; border-left: 4px solid #F59E0B;">
                            <div style="font-size: 14px; font-weight: 500; color: #92400E;">
                                💬 <?= $feedbackNew; ?> new feedback message<?= $feedbackNew > 1 ? 's' : ''; ?>
                            </div>
                            <a href="/dentosys/pages/communications/feedback.php" style="font-size: 12px; color: #D97706; text-decoration: none;">
                                Review feedback →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- System Status (Admins only) -->
            <?php if (is_admin()): ?>
            <div class="card-enhanced with-bottom-line">
                <div class="card-header">
                    <h3>⚙️ System Status</h3>
                </div>
                <div class="card-body">
                    <div style="space-y: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
                            <span style="font-size: 14px; color: #64748B;">Database</span>
                            <span class="badge-enhanced badge-success-enhanced">●  Online</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
                            <span style="font-size: 14px; color: #64748B;">Backup</span>
                            <span class="badge-enhanced badge-success-enhanced">●  Current</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
                            <span style="font-size: 14px; color: #64748B;">Integrations</span>
                            <span class="badge-enhanced badge-warning-enhanced">●  Limited</span>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px; text-align: center;">
                        <a href="/dentosys/pages/settings/integrations.php" class="btn-secondary-enhanced">
                            Manage Settings
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Links (hidden for Receptionist) -->
            <?php if (!is_receptionist()): ?>
            <div class="card-enhanced with-bottom-line">
                <div class="card-header">
                    <h3>🚀 Quick Links</h3>
                </div>
                <div class="card-body">
                    <div style="space-y: 8px;">
                        <a href="/dentosys/pages/reports/operational.php" style="display: flex; align-items: center; padding: 8px; text-decoration: none; color: #1E293B; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='transparent'">
                            <span style="margin-right: 12px;">📊</span>
                            <span style="font-size: 14px;">View Reports</span>
                        </a>
                        <a href="/dentosys/pages/records/prescriptions.php" style="display: flex; align-items: center; padding: 8px; text-decoration: none; color: #1E293B; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='transparent'">
                            <span style="margin-right: 12px;">💊</span>
                            <span style="font-size: 14px;">Prescriptions</span>
                        </a>
                        <?php if (!is_dentist()): ?>
                        <a href="/dentosys/pages/billing/insurance.php" style="display: flex; align-items: center; padding: 8px; text-decoration: none; color: #1E293B; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='transparent'">
                            <span style="margin-right: 12px;">🏥</span>
                            <span style="font-size: 14px;">Insurance Claims</span>
                        </a>
                        <?php endif; ?>
                        <?php if (is_admin()): ?>
                        <a href="/dentosys/pages/help.php" style="display: flex; align-items: center; padding: 8px; text-decoration: none; color: #1E293B; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='transparent'">
                            <span style="margin-right: 12px;">❓</span>
                            <span style="font-size: 14px;">Help & Support</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
/* Enhanced Dashboard Specific Styles */
main.main-content-enhanced {
    flex: 1;
    padding: 0;
    background: #f8fafc;
    margin: 0;
    overflow-x: auto;
}

/* Dashboard specific overrides - Remove ALL borders */
main.main-content-enhanced .dashboard-section {
    border: none !important;
    outline: none !important;
}

main.main-content-enhanced .card-enhanced {
    border: none !important;
    outline: none !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
}

main.main-content-enhanced .card-header {
    border: none !important;
    border-bottom: none !important;
    outline: none !important;
}

main.main-content-enhanced .card-body {
    border: none !important;
    outline: none !important;
}

/* Remove any pseudo-element borders */
main.main-content-enhanced .dashboard-section::before,
main.main-content-enhanced .dashboard-section::after,
main.main-content-enhanced .card-enhanced::before,
main.main-content-enhanced .card-enhanced::after {
    display: none !important;
    content: none !important;
}

/* Completely new KPI card design */
.kpi-card-clean {
    background: white;
    border-radius: 16px;
    padding: 28px 24px;
    text-align: center;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
    margin: 0;
    border: 0;
    outline: 0;
}

.kpi-card-clean:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.kpi-icon {
    font-size: 2.5rem;
    margin-bottom: 16px;
    display: block;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}

.kpi-value {
    font-size: 3rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
    line-height: 1;
}

.kpi-label {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Override default main padding for dashboard */
body:has(main.main-content-enhanced) main {
    padding: 0;
}

/* Make sure footer is positioned correctly */
body:has(main.main-content-enhanced) .site-footer {
    left: 200px;
    width: calc(100% - 200px);
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .grid-cols-4 { grid-template-columns: repeat(2, 1fr); }
    .grid-cols-3 { grid-template-columns: repeat(1, 1fr); }
    .grid-cols-2 { grid-template-columns: repeat(1, 1fr); }
}

@media (max-width: 768px) {
    .stats-card {
        padding: 16px;
    }
    
    .stats-card .stats-value {
        font-size: 24px;
    }
    
    .content-header,
    .content-body {
        padding: 16px;
    }
    
    /* Hide sidebar on mobile and adjust layout */
    body:has(main.main-content-enhanced) .sidebar {
        display: none;
    }
    
    body:has(main.main-content-enhanced) .site-footer {
        left: 0;
        width: 100%;
    }
}

/* Hover animations */
.stats-card:hover .stats-icon {
    transform: scale(1.1);
    transition: transform 0.2s ease;
}

.btn-primary-enhanced:hover,
.btn-secondary-enhanced:hover {
    transform: translateY(-1px);
    transition: transform 0.2s ease;
}

/* Ensure bottom row of cards has breathing room above footer */
.dashboard-bottom-row { margin-bottom: 70px; }

/* Subtle bottom hairline to distinguish card edge when adjacent to white footer */
.card-enhanced.with-bottom-line { position: relative; }
.card-enhanced.with-bottom-line::after { content: ""; position: absolute; left:0; right:0; bottom:0; height:1px; background: linear-gradient(to right,#e2e8f0,#f1f5f9); pointer-events:none; }

@media (max-width: 768px) { .dashboard-bottom-row { margin-bottom: 90px; } }
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
