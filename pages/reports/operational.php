<?php
/*****************************************************************
 * pages/reports/operational.php
 * ---------------------------------------------------------------
 * Operational metrics and analytics dashboard
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Role restriction - Reports usually for Admin/Management ───────── */
if (!is_admin() && ($_SESSION['role'] ?? 0) !== 2) { // Admin or Dentist
    flash('You do not have permission to view operational reports.');
    redirect('/dentosys/index.php');
}

/* ───────── Date range handling ───────── */
$start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Last day of current month

/* ───────── Key Metrics Queries ───────── */

// 1. Appointment Metrics
$appointment_metrics = $conn->query("
    SELECT 
        COUNT(*) as total_appointments,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as completed_appointments,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_appointments,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_appointments,
        ROUND(AVG(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) * 100, 2) as completion_rate
    FROM Appointment 
    WHERE DATE(appointment_dt) BETWEEN '$start_date' AND '$end_date'
")->fetch_assoc();

// 2. Patient Metrics
$patient_metrics = $conn->query("
    SELECT 
        COUNT(DISTINCT p.patient_id) as active_patients,
        COUNT(DISTINCT CASE WHEN a.appointment_dt >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN p.patient_id END) as recent_patients,
        COUNT(DISTINCT CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN p.patient_id END) as new_patients
    FROM Patient p
    LEFT JOIN Appointment a ON a.patient_id = p.patient_id
    WHERE p.created_at <= '$end_date'
")->fetch_assoc();

// 3. Financial Metrics
$financial_metrics = $conn->query("
    SELECT 
        COUNT(*) as total_invoices,
        SUM(total_amount) as total_revenue,
        SUM(CASE WHEN status = 'Paid' THEN total_amount ELSE 0 END) as collected_revenue,
        SUM(CASE WHEN status = 'Unpaid' THEN total_amount ELSE 0 END) as outstanding_revenue,
        ROUND(AVG(total_amount), 2) as avg_invoice_amount
    FROM Invoice 
    WHERE issued_date BETWEEN '$start_date' AND '$end_date'
")->fetch_assoc();

// 4. Dentist Productivity
$dentist_productivity = $conn->query("
    SELECT 
        u.email as dentist_name,
        d.specialty,
        COUNT(a.appointment_id) as total_appointments,
        SUM(CASE WHEN a.status = 'Approved' THEN 1 ELSE 0 END) as completed_appointments,
        COALESCE(SUM(i.total_amount), 0) as revenue_generated
    FROM Dentist d
    JOIN UserTbl u ON u.user_id = d.user_id
    LEFT JOIN Appointment a ON a.dentist_id = d.dentist_id 
        AND DATE(a.appointment_dt) BETWEEN '$start_date' AND '$end_date'
    LEFT JOIN Invoice i ON i.patient_id = a.patient_id 
        AND DATE(i.issued_date) BETWEEN '$start_date' AND '$end_date'
    GROUP BY d.dentist_id, u.email, d.specialty
    ORDER BY revenue_generated DESC
");

// 5. Daily Appointment Trends (Last 30 days)
$daily_trends = $conn->query("
    SELECT 
        DATE(appointment_dt) as appointment_date,
        COUNT(*) as appointment_count,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as completed_count
    FROM Appointment 
    WHERE appointment_dt >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(appointment_dt)
    ORDER BY appointment_date ASC
");

// 6. Treatment Types (if we have treatment data)
$treatment_types = $conn->query("
    SELECT 
        'General Checkup' as treatment_type,
        COUNT(*) as count
    FROM Appointment 
    WHERE DATE(appointment_dt) BETWEEN '$start_date' AND '$end_date'
        AND notes LIKE '%checkup%'
    UNION ALL
    SELECT 
        'Cleaning' as treatment_type,
        COUNT(*) as count
    FROM Appointment 
    WHERE DATE(appointment_dt) BETWEEN '$start_date' AND '$end_date'
        AND notes LIKE '%clean%'
    UNION ALL
    SELECT 
        'Emergency' as treatment_type,
        COUNT(*) as count
    FROM Appointment 
    WHERE DATE(appointment_dt) BETWEEN '$start_date' AND '$end_date'
        AND notes LIKE '%emergency%'
");

// 7. Insurance Claims Summary
$insurance_summary = $conn->query("
    SELECT 
        COUNT(*) as total_claims,
        SUM(claim_amount) as total_claimed,
        SUM(CASE WHEN status = 'Approved' THEN claim_amount ELSE 0 END) as approved_amount,
        SUM(CASE WHEN status = 'Paid' THEN paid_amount ELSE 0 END) as paid_amount
    FROM InsuranceClaim 
    WHERE created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
");
$insurance_data = $insurance_summary->fetch_assoc();

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
    <h2>Operational Metrics Dashboard</h2>
    <?= get_flash(); ?>

    <!-- Date Range Filter -->
    <form method="get" style="margin-bottom: 30px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
        <div style="display: flex; gap: 15px; align-items: end;">
            <div>
                <label>Start Date:</label>
                <input type="date" name="start_date" value="<?= $start_date; ?>" style="margin-top: 5px;">
            </div>
            <div>
                <label>End Date:</label>
                <input type="date" name="end_date" value="<?= $end_date; ?>" style="margin-top: 5px;">
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Update Report</button>
                <a href="operational.php" class="btn btn-outline">Reset</a>
            </div>
        </div>
    </form>

    <!-- Key Performance Indicators -->
    <h3>Key Performance Indicators</h3>
    <div class="grid grid-cols-4 gap-4 mb-4">
        <!-- Appointment KPIs -->
        <div class="card text-center">
            <h3><?= $appointment_metrics['total_appointments']; ?></h3>
            <p>Total Appointments</p>
            <small><?= $appointment_metrics['completion_rate']; ?>% completion rate</small>
        </div>
        
        <div class="card text-center">
            <h3><?= $patient_metrics['active_patients']; ?></h3>
            <p>Active Patients</p>
            <small><?= $patient_metrics['new_patients']; ?> new this month</small>
        </div>
        
        <div class="card text-center">
            <h3>$<?= number_format($financial_metrics['total_revenue'] ?? 0, 0); ?></h3>
            <p>Total Revenue</p>
            <small>$<?= number_format($financial_metrics['avg_invoice_amount'] ?? 0, 0); ?> average</small>
        </div>
        
        <div class="card text-center">
            <h3><?= $insurance_data['total_claims'] ?? 0; ?></h3>
            <p>Insurance Claims</p>
            <small>$<?= number_format($insurance_data['total_claimed'] ?? 0, 0); ?> claimed</small>
        </div>
    </div>

    <!-- Detailed Metrics Grid -->
    <div class="grid grid-cols-2 gap-6">
        <!-- Appointment Breakdown -->
        <div class="card">
            <h4>Appointment Status Breakdown</h4>
            <table class="table">
                <tr>
                    <td>Completed</td>
                    <td class="text-right"><?= $appointment_metrics['completed_appointments']; ?></td>
                </tr>
                <tr>
                    <td>Pending</td>
                    <td class="text-right"><?= $appointment_metrics['pending_appointments']; ?></td>
                </tr>
                <tr>
                    <td>Cancelled</td>
                    <td class="text-right"><?= $appointment_metrics['cancelled_appointments']; ?></td>
                </tr>
                <tr style="font-weight: bold; border-top: 2px solid #ddd;">
                    <td>Total</td>
                    <td class="text-right"><?= $appointment_metrics['total_appointments']; ?></td>
                </tr>
            </table>
        </div>

        <!-- Financial Summary -->
        <div class="card">
            <h4>Financial Summary</h4>
            <table class="table">
                <tr>
                    <td>Total Revenue</td>
                    <td class="text-right">$<?= number_format($financial_metrics['total_revenue'] ?? 0, 2); ?></td>
                </tr>
                <tr>
                    <td>Collected</td>
                    <td class="text-right">$<?= number_format($financial_metrics['collected_revenue'] ?? 0, 2); ?></td>
                </tr>
                <tr>
                    <td>Outstanding</td>
                    <td class="text-right">$<?= number_format($financial_metrics['outstanding_revenue'] ?? 0, 2); ?></td>
                </tr>
                <tr style="font-weight: bold; border-top: 2px solid #ddd;">
                    <td>Collection Rate</td>
                    <td class="text-right">
                        <?= $financial_metrics['total_revenue'] > 0 ? 
                            round(($financial_metrics['collected_revenue'] / $financial_metrics['total_revenue']) * 100, 1) : 0; ?>%
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Dentist Productivity -->
    <div class="card" style="margin-top: 30px;">
        <h4>Dentist Productivity Report</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Dentist</th>
                    <th>Specialty</th>
                    <th>Total Appointments</th>
                    <th>Completed</th>
                    <th>Completion Rate</th>
                    <th>Revenue Generated</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($dentist_productivity->num_rows === 0): ?>
                    <tr><td colspan="6" class="text-center">No productivity data available for this period.</td></tr>
                <?php else: ?>
                    <?php while ($dentist = $dentist_productivity->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($dentist['dentist_name']); ?></td>
                            <td><?= htmlspecialchars($dentist['specialty'] ?: 'General'); ?></td>
                            <td><?= $dentist['total_appointments']; ?></td>
                            <td><?= $dentist['completed_appointments']; ?></td>
                            <td>
                                <?= $dentist['total_appointments'] > 0 ? 
                                    round(($dentist['completed_appointments'] / $dentist['total_appointments']) * 100, 1) : 0; ?>%
                            </td>
                            <td>$<?= number_format($dentist['revenue_generated'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Daily Trends Chart (Simple Text Version) -->
    <div class="card" style="margin-top: 30px;">
        <h4>Daily Appointment Trends (Last 30 Days)</h4>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Appointments</th>
                        <th>Completed</th>
                        <th>Visual Trend</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($daily_trends->num_rows === 0): ?>
                        <tr><td colspan="4" class="text-center">No appointment data available.</td></tr>
                    <?php else: ?>
                        <?php while ($day = $daily_trends->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('M d', strtotime($day['appointment_date'])); ?></td>
                                <td><?= $day['appointment_count']; ?></td>
                                <td><?= $day['completed_count']; ?></td>
                                <td>
                                    <?php 
                                    // Simple visual bar
                                    $bar_width = min(($day['appointment_count'] * 10), 100);
                                    echo "<div style='background: #4CAF50; height: 20px; width: {$bar_width}px; border-radius: 3px;'></div>";
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Export Options -->
    <div style="margin-top: 30px; text-align: center;">
        <h4>Export Options</h4>
        <a href="?export=pdf&start_date=<?= $start_date; ?>&end_date=<?= $end_date; ?>" class="btn btn-primary">📄 Export as PDF</a>
        <a href="?export=csv&start_date=<?= $start_date; ?>&end_date=<?= $end_date; ?>" class="btn btn-secondary">📊 Export as CSV</a>
        <a href="financial.php" class="btn btn-outline">💰 Financial Reports</a>
    </div>
</main>

<style>
.card h4 {
    margin-bottom: 15px;
    color: #333;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 8px;
}
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
