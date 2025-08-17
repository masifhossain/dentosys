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
if (!is_admin() && ($_SESSION['role'] ?? 0) !== 3) {
    flash('You do not have permission to manage insurance claims.');
    redirect('/index.php');
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
<main>
  <h2><?= $editing ? 'Edit Insurance Claim' : 'Submit New Insurance Claim'; ?></h2>
  <?= get_flash(); ?>

  <div class="card" style="max-width: 900px;">
    <form method="post" id="claimForm">
      <?php if ($editing): ?>
        <input type="hidden" name="claim_id" value="<?= $claim['claim_id']; ?>">
      <?php endif; ?>
      
      <div class="grid grid-cols-2 gap-4">
        <!-- Patient Selection -->
        <div class="form-group">
          <label class="form-label">Patient *</label>
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
          <label class="form-label">Related Invoice (Optional)</label>
          <select name="invoice_id" id="invoiceSelect" class="form-select">
            <option value="">Select Invoice</option>
            <?php foreach ($invoices as $inv): ?>
              <option value="<?= $inv['invoice_id']; ?>"
                <?= ($claim && $claim['invoice_id'] == $inv['invoice_id']) ? 'selected' : ''; ?>>
                #<?= $inv['invoice_id']; ?> - $<?= number_format($inv['total_amount'], 2); ?> 
                (<?= date('M d, Y', strtotime($inv['issued_date'])); ?>) - <?= $inv['status']; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Insurance Provider -->
        <div class="form-group">
          <label class="form-label">Insurance Provider *</label>
          <input type="text" name="insurance_provider" required class="form-input" 
                 value="<?= $claim ? htmlspecialchars($claim['insurance_provider']) : ''; ?>"
                 placeholder="e.g., Blue Cross Blue Shield">
        </div>

        <!-- Policy Number -->
        <div class="form-group">
          <label class="form-label">Policy Number *</label>
          <input type="text" name="policy_number" required class="form-input" 
                 value="<?= $claim ? htmlspecialchars($claim['policy_number']) : ''; ?>"
                 placeholder="Insurance policy number">
        </div>

        <!-- Claim Amount -->
        <div class="form-group">
          <label class="form-label">Claim Amount *</label>
          <input type="number" step="0.01" min="0" name="claim_amount" required class="form-input" 
                 value="<?= $claim ? $claim['claim_amount'] : ''; ?>"
                 placeholder="0.00">
        </div>

        <!-- Claim Reference -->
        <div class="form-group">
          <label class="form-label">Claim Reference Number</label>
          <input type="text" name="claim_reference" class="form-input" 
                 value="<?= $claim ? htmlspecialchars($claim['claim_reference']) : ''; ?>"
                 placeholder="Internal reference number">
        </div>
      </div>

      <!-- Notes -->
      <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea name="notes" rows="4" class="form-textarea" 
                  placeholder="Additional notes about this claim..."><?= $claim ? htmlspecialchars($claim['notes']) : ''; ?></textarea>
      </div>

      <?php if ($editing && $claim): ?>
        <!-- Show current status for editing -->
        <div class="form-group">
          <label class="form-label">Current Status</label>
          <div class="p-4" style="background: #f8f9fa; border-radius: 8px;">
            <span class="badge badge-<?= 
              $claim['status'] === 'Approved' ? 'success' : 
              ($claim['status'] === 'Denied' ? 'danger' : 'warning'); ?>">
              <?= $claim['status']; ?>
            </span>
            <?php if ($claim['submitted_date']): ?>
              <span style="margin-left: 15px;">Submitted: <?= date('M d, Y', strtotime($claim['submitted_date'])); ?></span>
            <?php endif; ?>
            <?php if ($claim['processed_date']): ?>
              <span style="margin-left: 15px;">Processed: <?= date('M d, Y', strtotime($claim['processed_date'])); ?></span>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <div style="margin-top: 30px;">
        <button type="submit" class="btn btn-primary">
          <?= $editing ? 'Update Claim' : 'Create Claim'; ?>
        </button>
        <a href="insurance.php" class="btn btn-outline">Cancel</a>
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

<style>
.badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}
.badge-success { background: #d1f2eb; color: #0f5132; }
.badge-danger { background: #f8d7da; color: #721c24; }
.badge-warning { background: #fff3cd; color: #664d03; }
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
