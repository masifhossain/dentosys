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
if (!empty($_GET['user'])) {
    $uid = intval($_GET['user']);
    $where[] = 'a.user_id = ?';
    $params[] = $uid;
    $types   .= 'i';
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

$whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* --------------------------------------------------------------
 * 2. Prepare query
 * ------------------------------------------------------------ */
$query  = "SELECT a.*, u.email
           FROM AuditLog a
           LEFT JOIN UserTbl u ON u.user_id = a.user_id
           $whereSQL
           ORDER BY a.timestamp DESC
           LIMIT 500";        // safety cap

$stmt = $conn->prepare($query);
if ($types !== '') {
    /* bind dynamic params */
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result();

/* --------------------------------------------------------------
 * 3. Fetch users for dropdown
 * ------------------------------------------------------------ */
$usersDDL = $conn->query(
    "SELECT user_id, email FROM UserTbl ORDER BY email"
);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Audit Log</h2>
  <?= get_flash(); ?>

  <!-- Filter bar -->
  <form method="get" style="margin-bottom:14px;">
    <label>User:
      <select name="user">
        <option value="">All users</option>
        <?php while ($u = $usersDDL->fetch_assoc()): ?>
          <option value="<?= $u['user_id']; ?>"
            <?= (!empty($_GET['user']) && $_GET['user']==$u['user_id'])?'selected':''; ?>>
            <?= htmlspecialchars($u['email']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    </label>

    <label>From:
      <input type="date" name="from" value="<?= htmlspecialchars($start_date); ?>">
    </label>

    <label>To:
      <input type="date" name="to"   value="<?= htmlspecialchars($end_date); ?>">
    </label>

    <button type="submit">Apply</button>
    <a class="btn" href="audit_log.php">Reset</a>
  </form>

  <!-- Log table -->
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Timestamp</th>
        <th>User</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($logs->num_rows === 0): ?>
      <tr><td colspan="4">No log entries found.</td></tr>
    <?php else: $n=1; while ($row = $logs->fetch_assoc()): ?>
      <tr>
        <td><?= $n++; ?></td>
        <td><?= $row['timestamp']; ?></td>
        <td><?= htmlspecialchars($row['email'] ?? '— system —'); ?></td>
        <td><?= nl2br(htmlspecialchars($row['action'])); ?></td>
      </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>