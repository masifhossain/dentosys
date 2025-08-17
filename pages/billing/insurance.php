<?php
/*****************************************************************
 * pages/billing/insurance.php
 * ---------------------------------------------------------------
 * Manage insurance claims and submissions
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Restrict to Admin (1) or Receptionist (3) ───────── */
if (!is_admin() && ($_SESSION['role'] ?? 0) !== 3) {
    flash('You do not have permission to manage insurance claims.');
    redirect('/index.php');
}

/* ───────── Create InsuranceClaim table if not exists ───────── */
$conn->query("
CREATE TABLE IF NOT EXISTS InsuranceClaim (
    claim_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    invoice_id INT,
    insurance_provider VARCHAR(255) NOT NULL,
    policy_number VARCHAR(100) NOT NULL,
    claim_amount DECIMAL(10,2) NOT NULL,
    submitted_date DATE,
    processed_date DATE,
    status ENUM('Draft', 'Submitted', 'Under Review', 'Approved', 'Denied', 'Paid') DEFAULT 'Draft',
    notes TEXT,
    claim_reference VARCHAR(100),
    denial_reason TEXT,
    paid_amount DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES Patient(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES Invoice(invoice_id) ON DELETE SET NULL
)
");

/* ───────── Handle status updates ───────── */
if (isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'submit') {
        $conn->query("UPDATE InsuranceClaim SET status='Submitted', submitted_date=CURDATE() WHERE claim_id=$id");
        flash('Claim submitted successfully.');
    } elseif ($action === 'approve') {
        $conn->query("UPDATE InsuranceClaim SET status='Approved', processed_date=CURDATE() WHERE claim_id=$id");
        flash('Claim approved.');
    } elseif ($action === 'deny') {
        $conn->query("UPDATE InsuranceClaim SET status='Denied', processed_date=CURDATE() WHERE claim_id=$id");
        flash('Claim denied.');
    } elseif ($action === 'pay') {
        $conn->query("UPDATE InsuranceClaim SET status='Paid', processed_date=CURDATE() WHERE claim_id=$id");
        flash('Claim marked as paid.');
    }
    redirect('insurance.php');
}

/* ───────── Filter handling ───────── */
$whereSQL = "WHERE 1=1";
if (!empty($_GET['patient'])) {
    $pid = intval($_GET['patient']);
    $whereSQL .= " AND ic.patient_id = $pid";
}
if (!empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $whereSQL .= " AND ic.status = '$status'";
}
if (!empty($_GET['provider'])) {
    $provider = $conn->real_escape_string($_GET['provider']);
    $whereSQL .= " AND ic.insurance_provider LIKE '%$provider%'";
}

/* ───────── Fetch insurance claims ───────── */
$sql = "SELECT ic.*, 
               CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
               i.total_amount as invoice_amount
        FROM InsuranceClaim ic
        JOIN Patient p ON p.patient_id = ic.patient_id
        LEFT JOIN Invoice i ON i.invoice_id = ic.invoice_id
        $whereSQL
        ORDER BY ic.created_at DESC";
$claims = $conn->query($sql);

/* ───────── Get patients for filter ───────── */
$patients = get_patients($conn);

/* ───────── Get insurance providers for filter ───────── */
$providers = $conn->query("SELECT DISTINCT insurance_provider FROM InsuranceClaim ORDER BY insurance_provider");

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Insurance Claims Management</h2>
  <?= get_flash(); ?>

  <!-- Action buttons -->
  <div style="margin-bottom: 20px;">
    <a class="btn btn-primary" href="submit_claim.php">+ Submit New Claim</a>
    <a class="btn btn-outline" href="invoices.php">← Back to Invoices</a>
  </div>

  <!-- Summary Cards -->
  <?php
  $summary = $conn->query("
    SELECT 
      COUNT(*) as total_claims,
      SUM(CASE WHEN status = 'Submitted' THEN 1 ELSE 0 END) as pending_claims,
      SUM(CASE WHEN status = 'Approved' THEN claim_amount ELSE 0 END) as approved_amount,
      SUM(CASE WHEN status = 'Paid' THEN paid_amount ELSE 0 END) as paid_amount
    FROM InsuranceClaim
  ")->fetch_assoc();
  ?>
  <div class="grid grid-cols-4 gap-4 mb-4">
    <div class="card text-center">
      <h3><?= $summary['total_claims']; ?></h3>
      <p>Total Claims</p>
    </div>
    <div class="card text-center">
      <h3><?= $summary['pending_claims']; ?></h3>
      <p>Pending Review</p>
    </div>
    <div class="card text-center">
      <h3>$<?= number_format($summary['approved_amount'], 2); ?></h3>
      <p>Approved Amount</p>
    </div>
    <div class="card text-center">
      <h3>$<?= number_format($summary['paid_amount'], 2); ?></h3>
      <p>Paid Amount</p>
    </div>
  </div>

  <!-- Filter form -->
  <form method="get" style="margin-bottom:20px; padding:15px; background:#f8f9fa; border-radius:8px;">
    <div style="display:flex; gap:15px; align-items:end; flex-wrap:wrap;">
      <div>
        <label>Patient:</label>
        <select name="patient" style="margin-top:5px;">
          <option value="">-- All Patients --</option>
          <?php while ($p = $patients->fetch_assoc()): ?>
            <option value="<?= $p['patient_id']; ?>"
              <?= (!empty($_GET['patient']) && $_GET['patient'] == $p['patient_id']) ? 'selected' : ''; ?>>
              <?= htmlspecialchars($p['name']); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div>
        <label>Status:</label>
        <select name="status" style="margin-top:5px;">
          <option value="">-- All Status --</option>
          <?php foreach(['Draft', 'Submitted', 'Under Review', 'Approved', 'Denied', 'Paid'] as $status): ?>
            <option value="<?= $status; ?>"
              <?= (!empty($_GET['status']) && $_GET['status'] === $status) ? 'selected' : ''; ?>>
              <?= $status; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label>Insurance Provider:</label>
        <select name="provider" style="margin-top:5px;">
          <option value="">-- All Providers --</option>
          <?php while ($prov = $providers->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($prov['insurance_provider']); ?>"
              <?= (!empty($_GET['provider']) && $_GET['provider'] === $prov['insurance_provider']) ? 'selected' : ''; ?>>
              <?= htmlspecialchars($prov['insurance_provider']); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <a href="insurance.php" class="btn btn-outline">Reset</a>
      </div>
    </div>
  </form>

  <!-- Claims table -->
  <div class="card">
    <table class="table">
      <thead>
        <tr>
          <th>Claim ID</th>
          <th>Patient</th>
          <th>Insurance Provider</th>
          <th>Policy Number</th>
          <th>Claim Amount</th>
          <th>Status</th>
          <th>Submitted Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($claims->num_rows === 0): ?>
          <tr><td colspan="8" class="text-center">No insurance claims found.</td></tr>
        <?php else: ?>
          <?php while ($claim = $claims->fetch_assoc()): ?>
            <tr>
              <td>#<?= $claim['claim_id']; ?></td>
              <td><?= htmlspecialchars($claim['patient_name']); ?></td>
              <td><?= htmlspecialchars($claim['insurance_provider']); ?></td>
              <td><?= htmlspecialchars($claim['policy_number']); ?></td>
              <td>$<?= number_format($claim['claim_amount'], 2); ?></td>
              <td>
                <span class="badge badge-<?= 
                  $claim['status'] === 'Approved' ? 'success' : 
                  ($claim['status'] === 'Denied' ? 'danger' : 
                  ($claim['status'] === 'Paid' ? 'primary' : 'warning')); ?>">
                  <?= $claim['status']; ?>
                </span>
              </td>
              <td><?= $claim['submitted_date'] ? date('M d, Y', strtotime($claim['submitted_date'])) : '-'; ?></td>
              <td>
                <div style="display:flex; gap:5px; flex-wrap:wrap;">
                  <?php if ($claim['status'] === 'Draft'): ?>
                    <a href="?action=submit&id=<?= $claim['claim_id']; ?>" 
                       class="btn btn-sm btn-primary"
                       onclick="return confirm('Submit this claim?');">Submit</a>
                  <?php elseif ($claim['status'] === 'Submitted' || $claim['status'] === 'Under Review'): ?>
                    <a href="?action=approve&id=<?= $claim['claim_id']; ?>" 
                       class="btn btn-sm btn-success"
                       onclick="return confirm('Approve this claim?');">Approve</a>
                    <a href="?action=deny&id=<?= $claim['claim_id']; ?>" 
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Deny this claim?');">Deny</a>
                  <?php elseif ($claim['status'] === 'Approved'): ?>
                    <a href="?action=pay&id=<?= $claim['claim_id']; ?>" 
                       class="btn btn-sm btn-primary"
                       onclick="return confirm('Mark as paid?');">Mark Paid</a>
                  <?php endif; ?>
                  <a href="submit_claim.php?edit=<?= $claim['claim_id']; ?>" 
                     class="btn btn-sm btn-outline">Edit</a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<style>
.badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}
.badge-success { background: #d1f2eb; color: #0f5132; }
.badge-primary { background: #cff4fc; color: #055160; }
.badge-danger { background: #f8d7da; color: #721c24; }
.badge-warning { background: #fff3cd; color: #664d03; }
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
