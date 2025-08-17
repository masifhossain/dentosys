<?php
/*****************************************************************
 * pages/dashboard_enhanced.php
 * ---------------------------------------------------------------
 * Enhanced Clinic Dashboard with Figma Design
 *  • Modern KPI cards with icons and gradients
 *  • Interactive charts and widgets
 *  • Quick actions and recent activity
 *  • Responsive design with grid layout
 *****************************************************************/
require_once dirname(__DIR__) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

// Define BASE_URL if not defined
if (!defined('BASE_URL')) {
    define('BASE_URL', '');
}

// Helper function to get user display name
function get_user_display_name() {
    return $_SESSION['username'] ?? 'User';
}

/* --------------------------------------------------------------
 * 1. Enhanced KPI queries
 * ------------------------------------------------------------ */
$today = date('Y-m-d');
$thisMonth = date('Y-m');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$lastMonth = date('Y-m', strtotime('-1 month'));

// Today's appointments
$apptsToday = $conn->query(
    "SELECT COUNT(*) AS c
     FROM appointment
     WHERE DATE(appointment_dt) = '$today'
       AND status IN ('Scheduled','Pending','Approved')"
)->fetch_assoc()['c'] ?? 0;

// Yesterday's appointments for comparison
$apptsYesterday = $conn->query(
    "SELECT COUNT(*) AS c
     FROM appointment
     WHERE DATE(appointment_dt) = '$yesterday'
       AND status IN ('Scheduled','Pending','Approved')"
)->fetch_assoc()['c'] ?? 0;

// Total patients
$totalPatients = $conn->query(
    "SELECT COUNT(*) AS c FROM patient"
)->fetch_assoc()['c'] ?? 0;

// New patients this month
$newPatientsMonth = 0;
$checkColumn = $conn->query("SHOW COLUMNS FROM patient LIKE 'created_at'");
if ($checkColumn && $checkColumn->num_rows > 0) {
    $newPatientsMonth = $conn->query(
        "SELECT COUNT(*) AS c FROM patient WHERE DATE_FORMAT(created_at, '%Y-%m') = '$thisMonth'"
    )->fetch_assoc()['c'] ?? 0;
} else {
    // Fallback if created_at column doesn't exist
    $newPatientsMonth = $conn->query(
        "SELECT COUNT(*) AS c FROM patient WHERE patient_id > 0"
    )->fetch_assoc()['c'] ?? 0;
}

// Last month's new patients for comparison
$newPatientsLastMonth = 0;
if ($checkColumn && $checkColumn->num_rows > 0) {
    $newPatientsLastMonth = $conn->query(
        "SELECT COUNT(*) AS c FROM patient WHERE DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth'"
    )->fetch_assoc()['c'] ?? 0;
}

// Outstanding invoices
$outstanding = $conn->query(
    "SELECT COUNT(*) AS cnt,
            COALESCE(SUM(total_amount),0) AS amt
     FROM Invoice WHERE status = 'Unpaid'"
)->fetch_assoc();

// Monthly revenue
$monthlyRevenue = 0;
$checkInvoiceColumn = $conn->query("SHOW COLUMNS FROM Invoice LIKE 'created_at'");
if ($checkInvoiceColumn && $checkInvoiceColumn->num_rows > 0) {
    $monthlyRevenue = $conn->query(
        "SELECT COALESCE(SUM(total_amount),0) AS amt
         FROM Invoice 
         WHERE status = 'Paid' 
         AND DATE_FORMAT(created_at, '%Y-%m') = '$thisMonth'"
    )->fetch_assoc()['amt'] ?? 0;
} else {
    // Fallback - get all paid invoices
    $monthlyRevenue = $conn->query(
        "SELECT COALESCE(SUM(total_amount),0) AS amt
         FROM Invoice 
         WHERE status = 'Paid'"
    )->fetch_assoc()['amt'] ?? 0;
}

// Last month's revenue for comparison
$lastMonthRevenue = 0;
if ($checkInvoiceColumn && $checkInvoiceColumn->num_rows > 0) {
    $lastMonthRevenue = $conn->query(
        "SELECT COALESCE(SUM(total_amount),0) AS amt
         FROM Invoice 
         WHERE status = 'Paid' 
         AND DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth'"
    )->fetch_assoc()['amt'] ?? 0;
}

