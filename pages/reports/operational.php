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

/* ───────── Key Metrics Queries with role-based filtering ───────── */

// Build filtering conditions for dentists
$dentist_filter = '';
if (is_dentist()) {
    $current_dentist_id = get_current_dentist_id();
    if ($current_dentist_id) {
        $dentist_filter = "AND a.dentist_id = $current_dentist_id";
    } else {
        $dentist_filter = "AND 1 = 0"; // Show no data if dentist not found
    }
}

// 1. Appointment Metrics
$appointment_metrics = $conn->query("
    SELECT 
        COUNT(*) as total_appointments,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as completed_appointments,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_appointments,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_appointments,
        ROUND(AVG(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) * 100, 2) as completion_rate
    FROM Appointment a
    WHERE DATE(a.appointment_dt) BETWEEN '$start_date' AND '$end_date' $dentist_filter
")->fetch_assoc();

// 2. Patient Metrics
if (is_dentist()) {
    // For dentists, only show metrics for their assigned patients
    $patient_ids = get_dentist_patient_ids();
    if (empty($patient_ids)) {
        $patient_metrics = ['active_patients' => 0, 'recent_patients' => 0, 'new_patients' => 0];
    } else {
        $patient_ids_str = implode(',', $patient_ids);
        $patient_metrics = $conn->query("
            SELECT 
                COUNT(DISTINCT p.patient_id) as active_patients,
                COUNT(DISTINCT CASE WHEN a.appointment_dt >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN p.patient_id END) as recent_patients,
                COUNT(DISTINCT CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN p.patient_id END) as new_patients
            FROM Patient p
            LEFT JOIN Appointment a ON a.patient_id = p.patient_id
            WHERE p.created_at <= '$end_date' AND p.patient_id IN ($patient_ids_str)
        ")->fetch_assoc();
    }
} else {
    $patient_metrics = $conn->query("
        SELECT 
            COUNT(DISTINCT p.patient_id) as active_patients,
            COUNT(DISTINCT CASE WHEN a.appointment_dt >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN p.patient_id END) as recent_patients,
            COUNT(DISTINCT CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN p.patient_id END) as new_patients
        FROM Patient p
        LEFT JOIN Appointment a ON a.patient_id = p.patient_id
        WHERE p.created_at <= '$end_date'
    ")->fetch_assoc();
}

// 3. Financial Metrics
if (is_dentist()) {
    $pids = get_dentist_patient_ids();
    if (empty($pids)) {
        $financial_metrics = [
            'total_invoices' => 0,
            'total_revenue' => 0,
            'collected_revenue' => 0,
            'outstanding_revenue' => 0,
            'avg_invoice_amount' => 0,
        ];
    } else {
        $pidStr = implode(',', array_map('intval', $pids));
        $financial_metrics = $conn->query("
            SELECT 
                COUNT(*) as total_invoices,
                SUM(total_amount) as total_revenue,
                SUM(CASE WHEN status = 'Paid' THEN total_amount ELSE 0 END) as collected_revenue,
                SUM(CASE WHEN status = 'Unpaid' THEN total_amount ELSE 0 END) as outstanding_revenue,
                ROUND(AVG(total_amount), 2) as avg_invoice_amount
            FROM Invoice 
            WHERE issued_date BETWEEN '$start_date' AND '$end_date'
              AND patient_id IN ($pidStr)
        ")->fetch_assoc();
    }
} else {
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
}

// 4. Dentist Productivity
if (is_dentist()) {
    $dentist_id_scope = intval($current_dentist_id ?? 0);
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
        WHERE d.dentist_id = $dentist_id_scope
        GROUP BY d.dentist_id, u.email, d.specialty
        ORDER BY revenue_generated DESC
    ");
} else {
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
}

// 5. Daily Appointment Trends (Last 30 days)
if (is_dentist()) {
    $dentist_id_scope = intval($current_dentist_id ?? 0);
    $daily_trends = $conn->query("
        SELECT 
            DATE(appointment_dt) as appointment_date,
            COUNT(*) as appointment_count,
            SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as completed_count
        FROM Appointment 
        WHERE appointment_dt >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND dentist_id = $dentist_id_scope
        GROUP BY DATE(appointment_dt)
        ORDER BY appointment_date ASC
    ");
} else {
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
}

// 6. Treatment Types (if we have treatment data)
if (is_dentist()) {
    $dentist_id_scope = intval($current_dentist_id ?? 0);
    $treatment_types = $conn->query("
        SELECT 
            'General Checkup' as treatment_type,
            COUNT(*) as count
        FROM Appointment 
        WHERE DATE(appointment_dt) BETWEEN '$start_date' AND '$end_date'
            AND dentist_id = $dentist_id_scope
            AND notes LIKE '%checkup%'
        UNION ALL
        SELECT 
            'Cleaning' as treatment_type,
            COUNT(*) as count
        FROM Appointment 
        WHERE DATE(appointment_dt) BETWEEN '$start_date' AND '$end_date'
            AND dentist_id = $dentist_id_scope
            AND notes LIKE '%clean%'
        UNION ALL
        SELECT 
            'Emergency' as treatment_type,
            COUNT(*) as count
        FROM Appointment 
        WHERE DATE(appointment_dt) BETWEEN '$start_date' AND '$end_date'
            AND dentist_id = $dentist_id_scope
            AND notes LIKE '%emergency%'
    ");
} else {
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
}

// 7. Insurance Claims Summary
if (is_dentist()) {
    $pids = get_dentist_patient_ids();
    if (empty($pids)) {
        $insurance_data = [
            'total_claims' => 0,
            'total_claimed' => 0,
            'approved_amount' => 0,
            'paid_amount' => 0,
        ];
    } else {
        $pidStr = implode(',', array_map('intval', $pids));
        $insurance_summary = $conn->query("
            SELECT 
                COUNT(*) as total_claims,
                SUM(claim_amount) as total_claimed,
                SUM(CASE WHEN status = 'Approved' THEN claim_amount ELSE 0 END) as approved_amount,
                SUM(CASE WHEN status = 'Paid' THEN paid_amount ELSE 0 END) as paid_amount
            FROM InsuranceClaim 
            WHERE created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
              AND patient_id IN ($pidStr)
        ");
        $insurance_data = $insurance_summary->fetch_assoc();
    }
} else {
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
}

/* ───────── Export Handling ───────── */
if (isset($_GET['export'])) {
    $export_format = $_GET['export'];
    
    // Log export activity
    $export_details = "Operational report exported for period: $start_date to $end_date";
    log_export_event('Operational Report', $export_details);
    
    if ($export_format === 'csv') {
        // CSV Export
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="operational_report_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Report Header
        fputcsv($output, ['DentoSys Operational Report']);
        fputcsv($output, ['Generated on', date('Y-m-d H:i:s')]);
        fputcsv($output, ['Period', "$start_date to $end_date"]);
        fputcsv($output, ['Exported by', $_SESSION['email'] ?? 'Unknown']);
        fputcsv($output, []); // Empty row
        
        // Key Metrics
        fputcsv($output, ['KEY PERFORMANCE INDICATORS']);
        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Total Appointments', $appointment_metrics['total_appointments']]);
        fputcsv($output, ['Completed Appointments', $appointment_metrics['completed_appointments']]);
        fputcsv($output, ['Cancelled Appointments', $appointment_metrics['cancelled_appointments']]);
        fputcsv($output, ['Pending Appointments', $appointment_metrics['pending_appointments']]);
        fputcsv($output, ['Completion Rate (%)', $appointment_metrics['completion_rate']]);
        fputcsv($output, ['Active Patients', $patient_metrics['active_patients']]);
        fputcsv($output, ['New Patients (Last 30 days)', $patient_metrics['new_patients']]);
        fputcsv($output, ['Total Revenue', '$' . number_format($financial_metrics['total_revenue'] ?? 0, 2)]);
        fputcsv($output, ['Collected Revenue', '$' . number_format($financial_metrics['collected_revenue'] ?? 0, 2)]);
        fputcsv($output, ['Outstanding Revenue', '$' . number_format($financial_metrics['outstanding_revenue'] ?? 0, 2)]);
        fputcsv($output, ['Average Invoice Amount', '$' . number_format($financial_metrics['avg_invoice_amount'] ?? 0, 2)]);
        fputcsv($output, []); // Empty row
        
        // Dentist Productivity
        fputcsv($output, ['DENTIST PRODUCTIVITY']);
        fputcsv($output, ['Dentist', 'Specialty', 'Total Appointments', 'Completed', 'Completion Rate (%)', 'Revenue Generated']);
        
        $dentist_productivity->data_seek(0); // Reset result pointer
        while ($dentist = $dentist_productivity->fetch_assoc()) {
            $completion_rate = $dentist['total_appointments'] > 0 ? 
                round(($dentist['completed_appointments'] / $dentist['total_appointments']) * 100, 1) : 0;
            
            fputcsv($output, [
                $dentist['dentist_name'],
                $dentist['specialty'] ?: 'General',
                $dentist['total_appointments'],
                $dentist['completed_appointments'],
                $completion_rate,
                '$' . number_format($dentist['revenue_generated'], 2)
            ]);
        }
        fputcsv($output, []); // Empty row
        
        // Daily Trends
        fputcsv($output, ['DAILY APPOINTMENT TRENDS (Last 30 Days)']);
        fputcsv($output, ['Date', 'Total Appointments', 'Completed']);
        
        $daily_trends->data_seek(0); // Reset result pointer
        while ($day = $daily_trends->fetch_assoc()) {
            fputcsv($output, [
                $day['appointment_date'],
                $day['appointment_count'],
                $day['completed_count']
            ]);
        }
        
        fclose($output);
        exit;
        
    } elseif ($export_format === 'pdf') {
        // For PDF export, we'll create a simple HTML version that can be printed/saved as PDF
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>DentoSys Operational Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #7c3aed; padding-bottom: 15px; }
                .header h1 { color: #7c3aed; margin: 0; }
                .info { background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
                .section { margin-bottom: 30px; }
                .section h2 { color: #7c3aed; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
                th { background: #f8fafc; font-weight: bold; }
                .metric-value { font-weight: bold; color: #7c3aed; }
                .no-data { text-align: center; color: #6b7280; font-style: italic; }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>🏥 DentoSys Operational Report</h1>
                <p>Generated on <?= date('F j, Y \a\t g:i A'); ?></p>
                <p>Report Period: <?= date('F j, Y', strtotime($start_date)); ?> - <?= date('F j, Y', strtotime($end_date)); ?></p>
                <p>Exported by: <?= htmlspecialchars($_SESSION['email'] ?? 'Unknown'); ?></p>
            </div>

            <div class="info no-print">
                <strong>📋 Instructions:</strong> Use your browser's print function (Ctrl+P) and select "Save as PDF" to create a PDF file.
            </div>

            <div class="section">
                <h2>📊 Key Performance Indicators</h2>
                <table>
                    <tr><td>Total Appointments</td><td class="metric-value"><?= $appointment_metrics['total_appointments']; ?></td></tr>
                    <tr><td>Completed Appointments</td><td class="metric-value"><?= $appointment_metrics['completed_appointments']; ?></td></tr>
                    <tr><td>Cancelled Appointments</td><td class="metric-value"><?= $appointment_metrics['cancelled_appointments']; ?></td></tr>
                    <tr><td>Pending Appointments</td><td class="metric-value"><?= $appointment_metrics['pending_appointments']; ?></td></tr>
                    <tr><td>Completion Rate</td><td class="metric-value"><?= $appointment_metrics['completion_rate']; ?>%</td></tr>
                    <tr><td>Active Patients</td><td class="metric-value"><?= $patient_metrics['active_patients']; ?></td></tr>
                    <tr><td>New Patients (Last 30 days)</td><td class="metric-value"><?= $patient_metrics['new_patients']; ?></td></tr>
                    <tr><td>Total Revenue</td><td class="metric-value">$<?= number_format($financial_metrics['total_revenue'] ?? 0, 2); ?></td></tr>
                    <tr><td>Collected Revenue</td><td class="metric-value">$<?= number_format($financial_metrics['collected_revenue'] ?? 0, 2); ?></td></tr>
                    <tr><td>Outstanding Revenue</td><td class="metric-value">$<?= number_format($financial_metrics['outstanding_revenue'] ?? 0, 2); ?></td></tr>
                    <tr><td>Average Invoice Amount</td><td class="metric-value">$<?= number_format($financial_metrics['avg_invoice_amount'] ?? 0, 2); ?></td></tr>
                </table>
            </div>

            <div class="section">
                <h2>👨‍⚕️ Dentist Productivity</h2>
                <table>
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
                        <?php 
                        $dentist_productivity->data_seek(0); // Reset result pointer
                        if ($dentist_productivity->num_rows === 0): ?>
                            <tr><td colspan="6" class="no-data">No productivity data available for this period.</td></tr>
                        <?php else: ?>
                            <?php while ($dentist = $dentist_productivity->fetch_assoc()): ?>
                                <?php 
                                $completion_rate = $dentist['total_appointments'] > 0 ? 
                                    round(($dentist['completed_appointments'] / $dentist['total_appointments']) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($dentist['dentist_name']); ?></td>
                                    <td><?= htmlspecialchars($dentist['specialty'] ?: 'General'); ?></td>
                                    <td><?= $dentist['total_appointments']; ?></td>
                                    <td><?= $dentist['completed_appointments']; ?></td>
                                    <td><?= $completion_rate; ?>%</td>
                                    <td>$<?= number_format($dentist['revenue_generated'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h2>📅 Daily Appointment Trends (Last 30 Days)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Total Appointments</th>
                            <th>Completed</th>
                            <th>Completion Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $daily_trends->data_seek(0); // Reset result pointer
                        if ($daily_trends->num_rows === 0): ?>
                            <tr><td colspan="4" class="no-data">No appointment data available.</td></tr>
                        <?php else: ?>
                            <?php while ($day = $daily_trends->fetch_assoc()): ?>
                                <?php 
                                $daily_completion_rate = $day['appointment_count'] > 0 ? 
                                    round(($day['completed_count'] / $day['appointment_count']) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($day['appointment_date'])); ?></td>
                                    <td><?= $day['appointment_count']; ?></td>
                                    <td><?= $day['completed_count']; ?></td>
                                    <td><?= $daily_completion_rate; ?>%</td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h2>🦷 Treatment Types</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Treatment Type</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($treatment_types) && $treatment_types && $treatment_types->num_rows > 0): ?>
                            <?php $treatment_types->data_seek(0); while ($row = $treatment_types->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['treatment_type']); ?></td>
                                    <td><?= (int)$row['count']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="2" class="no-data">No treatment type data for this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h2>🏥 Insurance Claims Summary</h2>
                <table>
                    <tbody>
                        <tr><td>Total Claims</td><td class="metric-value"><?= (int)($insurance_data['total_claims'] ?? 0); ?></td></tr>
                        <tr><td>Total Claimed</td><td class="metric-value">$<?= number_format($insurance_data['total_claimed'] ?? 0, 2); ?></td></tr>
                        <tr><td>Approved Amount</td><td class="metric-value">$<?= number_format($insurance_data['approved_amount'] ?? 0, 2); ?></td></tr>
                        <tr><td>Paid Amount</td><td class="metric-value">$<?= number_format($insurance_data['paid_amount'] ?? 0, 2); ?></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h2>📋 Report Summary</h2>
                <table>
                    <tr><td>Total Records Processed</td><td class="metric-value"><?= $appointment_metrics['total_appointments'] + $patient_metrics['active_patients']; ?></td></tr>
                    <tr><td>Collection Rate</td><td class="metric-value">
                        <?= $financial_metrics['total_revenue'] > 0 ? 
                            round(($financial_metrics['collected_revenue'] / $financial_metrics['total_revenue']) * 100, 1) : 0; ?>%
                    </td></tr>
                    <tr><td>Report Generated</td><td class="metric-value"><?= date('Y-m-d H:i:s'); ?></td></tr>
                </table>
            </div>
            
            <script>
                // Auto-print for PDF generation
                window.onload = function() {
                    window.print();
                };
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>

<style>
.operational-main {
    padding: 0 2rem 3rem;
    background: linear-gradient(135deg, #fef7ff 0%, #f3e8ff 100%);
    min-height: 100vh;
}

.page-header {
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
    margin: 0 -2rem 2rem;
    padding: 2rem 2rem 2.5rem;
    color: white;
    border-radius: 0 0 24px 24px;
    box-shadow: 0 8px 32px -8px rgba(124, 58, 237, 0.3);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
    flex-wrap: wrap;
}

.title-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.icon-wrapper {
    width: 60px;
    height: 60px;
    background: rgba(255,255,255,0.2);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    backdrop-filter: blur(10px);
}

.page-header h1 {
    margin: 0 0 0.25rem;
    font-size: 2.2rem;
    font-weight: 700;
    letter-spacing: -0.025em;
}

.subtitle {
    margin: 0;
    opacity: 0.9;
    font-size: 1rem;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.filters-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.filter-input {
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: #f9fafb;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.filter-input:focus {
    border-color: #7c3aed;
    outline: none;
    background: white;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
}

.filter-actions {
    display: flex;
    gap: 0.75rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
    justify-content: center;
}

.btn-primary {
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
    color: white;
    box-shadow: 0 4px 12px -4px rgba(124, 58, 237, 0.4);
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px -4px rgba(124, 58, 237, 0.6);
}

.btn-secondary {
    background: white;
    color: #374151;
    border: 2px solid #e5e7eb;
    box-shadow: 0 2px 8px -2px rgba(0,0,0,0.1);
}

.btn-secondary:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

.btn-outline {
    background: rgba(255,255,255,0.1);
    color: white;
    border: 2px solid rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
}

.btn-outline:hover {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.3);
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.kpi-card {
    background: white;
    padding: 1.5rem;
    border-radius: 16px;
    text-align: center;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
    transition: transform 0.2s ease;
    position: relative;
    overflow: hidden;
}

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #7c3aed, #6d28d9);
}

.kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px -8px rgba(0,0,0,0.15);
}

.kpi-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #ede9fe, #ddd6fe);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin: 0 auto 1rem;
}

.kpi-value {
    font-size: 2rem;
    font-weight: 700;
    color: #7c3aed;
    margin-bottom: 0.5rem;
}

.kpi-label {
    color: #64748b;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.kpi-detail {
    color: #9ca3af;
    font-size: 0.75rem;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.metric-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
}

.metric-header {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.metric-title {
    margin: 0;
    color: #1e293b;
    font-size: 1.1rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.metric-content {
    padding: 0;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
}

.modern-table td {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.modern-table tr:hover {
    background: #f8fafc;
}

.metric-label {
    font-weight: 500;
    color: #374151;
}

.metric-value {
    text-align: right;
    font-weight: 700;
    color: #7c3aed;
}

.total-row {
    font-weight: bold;
    border-top: 2px solid #e2e8f0;
    background: #f8fafc;
}

.productivity-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
    margin-bottom: 2rem;
}

.productivity-header {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.productivity-title {
    margin: 0;
    color: #1e293b;
    font-size: 1.25rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.productivity-table {
    width: 100%;
    border-collapse: collapse;
}

.productivity-table th {
    background: #f8fafc;
    padding: 1rem 1.5rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #e2e8f0;
}

.productivity-table td {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.productivity-table tr:hover {
    background: #f8fafc;
}

.dentist-name {
    font-weight: 600;
    color: #374151;
}

.specialty-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #ede9fe;
    color: #7c3aed;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.completion-rate {
    font-weight: 600;
}

.high-rate { color: #059669; }
.medium-rate { color: #d97706; }
.low-rate { color: #dc2626; }

.revenue-value {
    font-weight: 700;
    color: #7c3aed;
}

.trends-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
    margin-bottom: 2rem;
}

.trends-header {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.trends-title {
    margin: 0;
    color: #1e293b;
    font-size: 1.25rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.trends-content {
    overflow-x: auto;
}

.trend-bar {
    background: linear-gradient(90deg, #7c3aed, #a855f7);
    height: 20px;
    border-radius: 10px;
    min-width: 4px;
    transition: all 0.2s ease;
}

.trend-bar:hover {
    transform: scaleY(1.2);
}

.export-section {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
}

.export-title {
    margin: 0 0 1.5rem;
    color: #1e293b;
    font-size: 1.25rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.export-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.empty-state {
    text-align: center;
    padding: 3rem 1.5rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .operational-main { padding: 0 1rem 2rem; }
    .page-header { margin: 0 -1rem 1.5rem; padding: 1.5rem 1rem 2rem; }
    .header-content { flex-direction: column; align-items: stretch; text-align: center; }
    .filters-grid { grid-template-columns: 1fr; }
    .filter-actions { flex-direction: column; }
    .kpi-grid { grid-template-columns: 1fr; }
    .metrics-grid { grid-template-columns: 1fr; }
    .export-actions { flex-direction: column; }
    .kpi-value { font-size: 1.5rem; }
}
</style>

<main class="operational-main">
    <div class="page-header">
        <div class="header-content">
            <div class="title-section">
                <div class="icon-wrapper">📈</div>
                <div>
                    <h1>Operational Dashboard</h1>
                    <p class="subtitle">Performance metrics and business insights</p>
                </div>
            </div>
            <div class="header-actions">
                <a class="btn btn-outline" href="financial.php">
                    <span>💰</span>
                    Financial Reports
                </a>
                <?php if (!is_dentist()): ?>
                    <a class="btn btn-outline" href="audit_log.php">
                        <span>📋</span>
                        Audit Log
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?= get_flash(); ?>

    <!-- Date Range Filter -->
    <div class="filters-card">
        <form method="get">
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Start Date</label>
                    <input type="date" name="start_date" value="<?= $start_date; ?>" class="filter-input">
                </div>

                <div class="filter-group">
                    <label class="filter-label">End Date</label>
                    <input type="date" name="end_date" value="<?= $end_date; ?>" class="filter-input">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <span>🔄</span>
                        Update Report
                    </button>
                    <a class="btn btn-secondary" href="operational.php">
                        <span>↺</span>
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Key Performance Indicators -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon">📅</div>
            <div class="kpi-value"><?= $appointment_metrics['total_appointments']; ?></div>
            <div class="kpi-label">Total Appointments</div>
            <div class="kpi-detail"><?= $appointment_metrics['completion_rate']; ?>% completion rate</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon">👥</div>
            <div class="kpi-value"><?= $patient_metrics['active_patients']; ?></div>
            <div class="kpi-label">Active Patients</div>
            <div class="kpi-detail"><?= $patient_metrics['new_patients']; ?> new this period</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon">💰</div>
            <div class="kpi-value">$<?= number_format($financial_metrics['total_revenue'] ?? 0, 0); ?></div>
            <div class="kpi-label">Total Revenue</div>
            <div class="kpi-detail">$<?= number_format($financial_metrics['avg_invoice_amount'] ?? 0, 0); ?> average</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon">🏥</div>
            <div class="kpi-value"><?= $insurance_data['total_claims'] ?? 0; ?></div>
            <div class="kpi-label">Insurance Claims</div>
            <div class="kpi-detail">$<?= number_format($insurance_data['total_claimed'] ?? 0, 0); ?> claimed</div>
        </div>
    </div>

    <!-- Detailed Metrics Grid -->
    <div class="metrics-grid">
        <!-- Appointment Breakdown -->
        <div class="metric-card">
            <div class="metric-header">
                <h3 class="metric-title">
                    <span>📊</span>
                    Appointment Status Breakdown
                </h3>
            </div>
            <div class="metric-content">
                <table class="modern-table">
                    <tbody>
                        <tr>
                            <td class="metric-label">Completed</td>
                            <td class="metric-value"><?= $appointment_metrics['completed_appointments']; ?></td>
                        </tr>
                        <tr>
                            <td class="metric-label">Pending</td>
                            <td class="metric-value"><?= $appointment_metrics['pending_appointments']; ?></td>
                        </tr>
                        <tr>
                            <td class="metric-label">Cancelled</td>
                            <td class="metric-value"><?= $appointment_metrics['cancelled_appointments']; ?></td>
                        </tr>
                        <tr class="total-row">
                            <td class="metric-label">Total</td>
                            <td class="metric-value"><?= $appointment_metrics['total_appointments']; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="metric-card">
            <div class="metric-header">
                <h3 class="metric-title">
                    <span>💵</span>
                    Financial Summary
                </h3>
            </div>
            <div class="metric-content">
                <table class="modern-table">
                    <tbody>
                        <tr>
                            <td class="metric-label">Total Revenue</td>
                            <td class="metric-value">$<?= number_format($financial_metrics['total_revenue'] ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                            <td class="metric-label">Collected</td>
                            <td class="metric-value">$<?= number_format($financial_metrics['collected_revenue'] ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                            <td class="metric-label">Outstanding</td>
                            <td class="metric-value">$<?= number_format($financial_metrics['outstanding_revenue'] ?? 0, 2); ?></td>
                        </tr>
                        <tr class="total-row">
                            <td class="metric-label">Collection Rate</td>
                            <td class="metric-value">
                                <?= $financial_metrics['total_revenue'] > 0 ? 
                                    round(($financial_metrics['collected_revenue'] / $financial_metrics['total_revenue']) * 100, 1) : 0; ?>%
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Dentist Productivity -->
    <div class="productivity-card">
        <div class="productivity-header">
            <h3 class="productivity-title">
                <span>👨‍⚕️</span>
                Dentist Productivity Report
            </h3>
        </div>
        
        <table class="productivity-table">
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
                    <tr>
                        <td colspan="6" class="empty-state">
                            <div class="empty-icon">👨‍⚕️</div>
                            <div>No productivity data available for this period.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($dentist = $dentist_productivity->fetch_assoc()): ?>
                        <?php 
                        $completion_rate = $dentist['total_appointments'] > 0 ? 
                            round(($dentist['completed_appointments'] / $dentist['total_appointments']) * 100, 1) : 0;
                        $rate_class = $completion_rate >= 80 ? 'high-rate' : ($completion_rate >= 60 ? 'medium-rate' : 'low-rate');
                        ?>
                        <tr>
                            <td class="dentist-name"><?= htmlspecialchars($dentist['dentist_name']); ?></td>
                            <td>
                                <span class="specialty-badge">
                                    <?= htmlspecialchars($dentist['specialty'] ?: 'General'); ?>
                                </span>
                            </td>
                            <td><?= $dentist['total_appointments']; ?></td>
                            <td><?= $dentist['completed_appointments']; ?></td>
                            <td class="completion-rate <?= $rate_class; ?>"><?= $completion_rate; ?>%</td>
                            <td class="revenue-value">$<?= number_format($dentist['revenue_generated'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Daily Trends Chart -->
    <div class="trends-card">
        <div class="trends-header">
            <h3 class="trends-title">
                <span>📅</span>
                Daily Appointment Trends (Last 30 Days)
            </h3>
        </div>
        
        <div class="trends-content">
            <table class="productivity-table">
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
                        <tr>
                            <td colspan="4" class="empty-state">
                                <div class="empty-icon">📊</div>
                                <div>No appointment data available.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($day = $daily_trends->fetch_assoc()): ?>
                            <tr>
                                <td class="dentist-name"><?= date('M d', strtotime($day['appointment_date'])); ?></td>
                                <td><?= $day['appointment_count']; ?></td>
                                <td><?= $day['completed_count']; ?></td>
                                <td>
                                    <?php 
                                    $bar_width = min(($day['appointment_count'] * 10), 100);
                                    ?>
                                    <div class="trend-bar" style="width: <?= $bar_width; ?>px;"></div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Export Options -->
    <div class="export-section">
        <h3 class="export-title">
            <span>📤</span>
            Export Options
        </h3>
        <div class="export-actions">
            <a href="?export=pdf&start_date=<?= $start_date; ?>&end_date=<?= $end_date; ?>" 
               class="btn btn-primary" target="_blank" title="Open PDF report in new window">
                <span>📄</span>
                Export as PDF
            </a>
            <a href="export_operational.php?format=csv&start_date=<?= $start_date; ?>&end_date=<?= $end_date; ?>" 
               class="btn btn-secondary" title="Download CSV file">
                <span>📊</span>
                Export as CSV
            </a>
            <a href="export_operational.php?format=json&start_date=<?= $start_date; ?>&end_date=<?= $end_date; ?>" 
               class="btn btn-outline" style="background: rgba(124, 58, 237, 0.1); color: #7c3aed; border: 2px solid rgba(124, 58, 237, 0.2);" 
               title="Download JSON data">
                <span>📋</span>
                Export as JSON
            </a>
        </div>
        <div style="margin-top: 1rem; font-size: 0.875rem; color: #64748b; text-align: center;">
            📅 Current period: <?= date('M j, Y', strtotime($start_date)); ?> - <?= date('M j, Y', strtotime($end_date)); ?>
        </div>
    </div>
</main>

<?php include BASE_PATH . '/templates/footer.php'; ?>
