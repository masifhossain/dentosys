<?php
/*****************************************************************
 * pages/billing/payments.php
 * ---------------------------------------------------------------
 * View all payments and add new ones (Admin / Receptionist only)
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2 levels
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Restrict to Admin (1) or Receptionist (3) ───────── */
if (!is_admin() && ($_SESSION['role'] ?? 0) !== 3) {
    flash('You do not have permission to manage payments.');
    redirect('/index.php');
}

/* ───────── Handle new-payment form ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_id = intval($_POST['invoice_id']);
    $amount     = floatval($_POST['amount']);
    $method     = $conn->real_escape_string($_POST['method']);
    $ref        = $conn->real_escape_string($_POST['reference']);
    $date       = $conn->real_escape_string($_POST['paid_date']);

    $stmt = $conn->prepare(
        "INSERT INTO Payment (invoice_id,amount,method,reference,paid_date)
         VALUES (?,?,?,?,?)"
    );
    $stmt->bind_param('idsss', $invoice_id, $amount, $method, $ref, $date);

    if ($stmt->execute()) {
        /* automatically mark invoice Paid if fully covered */
        $invRes = $conn->query(
            "SELECT total_amount, (
                SELECT COALESCE(SUM(amount),0)
                FROM Payment WHERE invoice_id = $invoice_id
             ) AS paid FROM Invoice WHERE invoice_id = $invoice_id"
        );
        if ($row = $invRes->fetch_assoc()) {
            if ($row['paid'] >= $row['total_amount']) {
                $conn->query(
                  "UPDATE Invoice SET status='Paid' WHERE invoice_id=$invoice_id"
                );
            }
        }
        flash('Payment recorded.');
    } else {
        flash('DB error: ' . $conn->error);
    }
    redirect('payments.php');
}

/* ───────── Filters ───────── */
$where = '';
if (!empty($_GET['invoice'])) {
    $inv = intval($_GET['invoice']);
    $where = "WHERE p.invoice_id = $inv";
}
if (!empty($_GET['method'])) {
    $mth = $conn->real_escape_string($_GET['method']);
    $where = $where ? "$where AND p.method = '$mth'" : "WHERE p.method = '$mth'";
}

/* ───────── Fetch payments ───────── */
$sql = "SELECT p.*, i.total_amount,
               CONCAT(pa.first_name,' ',pa.last_name) AS patient
        FROM Payment p
        JOIN Invoice i ON i.invoice_id = p.invoice_id
        JOIN Patient pa ON pa.patient_id = i.patient_id
        $where
        ORDER BY p.paid_date DESC";
$payments = $conn->query($sql);

/* ───────── Invoice list for dropdown ───────── */
$invoiceList = $conn->query(
    "SELECT invoice_id, CONCAT('#',invoice_id,' / ',total_amount,' / ',status) AS label
     FROM Invoice ORDER BY invoice_id DESC LIMIT 100"
);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Payments</h2>
  <?= get_flash(); ?>

  <!-- New payment form (collapsible minimal) -->
  <details style="margin-bottom:14px;">
    <summary style="cursor:pointer;padding:6px 0;">+ Record Payment</summary>
    <form method="post" style="margin-top:10px;max-width:440px;">
      <label>Invoice:
        <select name="invoice_id" required style="width:100%;">
          <option value="">Select Invoice</option>
          <?php while($iv=$invoiceList->fetch_assoc()): ?>
            <option value="<?= $iv['invoice_id']; ?>">
              <?= htmlspecialchars($iv['label']); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </label><br><br>

      <label>Amount ($):
        <input type="number" step="0.01" min="0" name="amount" required>
      </label><br><br>

      <label>Method:
        <select name="method" required>
          <?php foreach(['Cash','Credit Card','Check','Bank Transfer','Other'] as $m): ?>
            <option value="<?= $m; ?>"><?= $m; ?></option>
          <?php endforeach; ?>
        </select>
      </label><br><br>

      <label>Reference / Note:
        <input type="text" name="reference" maxlength="80">
      </label><br><br>

      <label>Date:
        <input type="date" name="paid_date" value="<?= date('Y-m-d'); ?>" required>
      </label><br><br>

      <button type="submit">Save Payment</button>
    </form>
  </details>

  <!-- Filter bar -->
  <form method="get" style="margin-bottom:10px;">
    <input type="number" name="invoice" placeholder="Invoice #" value="<?= $_GET['invoice'] ?? ''; ?>">
    <select name="method">
      <option value="">Any method</option>
      <?php foreach(['Cash','Credit Card','Check','Bank Transfer','Other'] as $m): ?>
        <option value="<?= $m; ?>" <?= (!empty($_GET['method']) && $_GET['method']===$m)?'selected':''; ?>><?= $m; ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Filter</button>
    <a class="btn" href="payments.php">Reset</a>
  </form>

  <!-- Payments table -->
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Invoice</th>
        <th>Patient</th>
        <th style="text-align:right;">Amount ($)</th>
        <th>Method</th>
        <th>Date</th>
        <th>Reference</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($payments->num_rows === 0): ?>
      <tr><td colspan="7">No payments found.</td></tr>
    <?php else: $i=1; while($p=$payments->fetch_assoc()): ?>
      <tr>
        <td><?= $i++; ?></td>
        <td>#<?= $p['invoice_id']; ?></td>
        <td><?= htmlspecialchars($p['patient']); ?></td>
        <td style="text-align:right;"><?= number_format($p['amount'],2); ?></td>
        <td><?= $p['method']; ?></td>
        <td><?= $p['paid_date']; ?></td>
        <td><?= htmlspecialchars($p['reference']); ?></td>
      </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>