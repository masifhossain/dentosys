<?php
/*****************************************************************
 * pages/billing/submit_claim.php
 * ---------------------------------------------------------------
 * Submit new insurance claim or edit existing one
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

/* ───────── Check if editing existing claim ───────── */
$editing = false;
$claim = null;
if (!empty($_GET['edit'])) {
    $claim_id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM InsuranceClaim WHERE claim_id = $claim_id LIMIT 1");
    $claim = $result->fetch_assoc();
    if ($claim) {
        $editing = true;
    }
}

/* ───────── Handle form submission ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $invoice_id = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : NULL;
    $insurance_provider = $conn->real_escape_string($_POST['insurance_provider']);
    $policy_number = $conn->real_escape_string($_POST['policy_number']);
    $claim_amount = floatval($_POST['claim_amount']);
    $notes = $conn->real_escape_string($_POST['notes']);
    $claim_reference = $conn->real_escape_string($_POST['claim_reference']);

    if ($editing) {
        // Update existing claim
        $claim_id = intval($_POST['claim_id']);
        $sql = "UPDATE InsuranceClaim SET 
                patient_id = $patient_id,
                invoice_id = " . ($invoice_id ? $invoice_id : "NULL") . ",
                insurance_provider = '$insurance_provider',
                policy_number = '$policy_number',
                claim_amount = $claim_amount,
                notes = '$notes',
                claim_reference = '$claim_reference'
                WHERE claim_id = $claim_id";
    } else {
        // Create new claim
        $invoice_sql = $invoice_id ? "$invoice_id" : "NULL";
        $sql = "INSERT INTO InsuranceClaim 
                (patient_id, invoice_id, insurance_provider, policy_number, 
                 claim_amount, notes, claim_reference, status)
                VALUES 
                ($patient_id, $invoice_sql, '$insurance_provider', '$policy_number', 
                 $claim_amount, '$notes', '$claim_reference', 'Draft')";
    }

    if ($conn->query($sql)) {
        flash($editing ? 'Claim updated successfully.' : 'Claim created successfully.');
        redirect('insurance.php');
    } else {
        flash('Error: ' . $conn->error, 'error');
    }
}

/* ───────── Get data for dropdowns ───────── */
$patients = get_patients($conn);

// Get invoices for selected patient
$invoices = [];
$selected_patient_id = $claim ? $claim['patient_id'] : (!empty($_GET['patient_id']) ? intval($_GET['patient_id']) : 0);
if ($selected_patient_id) {
    $invoice_query = $conn->query(
        "SELECT invoice_id, total_amount, issued_date, status
         FROM Invoice 
         WHERE patient_id = $selected_patient_id 
         ORDER BY issued_date DESC LIMIT 50"
    );
    while ($row = $invoice_query->fetch_assoc()) {
        $invoices[] = $row;
    }
}

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>

<style>
.submit-claim-main {
    padding: 0 2rem 3rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    min-height: 100vh;
}