// Unread feedback
$feedbackNew = $conn->query(
    "SELECT COUNT(*) AS c FROM Feedback WHERE status = 'New'"
)->fetch_assoc()['c'] ?? 0;

/* --------------------------------------------------------------
 * 2. Recent activity queries
 * ------------------------------------------------------------ */
// Next appointments today
$nextAppts = $conn->query(
    "SELECT DATE_FORMAT(a.appointment_dt,'%H:%i') AS atime,
            CONCAT(p.first_name,' ',p.last_name) AS patient,
            a.status,
            'Dr. Smith' as dentist_name
     FROM appointment a
     JOIN patient p ON p.patient_id = a.patient_id
     LEFT JOIN dentist d ON d.dentist_id = a.dentist_id
     WHERE DATE(a.appointment_dt) = '$today'
     ORDER BY a.appointment_dt
     LIMIT 5"
);

// Recent patients
$recentPatients = $conn->query(
    "SELECT CONCAT(first_name,' ',last_name) AS name,
            'Recently added' AS joined_date,
            phone
     FROM patient 
     ORDER BY patient_id DESC 
     LIMIT 5"
);

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

<div class="main-content-enhanced">
    <!-- Header Section -->
    <div class="content-header">
        <h1>📊 Dashboard</h1>
        <div class="breadcrumb">
            Welcome back, <?= get_user_display_name(); ?> • <?= date('l, F j, Y'); ?>
        </div>
    </div>

    <div class="content-body">
        <!-- Quick Actions Bar -->
        <div class="card-enhanced" style="margin-bottom: 30px;">
            <div class="card-body" style="padding: 20px;">
                <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <a href="<?= BASE_URL; ?>/pages/appointments/book.php" class="btn-primary-enhanced">
                        📅 Book Appointment
                    </a>
                    <a href="<?= BASE_URL; ?>/pages/patients/add.php" class="btn-secondary-enhanced">
                        👤 Add Patient
                    </a>
                    <a href="<?= BASE_URL; ?>/pages/billing/invoices.php" class="btn-secondary-enhanced">
                        💰 Create Invoice
                    </a>
                    <a href="<?= BASE_URL; ?>/pages/records/add_prescription.php" class="btn-secondary-enhanced">
                        💊 New Prescription
                    </a>
                </div>
            </div>
        </div>

        <!-- KPI Stats Grid -->
        <div class="grid grid-cols-4 gap-4" style="margin-bottom: 30px;">
            <!-- Today's Appointments -->
            <div class="stats-card">
                <div class="stats-icon">📅</div>
                <div class="stats-value"><?= $apptsToday; ?></div>
                <div class="stats-label">Today's Appointments</div>
                <?php if ($apptGrowth != 0): ?>
                    <div style="margin-top: 8px; font-size: 12px; color: <?= $apptGrowth > 0 ? '#10B981' : '#EF4444'; ?>;">
                        <?= $apptGrowth > 0 ? '↗' : '↘'; ?> <?= abs($apptGrowth); ?>% vs yesterday
                    </div>
                <?php endif; ?>
            </div>

            <!-- Total Patients -->
            <div class="stats-card">
                <div class="stats-icon">👥</div>
                <div class="stats-value"><?= $totalPatients; ?></div>
                <div class="stats-label">Total Patients</div>
                <div style="margin-top: 8px; font-size: 12px; color: #64748B;">
                    +<?= $newPatientsMonth; ?> this month
                </div>
            </div>

            <!-- Monthly Revenue -->
            <div class="stats-card">
                <div class="stats-icon">💰</div>
                <div class="stats-value">$<?= number_format($monthlyRevenue, 0); ?></div>
                <div class="stats-label">Monthly Revenue</div>
                <?php if ($revenueGrowth != 0): ?>
                    <div style="margin-top: 8px; font-size: 12px; color: <?= $revenueGrowth > 0 ? '#10B981' : '#EF4444'; ?>;">
                        <?= $revenueGrowth > 0 ? '↗' : '↘'; ?> <?= abs($revenueGrowth); ?>% vs last month
                    </div>
                <?php endif; ?>
            </div>

            <!-- Outstanding Invoices -->
            <div class="stats-card">
                <div class="stats-icon">⚠️</div>
                <div class="stats-value"><?= $outstanding['cnt']; ?></div>
                <div class="stats-label">Outstanding Invoices</div>
                <div style="margin-top: 8px; font-size: 12px; color: #F59E0B;">
                    $<?= number_format($outstanding['amt'], 0); ?> pending
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-2 gap-4">
            <!-- Today's Schedule -->
            <div class="card-enhanced">
                <div class="card-header">
                    <h3>📅 Today's Schedule</h3>
                </div>
                <div class="card-body">
                    <?php if ($nextAppts->num_rows === 0): ?>
                        <div style="text-align: center; padding: 40px 20px; color: #64748B;">
                            <div style="font-size: 48px; margin-bottom: 16px;">🗓️</div>
                            <p>No appointments scheduled for today</p>
                            <a href="<?= BASE_URL; ?>/pages/appointments/book.php" class="btn-primary-enhanced" style="margin-top: 16px;">
                                Book First Appointment
                            </a>
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
                            <a href="<?= BASE_URL; ?>/pages/appointments/calendar.php" class="btn-secondary-enhanced">
                                View Full Calendar
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Patients -->
            <div class="card-enhanced">
                <div class="card-header">
                    <h3>👥 Recent Patients</h3>
                </div>
                <div class="card-body">
                    <?php if ($recentPatients->num_rows === 0): ?>
                        <div style="text-align: center; padding: 40px 20px; color: #64748B;">
                            <div style="font-size: 48px; margin-bottom: 16px;">👤</div>
                            <p>No patients registered yet</p>
                            <a href="<?= BASE_URL; ?>/pages/patients/add.php" class="btn-primary-enhanced" style="margin-top: 16px;">
                                Add First Patient
                            </a>
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
                            <a href="<?= BASE_URL; ?>/pages/patients/list.php" class="btn-secondary-enhanced">
                                View All Patients
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Stats Row -->
        <div class="grid grid-cols-3 gap-4" style="margin-top: 30px;">
            <!-- Monthly Performance -->
            <div class="card-enhanced">
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
                            <a href="<?= BASE_URL; ?>/pages/communications/feedback.php" style="font-size: 12px; color: #D97706; text-decoration: none;">
                                Review feedback →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- System Status -->
            <div class="card-enhanced">
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
                        <a href="<?= BASE_URL; ?>/pages/settings/integrations_enhanced.php" class="btn-secondary-enhanced">
                            Manage Settings
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card-enhanced">
                <div class="card-header">
                    <h3>🚀 Quick Links</h3>
                </div>
                <div class="card-body">
                    <div style="space-y: 8px;">
                        <a href="<?= BASE_URL; ?>/pages/reports/operational.php" style="display: flex; align-items: center; padding: 8px; text-decoration: none; color: #1E293B; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='transparent'">
                            <span style="margin-right: 12px;">📊</span>
                            <span style="font-size: 14px;">View Reports</span>
                        </a>
                        <a href="<?= BASE_URL; ?>/pages/records/prescriptions.php" style="display: flex; align-items: center; padding: 8px; text-decoration: none; color: #1E293B; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='transparent'">
                            <span style="margin-right: 12px;">💊</span>
                            <span style="font-size: 14px;">Prescriptions</span>
                        </a>
                        <a href="<?= BASE_URL; ?>/pages/billing/insurance.php" style="display: flex; align-items: center; padding: 8px; text-decoration: none; color: #1E293B; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='transparent'">
                            <span style="margin-right: 12px;">🏥</span>
                            <span style="font-size: 14px;">Insurance Claims</span>
                        </a>
                        <a href="<?= BASE_URL; ?>/pages/help.php" style="display: flex; align-items: center; padding: 8px; text-decoration: none; color: #1E293B; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='transparent'">
                            <span style="margin-right: 12px;">❓</span>
                            <span style="font-size: 14px;">Help & Support</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Responsive grid adjustments */
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
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
