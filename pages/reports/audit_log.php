<?php
/*****************************************************************
 * pages/reports/audit_log.php
 * ---------------------------------------------------------------
 * System-wide Audit Log
 *  • Admin-only access (role_id = 1)
 *  • Filter by user and/or date range
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // …/pages/reports → up 2
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Restrict to Admins only ───────── */
if (!is_admin()) {
    flash('Audit Log is restricted to administrators.');
    redirect('/dentosys/index.php');
}

/* --------------------------------------------------------------
 * 1. Build WHERE clause from filters
 * ------------------------------------------------------------ */
$where   = [];
$params  = [];      // for prepared stmt
$types   = '';      // bind_param types

/* User filter */
$user_filter = $_GET['user'] ?? '';
if (!empty($user_filter)) {
    $uid = intval($user_filter);
    $where[] = 'a.user_id = ?';
    $params[] = $uid;
    $types   .= 'i';
}

/* Action type filter */
$action_type_filter = $_GET['action_type'] ?? '';
if (!empty($action_type_filter)) {
    $where[] = 'a.action_type = ?';
    $params[] = $action_type_filter;
    $types   .= 's';
}

/* Table name filter */
$table_filter = $_GET['table_name'] ?? '';
if (!empty($table_filter)) {
    $where[] = 'a.table_name = ?';
    $params[] = $table_filter;
    $types   .= 's';
}

/* Severity filter */
$severity_filter = $_GET['severity'] ?? '';
if (!empty($severity_filter)) {
    $where[] = 'a.severity = ?';
    $params[] = $severity_filter;
    $types   .= 's';
}

/* Date range filter */
$start_date = $_GET['from'] ?? '';
$end_date   = $_GET['to']   ?? '';

if ($start_date !== '') {
    $where[]  = 'a.timestamp >= ?';
    $params[] = $start_date . ' 00:00:00';
    $types   .= 's';
}
if ($end_date !== '') {
    $where[]  = 'a.timestamp <= ?';
    $params[] = $end_date . ' 23:59:59';
    $types   .= 's';
}

/* Search filter */
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $where[] = '(a.action LIKE ? OR a.details LIKE ? OR u.email LIKE ?)';
    $search_term = '%' . $search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types   .= 'sss';
}

$whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* --------------------------------------------------------------
 * 2. Get summary statistics
 * ------------------------------------------------------------ */
