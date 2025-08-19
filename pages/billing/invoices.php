<?php
/*****************************************************************
 * pages/billing/invoices.php
 * ---------------------------------------------------------------
 * View, filter, and mark invoices Paid / Unpaid
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // …/pages/billing/ -> up 2
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── OPTIONAL ROLE RESTRICTION ─────────
 * Allow only Admin (role_id = 1) or Receptionist (role_id = 3)
 * Comment out if you want everyone to access.
 */
if (!is_admin() && ($_SESSION['role'] ?? 0) !== 3) {
    flash('You do not have permission to manage invoices.');
    redirect('/dentosys/index.php');
}

/* ───────── Handle status toggle ───────── */
if (isset($_GET['toggle'], $_GET['id'])) {
    $id     = intval($_GET['id']);
    $newSta = ($_GET['toggle'] === 'pay') ? 'Paid' : 'Unpaid';

    $stmt = $conn->prepare(
        "UPDATE Invoice SET status = ? WHERE invoice_id = ? LIMIT 1"
    );
    $stmt->bind_param('si', $newSta, $id);
    if ($stmt->execute()) {
        flash("Invoice #$id marked $newSta.");
    } else {
        flash('DB error: ' . $conn->error);
    }
    redirect('invoices.php');
}

/* ───────── Filters ───────── */
$whereParts = [];
if (!empty($_GET['patient'])) {
    $pid = intval($_GET['patient']);
    $whereParts[] = "i.patient_id = $pid";
}
if (!empty($_GET['status'])) {
    $sta = $conn->real_escape_string($_GET['status']);
    $whereParts[] = "i.status = '$sta'";
}
$whereSQL = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

/* ───────── Query invoices ───────── */
$sql = "SELECT i.*, CONCAT(p.first_name,' ',p.last_name) AS patient
        FROM Invoice i
        JOIN Patient p ON p.patient_id = i.patient_id
        $whereSQL
        ORDER BY issued_date DESC";
$invoices = $conn->query($sql);

/* Patients for dropdown */
$patients = get_patients($conn);

/* ───────── HTML ───────── */
include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Invoices</h2>
  <?= get_flash(); ?>

  <!-- Filter form -->
  <form method="get" style="margin-bottom:12px;">
    <label>Patient:
      <select name="patient">
        <option value="">-- All --</option>
        <?php while ($p = $patients->fetch_assoc()): ?>
          <option value="<?= $p['patient_id']; ?>"
            <?= (!empty($_GET['patient']) && $_GET['patient'] == $p['patient_id']) ? 'selected' : ''; ?>>
            <?= htmlspecialchars($p['name']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    </label>

    <label>Status:
      <select name="status">
        <option value="">-- Any --</option>
        <?php foreach (['Paid','Unpaid'] as $s): ?>
          <option value="<?= $s; ?>"
            <?= (!empty($_GET['status']) && $_GET['status'] == $s) ? 'selected' : ''; ?>>
            <?= $s; ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <button type="submit">Apply</button>
    <a class="btn" href="invoices.php">Reset</a>
    <a class="btn btn-primary" style="float:right; margin-left:10px;" href="create_invoice.php">➕ Create Invoice</a>
    <a class="btn" style="float:right; margin-left:10px;" href="payments.php">💳 Payments</a>
    <a class="btn" style="float:right;" href="insurance.php">🏥 Insurance Claims</a>
  </form>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Issued</th>
        <th>Patient</th>
        <th>Description</th>
        <th style="text-align:right;">Total&nbsp;($)</th>
        <th>Status</th>
        <th style="width:180px;">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($invoices->num_rows === 0): ?>
      <tr><td colspan="7">No invoices found.</td></tr>
    <?php else: $i = 1; while ($inv = $invoices->fetch_assoc()): ?>
      <tr>
        <td><?= $i++; ?></td>
        <td><?= $inv['issued_date']; ?></td>
        <td><?= htmlspecialchars($inv['patient']); ?></td>
        <td style="max-width: 200px;">
            <?= htmlspecialchars(substr($inv['description'] ?? 'No description', 0, 50)); ?>
            <?= strlen($inv['description'] ?? '') > 50 ? '...' : ''; ?>
        </td>
        <td style="text-align:right;">
            <?= number_format($inv['total_amount'], 2); ?>
        </td>
        <td>
            <?= $inv['status']; ?>
        </td>
        <td>
          <div class="action-buttons">
            <a class="btn btn-edit" href="edit_invoice.php?id=<?= $inv['invoice_id']; ?>" title="Edit Invoice">
              ✏️
            </a>
            <?php if ($inv['status'] === 'Unpaid'): ?>
              <a class="btn ok"
                 href="?toggle=pay&id=<?= $inv['invoice_id']; ?>"
                 onclick="return confirm('Mark this invoice paid?');"
                 title="Mark Paid">
                 ✓
              </a>
            <?php else: ?>
              <a class="btn cancel"
                 href="?toggle=unpay&id=<?= $inv['invoice_id']; ?>"
                 onclick="return confirm('Mark this invoice unpaid?');"
                 title="Mark Unpaid">
                 ✗
              </a>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table>
</main>

<style>
.btn-primary {
    background: linear-gradient(135deg, #3498db, #2980b9) !important;
    color: white !important;
    border: none !important;
    padding: 8px 16px !important;
    border-radius: 6px !important;
    text-decoration: none !important;
    font-weight: 600 !important;
    transition: all 0.3s ease !important;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2980b9, #21618c) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3) !important;
}

table th, table td {
    padding: 12px 8px;
    vertical-align: middle;
}

.action-buttons {
    display: flex;
    gap: 5px;
    align-items: center;
}

.btn {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 4px;
    text-decoration: none;
    color: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    transition: all 0.2s ease;
}

.btn-edit {
    background: linear-gradient(135deg, #f39c12, #e67e22);
    color: white;
}

.btn-edit:hover {
    background: linear-gradient(135deg, #e67e22, #d35400);
    transform: translateY(-1px);
}

.btn.ok {
    background: linear-gradient(135deg, #27ae60, #229954);
}

.btn.ok:hover {
    background: linear-gradient(135deg, #229954, #1e8449);
    transform: translateY(-1px);
}

.btn.cancel {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.btn.cancel:hover {
    background: linear-gradient(135deg, #c0392b, #a93226);
    transform: translateY(-1px);
}
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>