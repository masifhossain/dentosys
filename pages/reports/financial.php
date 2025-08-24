<?php
/*****************************************************************
 * pages/reports/financial.php
 * ---------------------------------------------------------------
 * Financial dashboard – revenue KPIs + 12-month trend.
 * Admin-only access (role_id = 1).  Uses the Invoice + Payment tables.
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // 2 levels up
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* Access: Admin or Dentist (scoped) */
if (!is_admin() && !is_dentist()) {
    flash('Financial reports are restricted.');
    redirect('/dentosys/index.php');
}

/* --------------------------------------------------------------
 * 1. Optional date-range filter
 * ------------------------------------------------------------ */
$where = '';
$params = [];
$types  = '';

$start = $_GET['from'] ?? '';
$end   = $_GET['to']   ?? '';

if ($start !== '') {
    $where .= ' AND issued_date >= ?';
    $params[] = $start;
    $types   .= 's';
}
if ($end !== '') {
    $where .= ' AND issued_date <= ?';
    $params[] = $end;
    $types   .= 's';
}

/* --------------------------------------------------------------
 * 2. KPI queries
 * ------------------------------------------------------------ */
$patientScope = '';
if (is_dentist()) {
    $pids = get_dentist_patient_ids();
    if (!$pids) { $pids = []; }
    if (count($pids) === 0) {
        $patientScope = ' AND 1=0';
    } else {
        $patientScope = ' AND patient_id IN (' . implode(',', array_map('intval', $pids)) . ')';
    }
}

$kpiSQL =
  "SELECT
      (SELECT COALESCE(SUM(total_amount),0) FROM Invoice
       WHERE status='Paid'   $where $patientScope) AS paid_total,
      (SELECT COUNT(*) FROM Invoice
       WHERE status='Paid'   $where $patientScope) AS paid_count,
      (SELECT COALESCE(SUM(total_amount),0) FROM Invoice
       WHERE status='Unpaid' $where $patientScope) AS outstanding_total";

$stmt = $conn->prepare($kpiSQL);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$kpi = $stmt->get_result()->fetch_assoc();

/* Average invoice (paid only) */
$avgInv = $kpi['paid_count'] ? $kpi['paid_total'] / $kpi['paid_count'] : 0;

/* --------------------------------------------------------------
 * 3. 12-month revenue trend
 * ------------------------------------------------------------ */
$trendSQL =
    "SELECT DATE_FORMAT(issued_date,'%Y-%m') AS ym,
                    SUM(total_amount) AS revenue
     FROM Invoice
     WHERE status='Paid' $where $patientScope
     GROUP BY ym
     ORDER BY ym DESC
     LIMIT 12";
$stmtTrend = $conn->prepare($trendSQL);
if ($types) $stmtTrend->bind_param($types, ...$params);
$stmtTrend->execute();
$trendRes = $stmtTrend->get_result();

/* Put into array in descending order then reverse for chronological */
$trend = [];
while ($row = $trendRes->fetch_assoc()) $trend[] = $row;
$trend = array_reverse($trend);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>

<style>
.financial-main {
    padding: 0 2rem 3rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    min-height: 100vh;
}

.page-header {
    background: linear-gradient(135deg, #059669, #047857);
    margin: 0 -2rem 2rem;
    padding: 2rem 2rem 2.5rem;
    color: white;
    border-radius: 0 0 24px 24px;
    box-shadow: 0 8px 32px -8px rgba(5, 150, 105, 0.3);
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
    border-color: #059669;
    outline: none;
    background: white;
    box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
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
    background: linear-gradient(135deg, #059669, #047857);
    color: white;
    box-shadow: 0 4px 12px -4px rgba(5, 150, 105, 0.4);
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px -4px rgba(5, 150, 105, 0.6);
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
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.kpi-card {
    background: white;
    padding: 2rem;
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
    background: linear-gradient(90deg, #059669, #047857);
}

.kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px -8px rgba(0,0,0,0.15);
}

.kpi-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 1rem;
}

.kpi-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #059669;
    margin-bottom: 0.5rem;
}

.kpi-label {
    color: #64748b;
    font-size: 1rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.revenue-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
}

.revenue-header {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.revenue-title {
    margin: 0;
    color: #1e293b;
    font-size: 1.25rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
}

.modern-table th {
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

.modern-table td {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.modern-table tr:hover {
    background: #f8fafc;
}

.month-cell {
    font-weight: 600;
    color: #374151;
}

.revenue-cell {
    text-align: right;
    font-weight: 700;
    color: #059669;
    font-size: 1.1rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .financial-main { padding: 0 1rem 2rem; }
    .page-header { margin: 0 -1rem 1.5rem; padding: 1.5rem 1rem 2rem; }
    .header-content { flex-direction: column; align-items: stretch; text-align: center; }
    .filters-grid { grid-template-columns: 1fr; }
    .filter-actions { flex-direction: column; }
    .kpi-grid { grid-template-columns: 1fr; }
    .modern-table { font-size: 0.8rem; }
    .kpi-value { font-size: 2rem; }
}
</style>

<main class="financial-main">
    <div class="page-header">
        <div class="header-content">
            <div class="title-section">
                <div class="icon-wrapper">📊</div>
                <div>
                    <h1>Financial Reports</h1>
                    <p class="subtitle">Revenue analytics and financial insights</p>
                </div>
            </div>
            <div class="header-actions">
                <a class="btn btn-outline" href="operational.php">
                    <span>📊</span>
                    Operational Metrics
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

    <!-- Date Filter -->
    <div class="filters-card">
        <form method="get">
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">From Date</label>
                    <input type="date" name="from" value="<?= htmlspecialchars($start); ?>" class="filter-input">
                </div>

                <div class="filter-group">
                    <label class="filter-label">To Date</label>
                    <input type="date" name="to" value="<?= htmlspecialchars($end); ?>" class="filter-input">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <span>🔍</span>
                        Apply Filter
                    </button>
                    <a class="btn btn-secondary" href="financial.php">
                        <span>🔄</span>
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon">💰</div>
            <div class="kpi-value">$<?= number_format($kpi['paid_total'],2); ?></div>
            <div class="kpi-label">Total Paid Revenue</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon">📄</div>
            <div class="kpi-value">$<?= number_format($avgInv,2); ?></div>
            <div class="kpi-label">Average Invoice</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon">⏳</div>
            <div class="kpi-value">$<?= number_format($kpi['outstanding_total'],2); ?></div>
            <div class="kpi-label">Outstanding Balance</div>
        </div>
    </div>

    <!-- Revenue Trend Table -->
    <div class="revenue-card">
        <div class="revenue-header">
            <h3 class="revenue-title">
                <span>📈</span>
                Revenue Trend - Last 12 Months
            </h3>
        </div>

        <table class="modern-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th style="text-align: right;">Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$trend): ?>
                    <tr>
                        <td colspan="2" class="empty-state">
                            <div class="empty-icon">📊</div>
                            <div>No revenue data available for the selected period</div>
                        </td>
                    </tr>
                <?php else: foreach ($trend as $t): ?>
                    <tr>
                        <td class="month-cell">
                            <?= date('F Y', strtotime($t['ym'] . '-01')); ?>
                        </td>
                        <td class="revenue-cell">
                            $<?= number_format($t['revenue'],2); ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>