<?php
/*****************************************************************
 * pages/patients/my_billing.php
 * ---------------------------------------------------------------
 * Patient portal - View their own invoices and billing history
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

// Only allow patients to view this page
if (($_SESSION['role'] ?? 0) !== 4) {
    flash('Access denied. Patients only.');
    redirect('/dentosys/index.php');
}

$patient_id = $_SESSION['patient_id'] ?? 0;
if (!$patient_id) {
    flash('Patient information not found.');
    redirect('/dentosys/auth/login.php');
}

/* ───────── Query patient's invoices ───────── */
$sql = "SELECT i.*, CONCAT(p.first_name,' ',p.last_name) AS patient_name
        FROM Invoice i
        JOIN Patient p ON p.patient_id = i.patient_id
        WHERE i.patient_id = ?
        ORDER BY issued_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$invoices = $stmt->get_result();

/* ───────── Calculate totals ───────── */
$total_amount = 0;
$paid_amount = 0;
$unpaid_amount = 0;

$invoices->data_seek(0);
while ($inv = $invoices->fetch_assoc()) {
    $total_amount += $inv['total_amount'];
    if ($inv['status'] === 'Paid') {
        $paid_amount += $inv['total_amount'];
    } else {
        $unpaid_amount += $inv['total_amount'];
    }
}

/* ───────── HTML ───────── */
include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
    <div class="page-header">
        <h2>💰 My Billing History</h2>
        <div class="billing-summary">
            <div class="summary-card">
                <div class="summary-value">$<?= number_format($total_amount, 2); ?></div>
                <div class="summary-label">Total Billed</div>
            </div>
            <div class="summary-card paid">
                <div class="summary-value">$<?= number_format($paid_amount, 2); ?></div>
                <div class="summary-label">Paid</div>
            </div>
            <div class="summary-card unpaid">
                <div class="summary-value">$<?= number_format($unpaid_amount, 2); ?></div>
                <div class="summary-label">Outstanding</div>
            </div>
        </div>
    </div>

    <?= get_flash(); ?>

    <div class="invoices-container">
        <?php if ($invoices->num_rows === 0): ?>
            <div class="no-data">
                <div class="no-data-icon">💰</div>
                <h3>No Billing History</h3>
                <p>You don't have any invoices yet.</p>
            </div>
        <?php else: ?>
            <div class="invoices-grid">
                <?php 
                $invoices->data_seek(0);
                while ($inv = $invoices->fetch_assoc()): 
                ?>
                    <div class="invoice-card <?= strtolower($inv['status']); ?>">
                        <div class="invoice-header">
                            <div class="invoice-number">Invoice #<?= $inv['invoice_id']; ?></div>
                            <div class="invoice-status status-<?= strtolower($inv['status']); ?>">
                                <?= $inv['status']; ?>
                            </div>
                        </div>
                        
                        <div class="invoice-date">
                            <strong>Issue Date:</strong> <?= date('M j, Y', strtotime($inv['issued_date'])); ?>
                        </div>
                        
                        <div class="invoice-description">
                            <?= htmlspecialchars($inv['description'] ?? 'No description available'); ?>
                        </div>
                        
                        <div class="invoice-amount">
                            $<?= number_format($inv['total_amount'], 2); ?>
                        </div>
                        
                        <?php if ($inv['status'] === 'Unpaid'): ?>
                            <div class="invoice-actions">
                                <small>Please contact the clinic to make payment</small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.page-header {
    margin-bottom: 30px;
}

.billing-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.summary-card {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.summary-card.paid {
    background: linear-gradient(135deg, #27ae60, #229954);
}

.summary-card.unpaid {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.summary-value {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.summary-label {
    font-size: 14px;
    opacity: 0.9;
}

.invoices-container {
    margin-top: 30px;
}

.invoices-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.invoice-card {
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.invoice-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
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
    font-weight: bold;
    color: #2c3e50;
    font-size: 16px;
}

.invoice-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
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
    font-size: 20px;
    font-weight: bold;
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
}
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
