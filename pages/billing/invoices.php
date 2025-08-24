<?php
/*****************************************************************
 * pages/billing/invoices.php
 * ---------------------------------------------------------------
 * View, filter, and mark invoices Paid / Unpaid
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // …/pages/billing/ -> up 2
require_once BASE_PATH . '/includes/functions.php';

require_login();
require_staff(); // Only staff can access billing

// Updated policy: Dentists must not access patient billing
if (is_dentist()) {
    flash('Patient billing is restricted to administrators and receptionists.');
    redirect('/dentosys/index.php');
}

/* ───────── Handle status toggle ───────── */
if (isset($_GET['toggle'], $_GET['id'])) {
    // Only allow admins and receptionists to modify invoice status
    if (is_dentist()) {
        flash('You do not have permission to modify invoice status.');
        redirect('invoices.php');
    }
    
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

// Dentists cannot access this page; no dentist scoping needed

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

<style>
.billing-main {
    padding: 0 2rem 3rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    min-height: 100vh;
}

.page-header {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    margin: 0 -2rem 2rem;
    padding: 2rem 2rem 2.5rem;
    color: white;
    border-radius: 0 0 24px 24px;
    box-shadow: 0 8px 32px -8px rgba(59, 130, 246, 0.3);
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

.filter-select {
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: #f9fafb;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.filter-select:focus {
    border-color: #3b82f6;
    outline: none;
    background: white;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    box-shadow: 0 4px 12px -4px rgba(59, 130, 246, 0.4);
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px -4px rgba(59, 130, 246, 0.6);
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

.btn-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.btn-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.btn-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.invoices-table {
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

.invoice-number {
    font-weight: 700;
    color: #3b82f6;
}

.status-badge {
    display: inline-block;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-paid {
    background: #d1fae5;
    color: #065f46;
}

.status-unpaid {
    background: #fee2e2;
    color: #991b1b;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.btn-sm {
    padding: 0.5rem;
    font-size: 0.875rem;
    min-width: 36px;
    height: 36px;
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
    .billing-main { padding: 0 1rem 2rem; }
    .page-header { margin: 0 -1rem 1.5rem; padding: 1.5rem 1rem 2rem; }
    .header-content { flex-direction: column; align-items: stretch; text-align: center; }
    .filters-grid { grid-template-columns: 1fr; }
    .filter-actions { flex-direction: column; }
    .modern-table { font-size: 0.8rem; }
}
</style>

<main class="billing-main">
    <div class="page-header">
        <div class="header-content">
            <div class="title-section">
                <div class="icon-wrapper">💰</div>
                <div>
                    <h1>Invoice Management</h1>
                    <p class="subtitle">Manage and track all patient invoices</p>
                </div>
            </div>
            <div class="header-actions">
                <a class="btn btn-secondary" href="insurance.php">
                    <span>🏥</span>
                    Insurance Claims
                </a>
                <a class="btn btn-secondary" href="payments.php">
                    <span>💳</span>
                    Payments
                </a>
                <a class="btn btn-primary" href="create_invoice.php">
                    <span>➕</span>
                    Create Invoice
                </a>
            </div>
        </div>
    </div>

    <?= get_flash(); ?>

    <div class="filters-card">
        <form method="get">
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Patient</label>
                    <select name="patient" class="filter-select">
                        <option value="">All Patients</option>
                        <?php while ($p = $patients->fetch_assoc()): ?>
                            <option value="<?= $p['patient_id']; ?>"
                                <?= (!empty($_GET['patient']) && $_GET['patient'] == $p['patient_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($p['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select name="status" class="filter-select">
                        <option value="">All Statuses</option>
                        <?php foreach (['Paid','Unpaid'] as $s): ?>
                            <option value="<?= $s; ?>"
                                <?= (!empty($_GET['status']) && $_GET['status'] == $s) ? 'selected' : ''; ?>>
                                <?= $s; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <span>🔍</span>
                        Apply Filters
                    </button>
                    <a class="btn btn-secondary" href="invoices.php">
                        <span>🔄</span>
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="invoices-table">
        <div class="table-header">
            <h3 class="table-title">Invoice Records</h3>
        </div>

        <table class="modern-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Issue Date</th>
                    <th>Patient</th>
                    <th>Description</th>
                    <th style="text-align: right;">Amount</th>
                    <th>Status</th>
                    <th style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($invoices->num_rows === 0): ?>
                <tr>
                    <td colspan="7" class="empty-state">
                        <div class="empty-icon">📄</div>
                        <div>No invoices found matching your criteria</div>
                    </td>
                </tr>
            <?php else: while ($inv = $invoices->fetch_assoc()): ?>
                <tr>
                    <td>
                        <span class="invoice-number">#<?= $inv['invoice_id']; ?></span>
                    </td>
                    <td><?= date('M j, Y', strtotime($inv['issued_date'])); ?></td>
                    <td><?= htmlspecialchars($inv['patient']); ?></td>
                    <td style="max-width: 250px;">
                        <?= htmlspecialchars(substr($inv['description'] ?? 'No description', 0, 60)); ?>
                        <?= strlen($inv['description'] ?? '') > 60 ? '...' : ''; ?>
                    </td>
                    <td style="text-align: right; font-weight: 600;">
                        $<?= number_format($inv['total_amount'], 2); ?>
                    </td>
                    <td>
                        <span class="status-badge status-<?= strtolower($inv['status']); ?>">
                            <?= $inv['status']; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a class="btn btn-warning btn-sm" href="edit_invoice.php?id=<?= $inv['invoice_id']; ?>" title="Edit Invoice">
                                ✏️
                            </a>
                            <?php if ($inv['status'] === 'Unpaid'): ?>
                                <a class="btn btn-success btn-sm"
                                   href="?toggle=pay&id=<?= $inv['invoice_id']; ?>"
                                   onclick="return confirm('Mark this invoice as paid?');"
                                   title="Mark Paid">
                                   ✓
                                </a>
                            <?php else: ?>
                                <a class="btn btn-danger btn-sm"
                                   href="?toggle=unpay&id=<?= $inv['invoice_id']; ?>"
                                   onclick="return confirm('Mark this invoice as unpaid?');"
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
    </div>
</main>

<?php include BASE_PATH . '/templates/footer.php'; ?>