.page-header {
    background: linear-gradient(135deg, #7c3aed, #5b21b6);
    margin: 0 -2rem 2rem;
    padding: 2rem 2rem 2.5rem;
    color: white;
    border-radius: 0 0 24px 24px;
    box-shadow: 0 8px 32px -8px rgba(124, 58, 237, 0.3);
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

.form-container {
    max-width: 900px;
    margin: 0 auto;
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 40px -12px rgba(0,0,0,0.15);
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.form-header {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    padding: 2rem;
    border-bottom: 1px solid #e2e8f0;
}

.form-header h2 {
    margin: 0 0 0.5rem;
    color: #1e293b;
    font-size: 1.5rem;
    font-weight: 700;
}

.form-header p {
    margin: 0;
    color: #64748b;
    font-size: 0.95rem;
}

.form-body {
    padding: 2rem;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.required-indicator {
    color: #ef4444;
    font-weight: 700;
}

.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: 1rem 1.25rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    background: #f9fafb;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    transition: all 0.2s ease;
    font-family: inherit;
    box-sizing: border-box;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    border-color: #7c3aed;
    background: white;
    outline: none;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    transform: translateY(-1px);
}

.form-select {
    cursor: pointer;
    background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%237c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6,9 12,15 18,9"></polyline></svg>');
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 1rem;
    appearance: none;
    padding-right: 3rem;
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
    line-height: 1.5;
}

.status-display {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
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

.status-success { background: #d1fae5; color: #065f46; }
.status-danger { background: #fee2e2; color: #991b1b; }
.status-warning { background: #fef3c7; color: #92400e; }

.status-info {
    margin-left: 1rem;
    color: #64748b;
    font-size: 0.875rem;
}

.form-footer {
    background: #f8fafc;
    padding: 1.5rem 2rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 2px solid transparent;
    cursor: pointer;
    min-width: 120px;
    justify-content: center;
}

.btn-primary {
    background: linear-gradient(135deg, #7c3aed, #5b21b6);
    color: white;
    box-shadow: 0 4px 12px -4px rgba(124, 58, 237, 0.4);
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px -4px rgba(124, 58, 237, 0.6);
}

.btn-secondary {
    background: white;
    color: #374151;
    border-color: #e5e7eb;
    box-shadow: 0 2px 8px -2px rgba(0,0,0,0.1);
}

.btn-secondary:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

.input-helper {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 0.75rem;
    font-size: 0.8rem;
    color: #0369a1;
    margin-top: 0.5rem;
}

@media (max-width: 768px) {
    .submit-claim-main { padding: 0 1rem 2rem; }
    .page-header { margin: 0 -1rem 1.5rem; padding: 1.5rem 1rem 2rem; }
    .header-content { flex-direction: column; align-items: stretch; text-align: center; }
    .form-grid { grid-template-columns: 1fr; gap: 1rem; }
    .form-body, .form-header, .form-footer { padding: 1.5rem; }
    .form-footer { flex-direction: column; }
    .btn { width: 100%; }
}
</style>

<main class="submit-claim-main">
    <div class="page-header">
        <div class="header-content">
            <div class="title-section">
                <div class="icon-wrapper">📋</div>
                <div>
                    <h1><?= $editing ? 'Edit Insurance Claim' : 'Submit New Claim'; ?></h1>
                    <p class="subtitle">Create or update insurance claim submission</p>
                </div>
            </div>
            <div class="header-actions">
                <a class="btn btn-secondary" href="insurance.php">
                    <span>←</span>
                    Back to Claims
                </a>
            </div>
        </div>
    </div>

    <?= get_flash(); ?>

    <div class="form-container">
        <div class="form-header">
            <h2><?= $editing ? 'Edit Claim Details' : 'New Insurance Claim'; ?></h2>
            <p>Fill out the insurance claim information below</p>
        </div>

        <form method="post" id="claimForm">
            <?php if ($editing): ?>
                <input type="hidden" name="claim_id" value="<?= $claim['claim_id']; ?>">
            <?php endif; ?>
            
            <div class="form-body">
                <div class="form-grid">
                    <!-- Patient Selection -->
                    <div class="form-group">
                        <label class="form-label">
                            👤 Patient <span class="required-indicator">*</span>
                        </label>
                        <select name="patient_id" id="patientSelect" required class="form-select">
                            <option value="">Select Patient</option>
                            <?php while ($p = $patients->fetch_assoc()): ?>
                                <option value="<?= $p['patient_id']; ?>"
                                    <?= ($selected_patient_id == $p['patient_id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($p['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Related Invoice (Optional) -->
                    <div class="form-group">
                        <label class="form-label">💰 Related Invoice (Optional)</label>
                        <select name="invoice_id" id="invoiceSelect" class="form-select">
                            <option value="">Select Invoice</option>
                            <?php foreach ($invoices as $inv): ?>
                                <option value="<?= $inv['invoice_id']; ?>"
                                    <?= ($claim && $claim['invoice_id'] == $inv['invoice_id']) ? 'selected' : ''; ?>>
                                    #<?= $inv['invoice_id']; ?> - $<?= number_format($inv['total_amount'], 2); ?> 
                                    (<?= date('M j, Y', strtotime($inv['issued_date'])); ?>) - <?= $inv['status']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="input-helper">
                            <strong>Tip:</strong> Selecting an invoice will auto-populate the claim amount
                        </div>
                    </div>

                    <!-- Insurance Provider -->
                    <div class="form-group">
                        <label class="form-label">
                            🏥 Insurance Provider <span class="required-indicator">*</span>
                        </label>
                        <input type="text" name="insurance_provider" required class="form-input" 
                               value="<?= $claim ? htmlspecialchars($claim['insurance_provider']) : ''; ?>"
                               placeholder="e.g., Blue Cross Blue Shield">
                    </div>

                    <!-- Policy Number -->
                    <div class="form-group">
                        <label class="form-label">
                            📄 Policy Number <span class="required-indicator">*</span>
                        </label>
                        <input type="text" name="policy_number" required class="form-input" 
                               value="<?= $claim ? htmlspecialchars($claim['policy_number']) : ''; ?>"
                               placeholder="Insurance policy number">
                    </div>

                    <!-- Claim Amount -->
                    <div class="form-group">
                        <label class="form-label">
                            💵 Claim Amount <span class="required-indicator">*</span>
                        </label>
                        <input type="number" step="0.01" min="0" name="claim_amount" required class="form-input" 
                               value="<?= $claim ? $claim['claim_amount'] : ''; ?>"
                               placeholder="0.00">
                    </div>

                    <!-- Claim Reference -->
                    <div class="form-group">
                        <label class="form-label">🔖 Claim Reference Number</label>
                        <input type="text" name="claim_reference" class="form-input" 
                               value="<?= $claim ? htmlspecialchars($claim['claim_reference']) : ''; ?>"
                               placeholder="Internal reference number">
                    </div>
                </div>

                <!-- Notes -->
                <div class="form-group full-width">
                    <label class="form-label">📝 Notes</label>
                    <textarea name="notes" rows="4" class="form-textarea" 
                              placeholder="Additional notes about this claim..."><?= $claim ? htmlspecialchars($claim['notes']) : ''; ?></textarea>
                </div>

                <?php if ($editing && $claim): ?>
                    <!-- Show current status for editing -->
                    <div class="form-group full-width">
                        <label class="form-label">📊 Current Status</label>
                        <div class="status-display">
                            <span class="status-badge status-<?= 
                                $claim['status'] === 'Approved' ? 'success' : 
                                ($claim['status'] === 'Denied' ? 'danger' : 'warning'); ?>">
                                <?= $claim['status']; ?>
                            </span>
                            <?php if ($claim['submitted_date']): ?>
                                <span class="status-info">Submitted: <?= date('M j, Y', strtotime($claim['submitted_date'])); ?></span>
                            <?php endif; ?>
                            <?php if ($claim['processed_date']): ?>
                                <span class="status-info">Processed: <?= date('M j, Y', strtotime($claim['processed_date'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-footer">
                <a href="insurance.php" class="btn btn-secondary">
                    <span>✖</span>
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <span>💾</span>
                    <?= $editing ? 'Update Claim' : 'Create Claim'; ?>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
// Load invoices when patient is selected
document.getElementById('patientSelect').addEventListener('change', function() {
    const patientId = this.value;
    const invoiceSelect = document.getElementById('invoiceSelect');
    
    // Clear existing options
    invoiceSelect.innerHTML = '<option value="">Loading...</option>';
    
    if (patientId) {
        // Reload page with patient_id parameter to get invoices
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('patient_id', patientId);
        <?php if ($editing): ?>
        currentUrl.searchParams.set('edit', '<?= $claim['claim_id']; ?>');
        <?php endif; ?>
        window.location.href = currentUrl.toString();
    } else {
        invoiceSelect.innerHTML = '<option value="">Select Invoice</option>';
    }
});

// Auto-populate claim amount from selected invoice
document.getElementById('invoiceSelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
        // Extract amount from option text (format: #123 - $100.00 (Date) - Status)
        const text = selectedOption.text;
        const amountMatch = text.match(/\$([0-9,]+\.?[0-9]*)/);
        if (amountMatch) {
            const amount = amountMatch[1].replace(',', '');
            document.querySelector('input[name="claim_amount"]').value = amount;
        }
    }
});

// Form validation
document.getElementById('claimForm').addEventListener('submit', function(e) {
    const patientId = document.querySelector('select[name="patient_id"]').value;
    const provider = document.querySelector('input[name="insurance_provider"]').value.trim();
    const policyNumber = document.querySelector('input[name="policy_number"]').value.trim();
    const claimAmount = document.querySelector('input[name="claim_amount"]').value;
    
    if (!patientId || !provider || !policyNumber || !claimAmount || parseFloat(claimAmount) <= 0) {
        e.preventDefault();
        alert('Please fill in all required fields with valid values.');
    }
});
</script>

<?php include BASE_PATH . '/templates/footer.php'; ?>
