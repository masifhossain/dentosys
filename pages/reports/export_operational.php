<?php
/**
 * export_operational.php
 * Dedicated export handler for operational reports
 */

require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

// Check permissions
if (!is_admin() && ($_SESSION['role'] ?? 0) !== 2) {
    flash('You do not have permission to export operational reports.');
    redirect('/dentosys/index.php');
}

// Get parameters
$format = $_GET['format'] ?? 'csv';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Validate format
if (!in_array($format, ['csv', 'json', 'excel'])) {
    flash('Invalid export format.', 'error');
    redirect('/dentosys/pages/reports/operational.php');
}

// Build filtering conditions for dentists (align with operational.php)
$dentist_filter = '';
if (is_dentist()) {
    $current_dentist_id = get_current_dentist_id();
    if ($current_dentist_id) {
        $dentist_filter = "AND a.dentist_id = $current_dentist_id";
    } else {
        $dentist_filter = "AND 1 = 0"; // Show no data if dentist not found
    }
}

// Re-run the queries to get fresh data
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

if (is_dentist()) {
    // For dentists, only show metrics for their assigned patients
    $patient_ids = get_dentist_patient_ids();
    if (empty($patient_ids)) {
        $patient_metrics = ['active_patients' => 0, 'recent_patients' => 0, 'new_patients' => 0];
    } else {
        $patient_ids_str = implode(',', array_map('intval', $patient_ids));
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

// Treatment Types (mirror operational.php)
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

// Insurance Claims Summary (mirror operational.php)
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
        $insurance_data = $insurance_summary ? $insurance_summary->fetch_assoc() : null;
        if (!$insurance_data) {
            $insurance_data = [
                'total_claims' => 0,
                'total_claimed' => 0,
                'approved_amount' => 0,
                'paid_amount' => 0,
            ];
        }
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
    $insurance_data = $insurance_summary ? $insurance_summary->fetch_assoc() : null;
    if (!$insurance_data) {
        $insurance_data = [
            'total_claims' => 0,
            'total_claimed' => 0,
            'approved_amount' => 0,
            'paid_amount' => 0,
        ];
    }
}

// Log export activity
$export_details = "Operational report exported in $format format for period: $start_date to $end_date";
log_export_event('Operational Report', $export_details);

if ($format === 'csv') {
    // CSV Export
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="operational_report_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Report Header
    fputcsv($output, ['DentoSys Operational Report']);
    fputcsv($output, ['Generated on', date('Y-m-d H:i:s')]);
    fputcsv($output, ['Period', "$start_date to $end_date"]);
    fputcsv($output, ['Exported by', $_SESSION['email'] ?? 'Unknown']);
    fputcsv($output, []); // Empty row
    
    // Key Metrics
    fputcsv($output, ['KEY PERFORMANCE INDICATORS']);
    fputcsv($output, ['Metric', 'Value', 'Notes']);
    fputcsv($output, ['Total Appointments', $appointment_metrics['total_appointments'], 'All appointments in period']);
    fputcsv($output, ['Completed Appointments', $appointment_metrics['completed_appointments'], 'Approved status']);
    fputcsv($output, ['Cancelled Appointments', $appointment_metrics['cancelled_appointments'], 'Cancelled status']);
    fputcsv($output, ['Pending Appointments', $appointment_metrics['pending_appointments'], 'Pending status']);
    fputcsv($output, ['Completion Rate (%)', $appointment_metrics['completion_rate'], 'Percentage of completed appointments']);
    fputcsv($output, ['Active Patients', $patient_metrics['active_patients'], 'Patients with appointments']);
    fputcsv($output, ['New Patients (Last 30 days)', $patient_metrics['new_patients'], 'Recently registered patients']);
    fputcsv($output, ['Total Revenue', number_format($financial_metrics['total_revenue'] ?? 0, 2), 'USD']);
    fputcsv($output, ['Collected Revenue', number_format($financial_metrics['collected_revenue'] ?? 0, 2), 'USD - Paid invoices']);
    fputcsv($output, ['Outstanding Revenue', number_format($financial_metrics['outstanding_revenue'] ?? 0, 2), 'USD - Unpaid invoices']);
    fputcsv($output, ['Average Invoice Amount', number_format($financial_metrics['avg_invoice_amount'] ?? 0, 2), 'USD']);
    fputcsv($output, []); // Empty row
    
    // Dentist Productivity
    fputcsv($output, ['DENTIST PRODUCTIVITY']);
    fputcsv($output, ['Dentist', 'Specialty', 'Total Appointments', 'Completed', 'Completion Rate (%)', 'Revenue Generated (USD)']);
    
    while ($dentist = $dentist_productivity->fetch_assoc()) {
        $completion_rate = $dentist['total_appointments'] > 0 ? 
            round(($dentist['completed_appointments'] / $dentist['total_appointments']) * 100, 1) : 0;
        
        fputcsv($output, [
            $dentist['dentist_name'],
            $dentist['specialty'] ?: 'General',
            $dentist['total_appointments'],
            $dentist['completed_appointments'],
            $completion_rate,
            number_format($dentist['revenue_generated'], 2)
        ]);
    }
    fputcsv($output, []); // Empty row
    
    // Daily Trends
    fputcsv($output, ['DAILY APPOINTMENT TRENDS (Last 30 Days)']);
    fputcsv($output, ['Date', 'Total Appointments', 'Completed', 'Completion Rate (%)']);
    
    while ($day = $daily_trends->fetch_assoc()) {
        $daily_completion_rate = $day['appointment_count'] > 0 ? 
            round(($day['completed_count'] / $day['appointment_count']) * 100, 1) : 0;
            
        fputcsv($output, [
            $day['appointment_date'],
            $day['appointment_count'],
            $day['completed_count'],
            $daily_completion_rate
        ]);
    }
    fputcsv($output, []); // Empty row

    // Treatment Types
    fputcsv($output, ['TREATMENT TYPES']);
    fputcsv($output, ['Treatment Type', 'Count']);
    if ($treatment_types) {
        // No need to data_seek as it's a fresh result
        while ($row = $treatment_types->fetch_assoc()) {
            fputcsv($output, [$row['treatment_type'], $row['count']]);
        }
    }
    fputcsv($output, []); // Empty row

    // Insurance Claims Summary
    fputcsv($output, ['INSURANCE CLAIMS SUMMARY']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Claims', $insurance_data['total_claims'] ?? 0]);
    fputcsv($output, ['Total Claimed (USD)', number_format($insurance_data['total_claimed'] ?? 0, 2)]);
    fputcsv($output, ['Approved Amount (USD)', number_format($insurance_data['approved_amount'] ?? 0, 2)]);
    fputcsv($output, ['Paid Amount (USD)', number_format($insurance_data['paid_amount'] ?? 0, 2)]);
    
    fclose($output);
    exit;
    
} elseif ($format === 'json') {
    // JSON Export
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="operational_report_' . date('Y-m-d_H-i-s') . '.json"');
    
    $data = [
        'report_info' => [
            'title' => 'DentoSys Operational Report',
            'generated_on' => date('Y-m-d H:i:s'),
            'period' => [
                'start_date' => $start_date,
                'end_date' => $end_date
            ],
            'exported_by' => $_SESSION['email'] ?? 'Unknown',
            'format' => 'JSON'
        ],
        'key_metrics' => [
            'appointments' => $appointment_metrics,
            'patients' => $patient_metrics,
            'financial' => $financial_metrics
        ],
        'dentist_productivity' => [],
    'daily_trends' => [],
    'treatment_types' => [],
    'insurance_summary' => []
    ];
    
    // Add dentist productivity data
    $dentist_productivity->data_seek(0);
    while ($dentist = $dentist_productivity->fetch_assoc()) {
        $completion_rate = $dentist['total_appointments'] > 0 ? 
            round(($dentist['completed_appointments'] / $dentist['total_appointments']) * 100, 1) : 0;
        
        $dentist['completion_rate'] = $completion_rate;
        $data['dentist_productivity'][] = $dentist;
    }
    
    // Add daily trends data
    $daily_trends->data_seek(0);
    while ($day = $daily_trends->fetch_assoc()) {
        $daily_completion_rate = $day['appointment_count'] > 0 ? 
            round(($day['completed_count'] / $day['appointment_count']) * 100, 1) : 0;
        
        $day['completion_rate'] = $daily_completion_rate;
        $data['daily_trends'][] = $day;
    }
    
    // Add treatment types
    if ($treatment_types) {
        $treatment_types->data_seek(0);
        while ($row = $treatment_types->fetch_assoc()) {
            $data['treatment_types'][] = $row;
        }
    }
    
    // Add insurance summary
    $data['insurance_summary'] = $insurance_data;
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
    
} else {
    flash('Export format not yet implemented.', 'warning');
    redirect('/dentosys/pages/reports/operational.php');
}
?>
