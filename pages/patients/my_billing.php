<?php
/*****************************************************************
 * pages/patients/my_billing.php
 * ---------------------------------------------------------------
 * Patient portal - View their own invoices and billing history
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();
require_patient(); // Only patients can access their billing

// Resolve patient_id from the logged-in user account (avoids missing session patient_id)
$user_id = $_SESSION['user_id'];
$patient_q = $conn->query("
    SELECT p.patient_id
    FROM patient p
    JOIN usertbl u ON u.email = p.email
    WHERE u.user_id = $user_id
    LIMIT 1
");

if (!$patient_q) {
    flash('Database error: ' . $conn->error);
    redirect('/dentosys/auth/logout.php');
}

$patient_row = $patient_q->fetch_assoc();
if (!$patient_row) {
    flash('Patient information not found.');
    redirect('/dentosys/auth/logout.php');
}
$patient_id = (int)$patient_row['patient_id'];

/* ───────── Query patient's invoices ───────── */
$sql = "SELECT i.*, CONCAT(p.first_name,' ',p.last_name) AS patient_name
        FROM invoice i
        JOIN patient p ON p.patient_id = i.patient_id
        WHERE i.patient_id = ?
        ORDER BY i.issued_date DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    flash('Database error preparing invoice query: ' . $conn->error);
    redirect('/dentosys/pages/patients/dashboard.php');
}

$stmt->bind_param('i', $patient_id);
$stmt->execute();
$invoices_result = $stmt->get_result();

// Store invoices in array for multiple use
$invoices_array = [];
if ($invoices_result) {
    while ($row = $invoices_result->fetch_assoc()) {
        $invoices_array[] = $row;
    }
}

/* ───────── Query patient's insurance claims (optional, tolerant to table name differences) ───────── */
// Detect available claims table to avoid fatal if environment differs
$claims_table = null;
$claimsTableCheck = $conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('InsuranceClaims','InsuranceClaim','insuranceclaims','insuranceclaim') LIMIT 1");
if ($claimsTableCheck && ($row = $claimsTableCheck->fetch_row())) {
    $claims_table = $row[0];
}

if ($claims_table) {
    // Pick an available date column for ordering to avoid unknown column errors
    $colCheck = $conn->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '" . $conn->real_escape_string($claims_table) . "' AND COLUMN_NAME IN ('submission_date','created_at','updated_at','claim_id')");
    $availableCols = [];
    if ($colCheck) {
        while ($r = $colCheck->fetch_row()) { $availableCols[$r[0]] = true; }
    }
    if (isset($availableCols['submission_date'])) {
        $orderCol = 'submission_date';
    } elseif (isset($availableCols['created_at'])) {
        $orderCol = 'created_at';
    } elseif (isset($availableCols['updated_at'])) {
        $orderCol = 'updated_at';
    } else {
        $orderCol = 'claim_id';
    }

    $claims_sql = "SELECT ic.*, i.invoice_id, i.issued_date
                   FROM `{$claims_table}` ic
                   LEFT JOIN invoice i ON i.invoice_id = ic.invoice_id
                   WHERE ic.patient_id = ?
                   ORDER BY ic.`$orderCol` DESC";
    $cstmt = $conn->prepare($claims_sql);
    $cstmt->bind_param('i', $patient_id);
    $cstmt->execute();
    $claims = $cstmt->get_result();
} else {
    $claims = false; // No claims table in this environment
}

/* ───────── Calculate totals ───────── */
$total_billed = 0;
$total_paid = 0;

// Ensure we have data to work with
if (!empty($invoices_array)) {
    foreach ($invoices_array as $inv) {
        $total_billed += (float)$inv['total_amount'];
        if (isset($inv['status']) && $inv['status'] === 'Paid') {
            $total_paid += (float)$inv['total_amount'];
        }
    }
}

/* ───────── HTML ───────── */
$pageTitle = 'My Billing History';
include BASE_PATH . '/templates/header.php';
?>

