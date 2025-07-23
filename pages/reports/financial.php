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

/* ───────── Admin-only ───────── */
if (!is_admin()) {
    flash('Financial reports are restricted to administrators.');
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
$kpiSQL =
  "SELECT
      (SELECT COALESCE(SUM(total_amount),0) FROM Invoice
       WHERE status='Paid'   $where) AS paid_total,
      (SELECT COUNT(*) FROM Invoice
       WHERE status='Paid'   $where) AS paid_count,
      (SELECT COALESCE(SUM(total_amount),0) FROM Invoice
       WHERE status='Unpaid' $where) AS outstanding_total";

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
   WHERE status='Paid' $where
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
<main>
  <h2>Financial Report</h2>
  <?= get_flash(); ?>

  <!-- Date filter -->
  <form method="get" style="margin-bottom:14px;">
    <label>From:
      <input type="date" name="from" value="<?= htmlspecialchars($start); ?>">
    </label>
    <label>To:
      <input type="date" name="to"   value="<?= htmlspecialchars($end); ?>">
    </label>
    <button type="submit">Apply</button>
    <a class="btn" href="financial.php">Reset</a>
  </form>

  <!-- KPI cards -->
  <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:18px;">
    <div class="card">
      <h3>Paid Revenue</h3>
      <p>$<?= number_format($kpi['paid_total'],2); ?></p>
    </div>
    <div class="card">
      <h3>Avg Invoice (Paid)</h3>
      <p>$<?= number_format($avgInv,2); ?></p>
    </div>
    <div class="card">
      <h3>Outstanding Balances</h3>
      <p>$<?= number_format($kpi['outstanding_total'],2); ?></p>
    </div>
  </div>

  <!-- 12-month table -->
  <h3>Revenue – Last 12&nbsp;Months</h3>
  <table>
    <thead>
      <tr><th>Month</th><th style="text-align:right;">Revenue ($)</th></tr>
    </thead>
    <tbody>
      <?php if (!$trend): ?>
        <tr><td colspan="2">No data.</td></tr>
      <?php else: foreach ($trend as $t): ?>
        <tr>
          <td><?= $t['ym']; ?></td>
          <td style="text-align:right;"><?= number_format($t['revenue'],2); ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>