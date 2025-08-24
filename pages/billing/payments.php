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
// Dentists cannot manage payments
if (is_dentist() || (!is_admin() && ($_SESSION['role'] ?? 0) !== 3)) {
    flash('You do not have permission to manage payments.');
    redirect('/dentosys/index.php');
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

<style>
.payments-main {
    padding: 0 2rem 3rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    min-height: 100vh;
}

.page-header {
    background: linear-gradient(135deg, #0891b2, #0e7490);
    margin: 0 -2rem 2rem;
    padding: 2rem 2rem 2.5rem;
    color: white;
    border-radius: 0 0 24px 24px;
    box-shadow: 0 8px 32px -8px rgba(8, 145, 178, 0.3);
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

.payment-form-card {
    background: white;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.form-toggle {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    padding: 1rem 1.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 600;
    color: #374151;
    border: none;
    width: 100%;
    text-align: left;
    transition: all 0.2s ease;
}

.form-toggle:hover {
    background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
}

.form-toggle.active {
    background: linear-gradient(135deg, #0891b2, #0e7490);
    color: white;
}

.toggle-icon {
    font-size: 1.2rem;
    transition: transform 0.2s ease;
}

.toggle-icon.rotated {
    transform: rotate(45deg);
}

.form-content {
    padding: 2rem 1.5rem;
    display: none;
    border-top: 1px solid #e2e8f0;
}

.form-content.active {
    display: block;
}

.payment-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.form-input,
.form-select {
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: #f9fafb;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.form-input:focus,
.form-select:focus {
    border-color: #0891b2;
    outline: none;
    background: white;
    box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
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
    background: linear-gradient(135deg, #0891b2, #0e7490);
    color: white;
    box-shadow: 0 4px 12px -4px rgba(8, 145, 178, 0.4);
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px -4px rgba(8, 145, 178, 0.6);
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

.payments-table {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
}

.table-header {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.table-title {
    margin: 0;
    color: #1e293b;
    font-size: 1.25rem;
    font-weight: 700;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
}

.modern-table th {
    background: #f8fafc;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #e2e8f0;
}

.modern-table td {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.modern-table tr:hover {
    background: #f8fafc;
}

.payment-id {
    font-weight: 700;
    color: #0891b2;
}

.invoice-link {
    color: #0891b2;
    font-weight: 600;
    text-decoration: none;
}

.invoice-link:hover {
    text-decoration: underline;
}

.payment-method {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: #f0f9ff;
    color: #0c4a6e;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
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
    .payments-main { padding: 0 1rem 2rem; }
    .page-header { margin: 0 -1rem 1.5rem; padding: 1.5rem 1rem 2rem; }
    .header-content { flex-direction: column; align-items: stretch; text-align: center; }
    .payment-form-grid, .filters-grid { grid-template-columns: 1fr; }
    .modern-table { font-size: 0.8rem; }
}
</style>

<main class="payments-main">
    <div class="page-header">
        <div class="header-content">
            <div class="title-section">
                <div class="icon-wrapper">💳</div>
                <div>
                    <h1>Payment Management</h1>
                    <p class="subtitle">Record and track patient payments</p>
                </div>
            </div>
        </div>
    </div>

    <?= get_flash(); ?>

    <!-- New Payment Form -->
    <div class="payment-form-card">
        <button class="form-toggle" onclick="togglePaymentForm()">
            <span class="toggle-icon">➕</span>
            <span>Record New Payment</span>
        </button>
        
        <div class="form-content" id="paymentForm">
            <form method="post">
                <div class="payment-form-grid">
                    <div class="form-group">
                        <label class="form-label">Invoice <span style="color: #ef4444;">*</span></label>
                        <select name="invoice_id" required class="form-select">
                            <option value="">Select Invoice</option>
                            <?php while($iv=$invoiceList->fetch_assoc()): ?>
                                <option value="<?= $iv['invoice_id']; ?>">
                                    <?= htmlspecialchars($iv['label']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Amount ($) <span style="color: #ef4444;">*</span></label>
                        <input type="number" step="0.01" min="0" name="amount" required class="form-input" placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Method <span style="color: #ef4444;">*</span></label>
                        <select name="method" required class="form-select">
                            <?php foreach(['Cash','Credit Card','Check','Bank Transfer','Other'] as $m): ?>
                                <option value="<?= $m; ?>"><?= $m; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Reference / Note</label>
                        <input type="text" name="reference" maxlength="80" class="form-input" placeholder="Transaction reference">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Date <span style="color: #ef4444;">*</span></label>
                        <input type="date" name="paid_date" value="<?= date('Y-m-d'); ?>" required class="form-input">
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <span>💾</span>
                            Save Payment
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-card">
        <form method="get">
            <div class="filters-grid">
                <div class="form-group">
                    <label class="form-label">Invoice Number</label>
                    <input type="number" name="invoice" placeholder="Enter invoice #" value="<?= $_GET['invoice'] ?? ''; ?>" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Payment Method</label>
                    <select name="method" class="form-select">
                        <option value="">All Methods</option>
                        <?php foreach(['Cash','Credit Card','Check','Bank Transfer','Other'] as $m): ?>
                            <option value="<?= $m; ?>" <?= (!empty($_GET['method']) && $_GET['method']===$m)?'selected':''; ?>><?= $m; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <span>🔍</span>
                        Apply Filters
                    </button>
                </div>

                <div class="form-group">
                    <a class="btn btn-secondary" href="payments.php">
                        <span>🔄</span>
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="payments-table">
        <div class="table-header">
            <h3 class="table-title">Payment Records</h3>
        </div>

        <table class="modern-table">
            <thead>
                <tr>
                    <th>Payment #</th>
                    <th>Invoice</th>
                    <th>Patient</th>
                    <th style="text-align: right;">Amount</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Reference</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($payments->num_rows === 0): ?>
                <tr>
                    <td colspan="7" class="empty-state">
                        <div class="empty-icon">💳</div>
                        <div>No payments found matching your criteria</div>
                    </td>
                </tr>
            <?php else: $i=1; while($p=$payments->fetch_assoc()): ?>
                <tr>
                    <td>
                        <span class="payment-id">#<?= $i++; ?></span>
                    </td>
                    <td>
                        <a href="../billing/invoices.php" class="invoice-link">#<?= $p['invoice_id']; ?></a>
                    </td>
                    <td><?= htmlspecialchars($p['patient']); ?></td>
                    <td style="text-align: right; font-weight: 600;">
                        $<?= number_format($p['amount'],2); ?>
                    </td>
                    <td>
                        <span class="payment-method"><?= $p['method']; ?></span>
                    </td>
                    <td><?= date('M j, Y', strtotime($p['paid_date'])); ?></td>
                    <td><?= htmlspecialchars($p['reference']); ?></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
function togglePaymentForm() {
    const formContent = document.getElementById('paymentForm');
    const toggle = document.querySelector('.form-toggle');
    const icon = toggle.querySelector('.toggle-icon');
    
    if (formContent.classList.contains('active')) {
        formContent.classList.remove('active');
        toggle.classList.remove('active');
        icon.classList.remove('rotated');
        icon.textContent = '➕';
    } else {
        formContent.classList.add('active');
        toggle.classList.add('active');
        icon.classList.add('rotated');
        icon.textContent = '✖';
    }
}
</script>
<?php include BASE_PATH . '/templates/footer.php'; ?>