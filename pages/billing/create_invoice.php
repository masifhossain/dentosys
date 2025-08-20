<?php
/*****************************************************************
 * pages/billing/create_invoice.php
 * ---------------------------------------------------------------
 * Create new invoices for patients
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── ROLE RESTRICTION ─────────
 * Allow only Admin (role_id = 1) or Receptionist (role_id = 3)
 */
if (!is_admin() && ($_SESSION['role'] ?? 0) !== 3) {
    flash('You do not have permission to create invoices.');
    redirect('/dentosys/index.php');
}

/* ───────── Handle form submission ───────── */
if ($_POST) {
    $patient_id = intval($_POST['patient_id'] ?? 0);
    $issued_date = $_POST['issued_date'] ?? date('Y-m-d');
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'Unpaid';

    // Validation
    $errors = [];
    if ($patient_id <= 0) {
        $errors[] = 'Please select a valid patient.';
    }
    if (empty($issued_date)) {
        $errors[] = 'Issue date is required.';
    }
    if ($total_amount <= 0) {
        $errors[] = 'Total amount must be greater than 0.';
    }
    if (empty($description)) {
        $errors[] = 'Description is required.';
    }

    // Verify patient exists
    if ($patient_id > 0) {
        $check_stmt = $conn->prepare("SELECT patient_id FROM Patient WHERE patient_id = ?");
        $check_stmt->bind_param('i', $patient_id);
        $check_stmt->execute();
        if (!$check_stmt->get_result()->fetch_assoc()) {
            $errors[] = 'Selected patient does not exist.';
        }
    }

    if (empty($errors)) {
        try {
            // Insert invoice
            $stmt = $conn->prepare(
                "INSERT INTO Invoice (patient_id, issued_date, total_amount, status, description) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('isdss', $patient_id, $issued_date, $total_amount, $status, $description);
            
            if ($stmt->execute()) {
                $invoice_id = $conn->insert_id;
                flash("✅ Invoice #$invoice_id created successfully!");
                redirect('invoices.php');
            } else {
                $errors[] = 'Database error: ' . $conn->error;
            }
        } catch (Exception $e) {
            $errors[] = 'Error creating invoice: ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        foreach ($errors as $error) {
            flash($error);
        }
    }
}

/* ───────── Get patients for dropdown ───────── */
$patients = get_patients($conn);

/* ───────── HTML ───────── */
include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
    <div class="page-header">
        <h2>💰 Create New Invoice</h2>
        <div class="header-actions">
            <a href="invoices.php" class="btn btn-secondary">← Back to Invoices</a>
        </div>
    </div>

    <?= get_flash(); ?>

    <div class="form-container">
        <form method="post" class="enhanced-form">
            <div class="form-section">
                <h3>📋 Invoice Details</h3>
                
                <div class="form-group">
                    <label for="patient_id">Patient *</label>
                    <select name="patient_id" id="patient_id" required>
                        <option value="">-- Select Patient --</option>
                        <?php 
                        $patients->data_seek(0); // Reset pointer
                        while ($p = $patients->fetch_assoc()): 
                        ?>
                            <option value="<?= $p['patient_id']; ?>" 
                                <?= (isset($_POST['patient_id']) && $_POST['patient_id'] == $p['patient_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($p['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="issued_date">Issue Date *</label>
                        <input type="date" 
                               name="issued_date" 
                               id="issued_date" 
                               value="<?= $_POST['issued_date'] ?? date('Y-m-d'); ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="total_amount">Total Amount ($) *</label>
                        <input type="number" 
                               name="total_amount" 
                               id="total_amount" 
                               value="<?= $_POST['total_amount'] ?? ''; ?>"
                               step="0.01" 
                               min="0.01"
                               placeholder="0.00"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description/Services *</label>
                    <textarea name="description" 
                              id="description" 
                              rows="4" 
                              placeholder="Describe the services provided..."
                              required><?= htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="status">Payment Status</label>
                    <select name="status" id="status">
                        <option value="Unpaid" <?= (($_POST['status'] ?? 'Unpaid') === 'Unpaid') ? 'selected' : ''; ?>>
                            Unpaid
                        </option>
                        <option value="Paid" <?= (($_POST['status'] ?? '') === 'Paid') ? 'selected' : ''; ?>>
                            Paid
                        </option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    💰 Create Invoice
                </button>
                <a href="invoices.php" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</main>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e1e5e9;
}

.form-container {
    max-width: 800px;
    margin: 0 auto;
}

.enhanced-form {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border: 1px solid #e1e5e9;
}

.form-section {
    margin-bottom: 30px;
}

.form-section h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ecf0f1;
}

.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #34495e;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    padding-top: 20px;
    border-top: 1px solid #ecf0f1;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2980b9, #21618c);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background: #7f8c8d;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