$stats_query = "SELECT 
    COUNT(*) as total_entries,
    COUNT(DISTINCT a.user_id) as unique_users,
    COUNT(CASE WHEN a.severity = 'CRITICAL' THEN 1 END) as critical_events,
    COUNT(CASE WHEN a.severity = 'HIGH' THEN 1 END) as high_events,
    COUNT(CASE WHEN a.severity = 'MEDIUM' THEN 1 END) as medium_events,
    COUNT(CASE WHEN a.severity = 'LOW' THEN 1 END) as low_events,
    COUNT(CASE WHEN a.action_type = 'LOGIN_FAILED' THEN 1 END) as failed_logins,
    COUNT(CASE WHEN a.timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as last_24h
FROM auditlog a
LEFT JOIN usertbl u ON u.user_id = a.user_id
$whereSQL";

$stats_stmt = $conn->prepare($stats_query);
if ($types !== '') {
    $stats_stmt->bind_param($types, ...$params);
}
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

/* --------------------------------------------------------------
 * 3. Get action type breakdown
 * ------------------------------------------------------------ */
$action_types_query = "SELECT 
    COALESCE(a.action_type, 'GENERAL') as action_type,
    COUNT(*) as count
FROM auditlog a
LEFT JOIN usertbl u ON u.user_id = a.user_id
$whereSQL
GROUP BY COALESCE(a.action_type, 'GENERAL')
ORDER BY count DESC";

$action_types_stmt = $conn->prepare($action_types_query);
if ($types !== '') {
    $action_types_stmt->bind_param($types, ...$params);
}
$action_types_stmt->execute();
$action_types = $action_types_stmt->get_result();

/* --------------------------------------------------------------
 * 4. Prepare main query with pagination
 * ------------------------------------------------------------ */
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

$query  = "SELECT a.*, u.email, u.first_name, u.last_name, r.role_name
           FROM auditlog a
           LEFT JOIN usertbl u ON u.user_id = a.user_id
           LEFT JOIN role r ON r.role_id = u.role_id
           $whereSQL
           ORDER BY a.timestamp DESC
           LIMIT $per_page OFFSET $offset";

$stmt = $conn->prepare($query);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result();

/* --------------------------------------------------------------
 * 5. Get total count for pagination
 * ------------------------------------------------------------ */
$count_query = "SELECT COUNT(*) as total
                FROM auditlog a
                LEFT JOIN usertbl u ON u.user_id = a.user_id
                $whereSQL";

$count_stmt = $conn->prepare($count_query);
if ($types !== '') {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

/* --------------------------------------------------------------
 * 6. Fetch users for dropdown
 * ------------------------------------------------------------ */
$usersDDL = $conn->query(
    "SELECT u.user_id, u.email, u.first_name, u.last_name, r.role_name 
     FROM UserTbl u 
     LEFT JOIN role r ON r.role_id = u.role_id 
     ORDER BY u.email"
);

/* --------------------------------------------------------------
 * 7. Get unique values for filter dropdowns
 * ------------------------------------------------------------ */
$action_types_ddl = $conn->query("SELECT DISTINCT action_type FROM auditlog WHERE action_type IS NOT NULL ORDER BY action_type");
$tables_ddl = $conn->query("SELECT DISTINCT table_name FROM auditlog WHERE table_name IS NOT NULL ORDER BY table_name");

/* --------------------------------------------------------------
 * 8. Recent high-priority alerts (last 7 days)
 * ------------------------------------------------------------ */
$alerts_query = "SELECT a.*, u.email 
                 FROM auditlog a
                 LEFT JOIN usertbl u ON u.user_id = a.user_id
                 WHERE a.severity IN ('HIGH', 'CRITICAL') 
                 AND a.timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 ORDER BY a.timestamp DESC
                 LIMIT 10";
$alerts = $conn->query($alerts_query);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>

<style>
.audit-main {
    padding: 0 2rem 3rem;
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    min-height: 100vh;
}

.page-header {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    margin: 0 -2rem 2rem;
    padding: 2rem 2rem 2.5rem;
    color: white;
    border-radius: 0 0 24px 24px;
    box-shadow: 0 8px 32px -8px rgba(220, 38, 38, 0.3);
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
    border-color: #dc2626;
    outline: none;
    background: white;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

.filter-actions {
    display: flex;
    gap: 0.75rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 12px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #dc2626, #b91c1c);
}

.stat-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin: 0 auto 1rem;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #dc2626;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #64748b;
    font-size: 0.875rem;
    font-weight: 500;
}

.alerts-section {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
    margin-bottom: 2rem;
}

.alerts-header {
    background: linear-gradient(135deg, #fef3c7, #fed7aa);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f59e0b;
}

.alerts-title {
    margin: 0;
    color: #92400e;
    font-size: 1.1rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-item {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.alert-item:last-child {
    border-bottom: none;
}

.alert-severity {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.severity-critical {
    background: #fca5a5;
    color: #7f1d1d;
}

.severity-high {
    background: #fed7aa;
    color: #9a3412;
}

.alert-content {
    flex: 1;
}

.alert-action {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.25rem;
}

.alert-time {
    color: #6b7280;
    font-size: 0.875rem;
}

.breakdown-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.breakdown-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
}

.breakdown-header {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.breakdown-title {
    margin: 0;
    color: #1e293b;
    font-size: 1rem;
    font-weight: 700;
}

.breakdown-item {
    padding: 0.75rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.breakdown-item:last-child {
    border-bottom: none;
}

.breakdown-label {
    font-weight: 500;
    color: #374151;
}

.breakdown-count {
    background: #f3f4f6;
    color: #374151;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.pagination a, .pagination span {
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
}

.pagination a {
    color: #374151;
    background: white;
    border: 1px solid #d1d5db;
    transition: all 0.2s ease;
}

.pagination a:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.pagination .current {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: white;
    border: 1px solid #dc2626;
}

.search-box {
    width: 100%;
    max-width: 300px;
}

.severity-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.severity-low { background: #d1fae5; color: #065f46; }
.severity-medium { background: #fef3c7; color: #92400e; }
.severity-high { background: #fed7aa; color: #9a3412; }
.severity-critical { background: #fca5a5; color: #7f1d1d; }

.action-type-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 500;
    background: #e2e8f0;
    color: #475569;
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.user-name {
    font-weight: 600;
    color: #374151;
}

.user-role {
    font-size: 0.75rem;
    color: #6b7280;
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
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: white;
    box-shadow: 0 4px 12px -4px rgba(220, 38, 38, 0.4);
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px -4px rgba(220, 38, 38, 0.6);
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

.audit-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
}

.audit-header {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.audit-title {
    margin: 0;
    color: #1e293b;
    font-size: 1.25rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.audit-stats {
    margin-top: 0.75rem;
    color: #64748b;
    font-size: 0.875rem;
}

.audit-table {
    width: 100%;
    border-collapse: collapse;
}

.audit-table th {
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

.audit-table td {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: top;
}

.audit-table tr:hover {
    background: #f8fafc;
}

.row-number {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #dc2626;
    font-weight: 700;
    text-align: center;
    border-radius: 6px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}

.timestamp-cell {
    font-family: 'Courier New', monospace;
    color: #6b7280;
    font-size: 0.875rem;
}

.user-cell {
    font-weight: 600;
    color: #374151;
}

.system-user {
    color: #dc2626;
    font-style: italic;
    font-weight: 500;
}

.action-cell {
    font-size: 0.875rem;
    line-height: 1.5;
    color: #4b5563;
    max-width: 400px;
    word-wrap: break-word;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

.empty-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #374151;
}

.empty-message {
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .audit-main { padding: 0 1rem 2rem; }
    .page-header { margin: 0 -1rem 1.5rem; padding: 1.5rem 1rem 2rem; }
    .header-content { flex-direction: column; align-items: stretch; text-align: center; }
    .filters-grid { grid-template-columns: 1fr; }
    .filter-actions { flex-direction: column; }
    .audit-table { font-size: 0.8rem; }
    .audit-table th, .audit-table td { padding: 0.75rem 1rem; }
    .action-cell { max-width: 200px; }
}
</style>

<main class="audit-main">
    <div class="page-header">
        <div class="header-content">
            <div class="title-section">
                <div class="icon-wrapper">📋</div>
                <div>
                    <h1>Audit Log</h1>
                    <p class="subtitle">System activity and user actions tracking</p>
                </div>
            </div>
            <div class="header-actions">
                <a class="btn btn-outline" href="financial.php">
                    <span>💰</span>
                    Financial Reports
                </a>
                <a class="btn btn-outline" href="operational.php">
                    <span>📈</span>
                    Operational Metrics
                </a>
            </div>
        </div>
    </div>

    <?= get_flash(); ?>

    <!-- Statistics Dashboard -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-value"><?= number_format($stats['total_entries']); ?></div>
            <div class="stat-label">Total Log Entries</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?= $stats['unique_users']; ?></div>
            <div class="stat-label">Active Users</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🔥</div>
            <div class="stat-value"><?= $stats['critical_events'] + $stats['high_events']; ?></div>
            <div class="stat-label">High Priority Events</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🚨</div>
            <div class="stat-value"><?= $stats['failed_logins']; ?></div>
            <div class="stat-label">Failed Login Attempts</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⏰</div>
            <div class="stat-value"><?= $stats['last_24h']; ?></div>
            <div class="stat-label">Last 24 Hours</div>
        </div>
    </div>

    <!-- Recent High Priority Alerts -->
    <?php if ($alerts->num_rows > 0): ?>
    <div class="alerts-section">
        <div class="alerts-header">
            <h3 class="alerts-title">
                <span>⚠️</span>
                Recent High Priority Events (Last 7 Days)
            </h3>
        </div>
        
        <?php while ($alert = $alerts->fetch_assoc()): ?>
            <div class="alert-item">
                <span class="alert-severity severity-<?= strtolower($alert['severity']); ?>">
                    <?= $alert['severity']; ?>
                </span>
                <div class="alert-content">
                    <div class="alert-action"><?= htmlspecialchars($alert['action']); ?></div>
                    <div class="alert-time">
                        <?= date('M d, Y H:i:s', strtotime($alert['timestamp'])); ?> - 
                        <?= $alert['email'] ? htmlspecialchars($alert['email']) : 'System'; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- Activity Breakdown -->
    <div class="breakdown-grid">
        <div class="breakdown-card">
            <div class="breakdown-header">
                <h3 class="breakdown-title">Action Types</h3>
            </div>
            <?php $action_types->data_seek(0); ?>
            <?php if ($action_types->num_rows === 0): ?>
                <div class="breakdown-item">
                    <span class="breakdown-label">No data available</span>
                </div>
            <?php else: ?>
                <?php while ($type = $action_types->fetch_assoc()): ?>
                    <div class="breakdown-item">
                        <span class="breakdown-label"><?= htmlspecialchars($type['action_type']); ?></span>
                        <span class="breakdown-count"><?= $type['count']; ?></span>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <div class="breakdown-card">
            <div class="breakdown-header">
                <h3 class="breakdown-title">Severity Distribution</h3>
            </div>
            <div class="breakdown-item">
                <span class="breakdown-label">Critical</span>
                <span class="breakdown-count"><?= $stats['critical_events']; ?></span>
            </div>
            <div class="breakdown-item">
                <span class="breakdown-label">High</span>
                <span class="breakdown-count"><?= $stats['high_events']; ?></span>
            </div>
            <div class="breakdown-item">
                <span class="breakdown-label">Medium</span>
                <span class="breakdown-count"><?= $stats['medium_events']; ?></span>
            </div>
            <div class="breakdown-item">
                <span class="breakdown-label">Low</span>
                <span class="breakdown-count"><?= $stats['low_events']; ?></span>
            </div>
        </div>
    </div>

    <!-- Advanced Filters -->
    <div class="filters-card">
        <form method="get">
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" 
                           placeholder="Search actions, details, or users..." class="filter-input search-box">
                </div>

                <div class="filter-group">
                    <label class="filter-label">User Filter</label>
                    <select name="user" class="filter-input">
                        <option value="">All users</option>
                        <?php while ($u = $usersDDL->fetch_assoc()): ?>
                            <option value="<?= $u['user_id']; ?>"
                                <?= $user_filter == $u['user_id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($u['email']); ?>
                                <?= $u['role_name'] ? ' (' . htmlspecialchars($u['role_name']) . ')' : ''; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Action Type</label>
                    <select name="action_type" class="filter-input">
                        <option value="">All types</option>
                        <?php while ($at = $action_types_ddl->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($at['action_type']); ?>"
                                <?= $action_type_filter == $at['action_type'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($at['action_type']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Table</label>
                    <select name="table_name" class="filter-input">
                        <option value="">All tables</option>
                        <?php while ($tbl = $tables_ddl->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($tbl['table_name']); ?>"
                                <?= $table_filter == $tbl['table_name'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($tbl['table_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Severity</label>
                    <select name="severity" class="filter-input">
                        <option value="">All levels</option>
                        <option value="CRITICAL" <?= $severity_filter == 'CRITICAL' ? 'selected' : ''; ?>>Critical</option>
                        <option value="HIGH" <?= $severity_filter == 'HIGH' ? 'selected' : ''; ?>>High</option>
                        <option value="MEDIUM" <?= $severity_filter == 'MEDIUM' ? 'selected' : ''; ?>>Medium</option>
                        <option value="LOW" <?= $severity_filter == 'LOW' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">From Date</label>
                    <input type="date" name="from" value="<?= htmlspecialchars($start_date); ?>" class="filter-input">
                </div>

                <div class="filter-group">
                    <label class="filter-label">To Date</label>
                    <input type="date" name="to" value="<?= htmlspecialchars($end_date); ?>" class="filter-input">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <span>🔍</span>
                        Apply Filters
                    </button>
                    <a class="btn btn-secondary" href="audit_log.php">
                        <span>🔄</span>
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Audit Log Table -->
    <div class="audit-card">
        <div class="audit-header">
            <h3 class="audit-title">
                <span>📊</span>
                System Activity Log
            </h3>
            <div class="audit-stats">
                Showing <?= $logs->num_rows; ?> of <?= number_format($total_records); ?> entries
                <?php if ($user_filter || $action_type_filter || $table_filter || $severity_filter || $start_date || $end_date || $search): ?>
                    (filtered)
                <?php endif; ?>
                - Page <?= $page; ?> of <?= $total_pages; ?>
            </div>
        </div>

        <table class="audit-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th style="width: 140px;">Time</th>
                    <th style="width: 180px;">User</th>
                    <th style="width: 80px;">Type</th>
                    <th style="width: 70px;">Severity</th>
                    <th style="width: 100px;">Table</th>
                    <th>Action & Details</th>
                    <th style="width: 100px;">IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logs->num_rows === 0): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <div class="empty-icon">📋</div>
                            <div class="empty-title">No log entries found</div>
                            <div class="empty-message">
                                <?php if ($user_filter || $action_type_filter || $table_filter || $severity_filter || $start_date || $end_date || $search): ?>
                                    Try adjusting your filters to see more results.
                                <?php else: ?>
                                    No system activities have been logged yet.
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $n = $offset + 1; while ($row = $logs->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="row-number"><?= $n++; ?></div>
                            </td>
                            <td class="timestamp-cell">
                                <div><?= date('M d, Y', strtotime($row['timestamp'])); ?></div>
                                <div style="font-size: 0.75rem; color: #9ca3af;">
                                    <?= date('H:i:s', strtotime($row['timestamp'])); ?>
                                </div>
                            </td>
                            <td class="user-cell">
                                <?php if ($row['email']): ?>
                                    <div class="user-info">
                                        <div class="user-name">
                                            <?= htmlspecialchars($row['first_name'] && $row['last_name'] ? 
                                                $row['first_name'] . ' ' . $row['last_name'] : $row['email']); ?>
                                        </div>
                                        <?php if ($row['role_name']): ?>
                                            <div class="user-role"><?= htmlspecialchars($row['role_name']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="system-user">— system —</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['action_type']): ?>
                                    <span class="action-type-badge">
                                        <?= htmlspecialchars($row['action_type']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['severity']): ?>
                                    <span class="severity-badge severity-<?= strtolower($row['severity']); ?>">
                                        <?= $row['severity']; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['table_name']): ?>
                                    <span style="font-family: monospace; font-size: 0.8rem; color: #6b7280;">
                                        <?= htmlspecialchars($row['table_name']); ?>
                                    </span>
                                    <?php if ($row['record_id']): ?>
                                        <div style="font-size: 0.75rem; color: #9ca3af;">
                                            ID: <?= $row['record_id']; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="action-cell">
                                <div style="font-weight: 600; margin-bottom: 0.25rem;">
                                    <?= htmlspecialchars($row['action']); ?>
                                </div>
                                <?php if ($row['details']): ?>
                                    <div style="font-size: 0.8rem; color: #6b7280;">
                                        <?= nl2br(htmlspecialchars($row['details'])); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="font-family: monospace; font-size: 0.8rem; color: #6b7280;">
                                <?= $row['ip_address'] ?: '—'; ?>
                                <?php if ($row['session_id']): ?>
                                    <div style="font-size: 0.7rem; color: #9ca3af; margin-top: 0.25rem;">
                                        Session: <?= substr($row['session_id'], 0, 8); ?>...
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">« Prev</a>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1) {
                    echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a>';
                    if ($start_page > 2) echo '<span>...</span>';
                }
                
                for ($i = $start_page; $i <= $end_page; $i++) {
                    if ($i == $page) {
                        echo '<span class="current">' . $i . '</span>';
                    } else {
                        echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . '">' . $i . '</a>';
                    }
                }
                
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) echo '<span>...</span>';
                    echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '">' . $total_pages . '</a>';
                }
                ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Export Options -->
    <div class="export-section" style="background: white; border-radius: 16px; padding: 2rem; text-align: center; box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;">
        <h3 style="margin: 0 0 1.5rem; color: #1e293b; font-size: 1.25rem; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 0.75rem;">
            <span>📤</span>
            Export Audit Log
        </h3>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="export_audit_log.php?format=csv&<?= http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>" 
               class="btn btn-primary">
                <span>📊</span>
                Export as CSV
            </a>
            <a href="export_audit_log.php?format=json&<?= http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>" 
               class="btn btn-secondary">
                <span>📋</span>
                Export as JSON
            </a>
            <a href="audit_demo.php" class="btn btn-outline" style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9; border: 2px solid rgba(14, 165, 233, 0.2);">
                <span>🧪</span>
                Demo Features
            </a>
        </div>
    </div>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>