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
// Dentists cannot manage insurance claims
if (is_dentist() || (!is_admin() && ($_SESSION['role'] ?? 0) !== 3)) {
    flash('You do not have permission to manage insurance claims.');
    redirect('/dentosys/index.php');
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

<style>
.insurance-main {
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

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 16px;
    text-align: center;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #059669;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #64748b;
    font-size: 0.875rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
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

.btn-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
}

.claims-table {
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

.claim-id {
    font-weight: 700;
    color: #059669;
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

.status-draft { background: #f3f4f6; color: #374151; }
.status-submitted { background: #dbeafe; color: #1e40af; }
.status-under-review { background: #fef3c7; color: #92400e; }
.status-approved { background: #d1fae5; color: #065f46; }
.status-denied { background: #fee2e2; color: #991b1b; }
.status-paid { background: #e0f2fe; color: #0c4a6e; }

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
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
    .insurance-main { padding: 0 1rem 2rem; }
    .page-header { margin: 0 -1rem 1.5rem; padding: 1.5rem 1rem 2rem; }
    .header-content { flex-direction: column; align-items: stretch; text-align: center; }
    .filters-grid { grid-template-columns: 1fr; }
    .filter-actions { flex-direction: column; }
    .stats-grid { grid-template-columns: 1fr; }
    .modern-table { font-size: 0.8rem; }
    .action-buttons { flex-direction: column; }
}
</style>

<main class="insurance-main">
    <div class="page-header">
        <div class="header-content">
            <div class="title-section">
                <div class="icon-wrapper">🏥</div>
                <div>
                    <h1>Insurance Claims</h1>
                    <p class="subtitle">Manage and track insurance claim submissions</p>
                </div>
            </div>
            <div class="header-actions">
                <a class="btn btn-secondary" href="invoices.php">
                    <span>←</span>
                    Back to Invoices
                </a>
                <a class="btn btn-primary" href="submit_claim.php">
                    <span>➕</span>
                    Submit New Claim
                </a>
            </div>
        </div>
    </div>

    <?= get_flash(); ?>

    <!-- Summary Statistics -->
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
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $summary['total_claims']; ?></div>
            <div class="stat-label">Total Claims</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $summary['pending_claims']; ?></div>
            <div class="stat-label">Pending Review</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">$<?= number_format($summary['approved_amount'], 2); ?></div>
            <div class="stat-label">Approved Amount</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">$<?= number_format($summary['paid_amount'], 2); ?></div>
            <div class="stat-label">Paid Amount</div>
        </div>
    </div>

    <!-- Filters -->
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
                        <?php foreach(['Draft', 'Submitted', 'Under Review', 'Approved', 'Denied', 'Paid'] as $status): ?>
                            <option value="<?= $status; ?>"
                                <?= (!empty($_GET['status']) && $_GET['status'] === $status) ? 'selected' : ''; ?>>
                                <?= $status; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Insurance Provider</label>
                    <select name="provider" class="filter-select">
                        <option value="">All Providers</option>
                        <?php while ($prov = $providers->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($prov['insurance_provider']); ?>"
                                <?= (!empty($_GET['provider']) && $_GET['provider'] === $prov['insurance_provider']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($prov['insurance_provider']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <span>🔍</span>
                        Apply Filters
                    </button>
                    <a href="insurance.php" class="btn btn-secondary">
                        <span>🔄</span>
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Claims Table -->
    <div class="claims-table">
        <div class="table-header">
            <h3 class="table-title">Insurance Claims</h3>
        </div>

        <table class="modern-table">
            <thead>
                <tr>
                    <th>Claim ID</th>
                    <th>Patient</th>
                    <th>Insurance Provider</th>
                    <th>Policy Number</th>
                    <th style="text-align: right;">Claim Amount</th>
                    <th>Status</th>
                    <th>Submitted Date</th>
                    <th style="width: 180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($claims->num_rows === 0): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <div class="empty-icon">🏥</div>
                            <div>No insurance claims found matching your criteria</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($claim = $claims->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="claim-id">#<?= $claim['claim_id']; ?></span>
                            </td>
                            <td><?= htmlspecialchars($claim['patient_name']); ?></td>
                            <td><?= htmlspecialchars($claim['insurance_provider']); ?></td>
                            <td><?= htmlspecialchars($claim['policy_number']); ?></td>
                            <td style="text-align: right; font-weight: 600;">
                                $<?= number_format($claim['claim_amount'], 2); ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $claim['status'])); ?>">
                                    <?= $claim['status']; ?>
                                </span>
                            </td>
                            <td><?= $claim['submitted_date'] ? date('M j, Y', strtotime($claim['submitted_date'])) : '-'; ?></td>
                            <td>
                                <div class="action-buttons">
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
                                       class="btn btn-sm btn-secondary">✏️</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include BASE_PATH . '/templates/footer.php'; ?>
