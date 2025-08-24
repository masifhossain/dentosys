<?php
/**
 * export_audit_log.php
 * Export audit log data in various formats
 */

require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// Check authentication
require_login();
require_admin();

$format = $_GET['format'] ?? 'csv';
$user_filter = $_GET['user'] ?? '';
$action_type_filter = $_GET['action_type'] ?? '';
$table_filter = $_GET['table_name'] ?? '';
$severity_filter = $_GET['severity'] ?? '';
$start_date = $_GET['from'] ?? '';
$end_date = $_GET['to'] ?? '';
$search = $_GET['search'] ?? '';

// Build WHERE clause
$where = [];
$params = [];
$types = '';

if (!empty($user_filter)) {
    $where[] = 'a.user_id = ?';
    $params[] = intval($user_filter);
    $types .= 'i';
}

if (!empty($action_type_filter)) {
    $where[] = 'a.action_type = ?';
    $params[] = $action_type_filter;
    $types .= 's';
}

if (!empty($table_filter)) {
    $where[] = 'a.table_name = ?';
    $params[] = $table_filter;
    $types .= 's';
}

if (!empty($severity_filter)) {
    $where[] = 'a.severity = ?';
    $params[] = $severity_filter;
    $types .= 's';
}

if ($start_date !== '') {
    $where[] = 'a.timestamp >= ?';
    $params[] = $start_date . ' 00:00:00';
    $types .= 's';
}

if ($end_date !== '') {
    $where[] = 'a.timestamp <= ?';
    $params[] = $end_date . ' 23:59:59';
    $types .= 's';
}

if (!empty($search)) {
    $where[] = '(a.action LIKE ? OR a.details LIKE ? OR u.email LIKE ?)';
    $search_term = '%' . $search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

$whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Fetch data
$query = "SELECT 
    a.log_id,
    a.timestamp,
    COALESCE(CONCAT(u.first_name, ' ', u.last_name), u.email, 'System') as user_name,
    u.email as user_email,
    r.role_name,
    a.action,
    a.action_type,
    a.table_name,
    a.record_id,
    a.details,
    a.ip_address,
    a.session_id,
    a.severity
FROM auditlog a
LEFT JOIN usertbl u ON u.user_id = a.user_id
LEFT JOIN role r ON r.role_id = u.role_id
$whereSQL
ORDER BY a.timestamp DESC
LIMIT 10000"; // Limit to prevent memory issues

$stmt = $conn->prepare($query);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Log the export activity
$filter_details = [];
if ($user_filter) $filter_details[] = "User: $user_filter";
if ($action_type_filter) $filter_details[] = "Type: $action_type_filter";
if ($table_filter) $filter_details[] = "Table: $table_filter";
if ($severity_filter) $filter_details[] = "Severity: $severity_filter";
if ($start_date) $filter_details[] = "From: $start_date";
if ($end_date) $filter_details[] = "To: $end_date";
if ($search) $filter_details[] = "Search: $search";

$export_details = 'Exported ' . $result->num_rows . ' audit log entries';
if (!empty($filter_details)) {
    $export_details .= ' with filters: ' . implode(', ', $filter_details);
}

log_export_event('Audit Log', $export_details);

if ($format === 'csv') {
    // CSV Export
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Headers
    fputcsv($output, [
        'Log ID',
        'Timestamp',
        'User Name',
        'User Email',
        'Role',
        'Action',
        'Action Type',
        'Table Name',
        'Record ID',
        'Details',
        'IP Address',
        'Session ID',
        'Severity'
    ]);
    
    // Data
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['log_id'],
            $row['timestamp'],
            $row['user_name'],
            $row['user_email'],
            $row['role_name'],
            $row['action'],
            $row['action_type'],
            $row['table_name'],
            $row['record_id'],
            $row['details'],
            $row['ip_address'],
            $row['session_id'],
            $row['severity']
        ]);
    }
    
    fclose($output);
    exit;
    
} elseif ($format === 'json') {
    // JSON Export
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d_H-i-s') . '.json"');
    
    $data = [
        'export_info' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'exported_by' => $_SESSION['email'] ?? 'Unknown',
            'total_records' => $result->num_rows,
            'filters_applied' => $filter_details
        ],
        'audit_logs' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        $data['audit_logs'][] = $row;
    }
    
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
    
} else {
    // Unsupported format
    flash('Unsupported export format', 'error');
    redirect('/dentosys/pages/reports/audit_log.php');
}
?>