<div class="main-wrapper patient-page full-width">
    <?php include BASE_PATH . '/templates/sidebar.php'; ?>
    <main class="content">
        <div class="page-container">
            <header class="content-header">
                <h1>💰 My Billing</h1>
                <p class="subtitle">Review your invoices, payments, and insurance claims.</p>
            </header>

            <?= get_flash(); ?>

            <div class="billing-summary">
                <div class="summary-card">
                    <h3>Total Billed</h3>
                    <p>$<?= number_format($total_billed, 2); ?></p>
                </div>
                <div class="summary-card">
                    <h3>Total Paid</h3>
                    <p>$<?= number_format($total_paid, 2); ?></p>
                </div>
                <div class="summary-card outstanding">
                    <h3>Outstanding</h3>
                    <p>$<?= number_format($total_billed - $total_paid, 2); ?></p>
                </div>
            </div>

            <div class="billing-section">
                <div class="billing-header">
                    <h2>Invoices</h2>
                </div>
                <?php if (count($invoices_array) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices_array as $invoice): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($invoice['invoice_id']); ?></td>
                                        <td><?= date('M j, Y', strtotime($invoice['issued_date'])); ?></td>
                                        <td>$<?= number_format($invoice['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status status-<?= strtolower(htmlspecialchars($invoice['status'])); ?>">
                                                <?= htmlspecialchars($invoice['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="/dentosys/pages/billing/invoices.php?view=<?= $invoice['invoice_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <p>No invoices found.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($claims_table): ?>
            <div class="billing-section">
                <div class="billing-header">
                    <h2>Insurance Claims</h2>
                </div>
                <?php if ($claims && $claims->num_rows > 0): ?>
                     <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Claim ID</th>
                                    <th>Invoice #</th>
                                    <th>Submission Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($claim = $claims->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($claim['claim_id']); ?></td>
                                        <td><a href="/dentosys/pages/billing/invoices.php?view=<?= $claim['invoice_id']; ?>"><?= htmlspecialchars($claim['invoice_id']); ?></a></td>
                                        <td><?= isset($claim['submission_date']) ? date('M j, Y', strtotime($claim['submission_date'])) : 'N/A'; ?></td>
                                        <td>$<?= number_format($claim['claim_amount'], 2); ?></td>
                                        <td>
                                            <span class="status status-<?= strtolower(htmlspecialchars($claim['status'])); ?>">
                                                <?= htmlspecialchars($claim['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <p>No insurance claims found.</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<style>
/**** Billing page specific styles (updated for full width) ****/
.billing-summary { 
  display: grid; 
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
  gap: 16px; 
  margin-top: 20px; 
  width: 100%; 
  /* removed max-width */
}

.summary-card { 
  background: linear-gradient(135deg, #3498db, #2980b9); 
  color: white; 
  padding: 16px; 
  border-radius: 14px; 
  display: flex; 
  align-items: center; 
  gap: 12px; 
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); 
}

.summary-card .summary-icon { 
  font-size: 28px; 
  line-height: 1; 
}

.summary-card.paid {
    background: linear-gradient(135deg, #27ae60, #229954);
}

.summary-card.unpaid {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.summary-value { 
  font-size: 22px; 
  font-weight: 800; 
  margin-bottom: 2px; 
}

.summary-label { 
  font-size: 13px; 
  opacity: 0.92; 
}

.invoices-container { 
  margin-top: 30px; 
  width: 100%; 
  display: flex; 
  flex-direction: column; 
  align-items: stretch; /* fill width */
}

.invoices-header { 
  width: 100%; 
  /* removed max-width */
  text-align: left; 
  margin: 0 0 10px; 
}

.invoices-header h2 { 
  margin: 0 0 6px; 
}

.invoices-header p { 
  margin: 0; 
  color: #6b7280; 
}

.invoices-grid { 
  display: grid; 
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); 
  gap: 20px; 
  width: 100%; 
  /* removed max-width and centering */
}

.invoice-card { 
  background: white; 
  border: 1px solid #e1e5e9; 
  border-radius: 12px; 
  padding: 20px; 
  box-shadow: 0 6px 16px rgba(0,0,0,0.06); 
  transition: all 0.3s ease; 
}

.invoice-card:hover { 
  transform: translateY(-2px); 
  box-shadow: 0 10px 18px rgba(0,0,0,0.10); 
}

.invoice-card.paid {
    border-left: 4px solid #27ae60;
}

.invoice-card.unpaid {
    border-left: 4px solid #e74c3c;
}

.invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ecf0f1;
}

.invoice-number { 
  font-weight: 700; 
  color: #2c3e50; 
  font-size: 16px; 
}

.invoice-status { 
  padding: 4px 12px; 
  border-radius: 999px; 
  font-size: 12px; 
  font-weight: 700; 
  text-transform: uppercase; 
}

.status-paid {
    background: #d5f4e6;
    color: #27ae60;
}

.status-unpaid {
    background: #fadbd8;
    color: #e74c3c;
}

.invoice-date {
    margin-bottom: 10px;
    font-size: 14px;
    color: #7f8c8d;
}

.invoice-description {
    margin-bottom: 15px;
    color: #2c3e50;
    line-height: 1.5;
}

.invoice-amount { 
  font-size: 22px; 
  font-weight: 800; 
  color: #2c3e50; 
  text-align: right; 
}

.invoice-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #ecf0f1;
    text-align: center;
    color: #7f8c8d;
    font-style: italic;
}

.no-data {
    text-align: center;
    padding: 60px 20px;
    color: #7f8c8d;
}

.no-data-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.no-data h3 {
    color: #2c3e50;
    margin-bottom: 10px;
}

@media (max-width: 768px) {
    .billing-summary {
        grid-template-columns: 1fr;
    }
    
    .invoices-grid {
        grid-template-columns: 1fr;
    }
    
    .invoice-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    .claims-grid { grid-template-columns: 1fr; }
}

/* Insurance Claims Styles */
.claims-section { 
  width: 100%; 
  /* removed max-width */
  margin: 40px 0 0; 
}

.claims-header { 
  text-align: center; 
  margin-bottom: 16px; 
}

.claims-header h2 { 
  margin: 0 0 6px; 
}

.claims-header p { 
  margin: 0; 
  color: #6b7280; 
}

.claims-grid { 
  display: grid; 
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); 
  gap: 16px; 
}

.claim-card { 
  background: white; 
  border: 1px solid #e1e5e9; 
  border-radius: 12px; 
  box-shadow: 0 6px 16px rgba(0,0,0,0.06); 
  padding: 16px; 
  transition: transform .2s ease, box-shadow .2s ease; 
}

.claim-card:hover { 
  transform: translateY(-2px); 
  box-shadow: 0 10px 18px rgba(0,0,0,0.10); 
}

.claim-header { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  padding-bottom: 8px; 
  margin-bottom: 10px; 
  border-bottom: 1px solid #ecf0f1; 
}

.claim-number { 
  font-weight: 700; 
  color: #2c3e50; 
}

.claim-status { 
  padding: 4px 10px; 
  border-radius: 999px; 
  font-size: 12px; 
  font-weight: 700; 
  background: #eef2ff; 
  color: #4338ca; 
}

.claim-card.status-approved .claim-status { 
  background: #d1fae5; 
  color: #065f46; 
}

.claim-card.status-rejected .claim-status { 
  background: #fee2e2; 
  color: #991b1b; 
}

.claim-card.status-paid .claim-status { 
  background: #dcfce7; 
  color: #166534; 
}

.claim-body > div { 
  margin: 6px 0; 
  color: #374151; 
}

.claim-notes { 
  background: #f8fafc; 
  padding: 10px; 
  border-radius: 8px; 
  border-left: 3px solid #93c5fd; 
}
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
