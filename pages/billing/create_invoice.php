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
// Dentists cannot create invoices
if (is_dentist() || (!is_admin() && ($_SESSION['role'] ?? 0) !== 3)) {
    flash('You do not have permission to create invoices.');
    redirect('/dentosys/index.php');
}

/* ───────── Handle form submission ───────── */
if ($_POST) {
    $patient_id   = intval($_POST['patient_id'] ?? 0);
    $issued_date  = $_POST['issued_date'] ?? date('Y-m-d');
    $status       = $_POST['status'] ?? 'Unpaid';
    $notes        = trim($_POST['notes'] ?? '');
    $rawLineItems = $_POST['line_items_json'] ?? '';
    $description  = trim($_POST['description'] ?? ''); // fallback legacy
    $computedTotal = 0.0;
    $lineItems = [];

    // Decode line items if provided
    if ($rawLineItems) {
        $decoded = json_decode($rawLineItems, true);
        if (is_array($decoded)) {
            foreach ($decoded as $it) {
                $label = trim($it['label'] ?? '');
                $amount = floatval($it['amount'] ?? 0);
                if ($label !== '' && $amount > 0) {
                    $lineItems[] = [ 'label' => $label, 'amount' => $amount ];
                    $computedTotal += $amount;
                }
            }
        }
    }

    // Build description if we have structured items
    if ($lineItems) {
        $lines = [];
        foreach ($lineItems as $li) { $lines[] = '- ' . $li['label'] . ' (A$' . number_format($li['amount'],2) . ')'; }
        if ($notes !== '') { $lines[] = ''; $lines[] = 'Notes: ' . $notes; }
        $description = implode("\n", $lines);
    }

    // If still no description fall back to notes
    if ($description === '' && $notes !== '') { $description = $notes; }

    $total_amount = $lineItems ? $computedTotal : floatval($_POST['total_amount'] ?? 0);

    // Validation
    $errors = [];
    if ($patient_id <= 0) $errors[] = 'Please select a valid patient.';
    if (empty($issued_date)) $errors[] = 'Issue date is required.';
    if ($total_amount <= 0) $errors[] = 'Total amount must be greater than 0.';
    if (empty($description)) $errors[] = 'At least one line item or description is required.';

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

    if ($errors) { foreach ($errors as $error) flash($error); }
}

/* ───────── Get patients for dropdown ───────── */
$patients = get_patients($conn);

/* ───────── HTML ───────── */
include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main class="invoice-main">
    <div class="invoice-header">
        <div class="title-wrap">
            <div class="icon">🧾</div>
            <div>
                <h1>Create Invoice</h1>
                <p class="subtitle">Add billable services and generate a patient invoice.</p>
            </div>
        </div>
        <a href="invoices.php" class="btn-secondary-lite">← Back</a>
    </div>

    <?= get_flash(); ?>

    <div class="invoice-layout">
        <form method="post" id="invoiceForm" class="invoice-form" novalidate>
            <fieldset class="section-grid">
                <legend class="visually-hidden">Invoice Details</legend>
                <div class="form-group required">
                    <label for="patient_id">Patient</label>
                    <select name="patient_id" id="patient_id" required>
                        <option value="">-- Select Patient --</option>
                        <?php $patients->data_seek(0); while ($p = $patients->fetch_assoc()): ?>
                            <option value="<?= $p['patient_id']; ?>" <?= (isset($_POST['patient_id']) && $_POST['patient_id']==$p['patient_id'])?'selected':''; ?>>
                                <?= htmlspecialchars($p['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small class="hint">Required</small>
                </div>
                <div class="form-group required">
                    <label for="issued_date">Issue Date</label>
                    <input type="date" name="issued_date" id="issued_date" value="<?= htmlspecialchars($_POST['issued_date'] ?? date('Y-m-d')); ?>" required>
                </div>
                <div class="form-group">
                    <label for="status">Payment Status</label>
                    <select name="status" id="status">
                        <option value="Unpaid" <?= (($_POST['status'] ?? 'Unpaid')==='Unpaid')?'selected':''; ?>>Unpaid</option>
                        <option value="Paid" <?= (($_POST['status'] ?? '')==='Paid')?'selected':''; ?>>Paid</option>
                    </select>
                </div>
            </fieldset>

            <!-- Dynamic Line Items -->
            <div class="line-items-wrapper">
                <div class="section-head">
                    <h2>Line Items</h2>
                    <button type="button" id="addLineBtn" class="ghost-btn">＋ Add Item</button>
                </div>
                <div id="lineItems" class="items-list" aria-live="polite"></div>
                <div class="empty-line-hint" id="emptyHint">No items yet. Start by adding a procedure or service.</div>
            </div>

            <div class="summary-bar">
                        <div class="left-col">
                            <div class="totals">
                                <div class="row"><span>Subtotal</span><span id="subtotalDisplay">A$0.00</span></div>
                                <div class="row"><span>Tax (0%)</span><span id="taxDisplay">A$0.00</span></div>
                                <div class="row total"><span>Total</span><span id="totalDisplay">A$0.00</span></div>
                            </div>
                            <div class="notes-inline">
                                <label for="notes">Notes (optional)</label>
                                <textarea name="notes" id="notes" rows="3" placeholder="Add any billing notes or internal remarks."><?= htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="actions">
                    <input type="hidden" name="line_items_json" id="line_items_json" value="">
                    <input type="hidden" name="description" id="descriptionHidden" value="<?= htmlspecialchars($_POST['description'] ?? ''); ?>">
                    <input type="hidden" name="total_amount" id="total_amount" value="<?= htmlspecialchars($_POST['total_amount'] ?? ''); ?>">
                    <button type="submit" class="btn-primary-lg" id="submitBtn">💰 Create Invoice</button>
                    <a href="invoices.php" class="btn-secondary-lite alt">Cancel</a>
                </div>
            </div>
        </form>

        <aside class="invoice-side">
            <div class="panel">
                <h3>🛈 Tips</h3>
                <ul>
                    <li>Break down procedures into separate line items.</li>
                    <li>Include clear, patient‑friendly labels.</li>
                    <li>Status "Paid" immediately marks it as settled.</li>
                    <li>Use notes for internal remarks (not printed).</li>
                </ul>
            </div>
            <div class="panel tight">
                <h3>🔒 Security</h3>
                <p class="small">All invoice creations are logged with your user account.</p>
            </div>
        </aside>
    </div>
</main>

<style>
    .invoice-main { padding:0 2rem 3rem; }
    .invoice-header { display:flex; justify-content:space-between; align-items:center; margin:0 0 1.75rem; gap:1rem; flex-wrap:wrap; }
    .invoice-header h1 { margin:0 0 .25rem; font-size:2.1rem; letter-spacing:.5px; }
    .invoice-header .subtitle { margin:0; color:#64748b; font-size:.95rem; }
    .invoice-header .icon { font-size:2.75rem; filter:drop-shadow(0 4px 8px rgba(0,0,0,.15)); }
    .title-wrap { display:flex; gap:1.1rem; align-items:center; }
    .btn-secondary-lite { background:#fff; border:2px solid #e2e8f0; color:#1e293b; padding:.8rem 1.25rem; border-radius:12px; font-weight:600; text-decoration:none; transition:.25s; }
    .btn-secondary-lite:hover { border-color:#4facfe; color:#2563eb; }
    .btn-secondary-lite.alt { background:#f1f5f9; }

    .invoice-layout { display:grid; grid-template-columns:minmax(0,1fr) 320px; gap:2rem; align-items:start; }
    @media (max-width:1100px){ .invoice-layout { grid-template-columns:1fr; } .invoice-side { order:-1; } }

    .invoice-form { background:#fff; border-radius:22px; padding:2rem 2.25rem 2.5rem; box-shadow:0 10px 30px -8px rgba(0,0,0,.12); position:relative; overflow:hidden; }
    .invoice-form:before { content:""; position:absolute; inset:0; background:radial-gradient(circle at 85% 15%, rgba(0,150,255,.08), transparent 60%); pointer-events:none; }
    fieldset.section-grid { border:0; margin:0 0 1.5rem; padding:0; display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1.25rem 1.5rem; }
    .form-group { display:flex; flex-direction:column; gap:.4rem; }
    .form-group.required label:after { content:'*'; color:#e53e3e; margin-left:4px; }
    .form-group label { font-weight:600; font-size:.9rem; color:#1e293b; letter-spacing:.5px; }
    .form-group select, .form-group input, .form-group textarea { border:2px solid #e2e8f0; background:#f8fafc; padding:.85rem .95rem; border-radius:12px; font-size:.95rem; transition:.25s; font-family:inherit; }
    .form-group select:focus, .form-group input:focus, .form-group textarea:focus { outline:none; border-color:#4facfe; background:#fff; box-shadow:0 0 0 3px rgba(79,172,254,.15); }
    .form-group small.hint { color:#64748b; font-size:.65rem; letter-spacing:.5px; }

    .line-items-wrapper { margin:0 0 1.5rem; }
    .section-head { display:flex; align-items:center; justify-content:space-between; margin:0 0 .75rem; }
    .section-head h2 { margin:0; font-size:1.05rem; letter-spacing:.5px; }
    .ghost-btn { background:#f1f5f9; border:2px dashed #cbd5e1; color:#475569; padding:.55rem .9rem; border-radius:10px; font-size:.8rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:.4rem; transition:.25s; }
    .ghost-btn:hover { background:#e2e8f0; border-color:#94a3b8; }

    .items-list { display:grid; gap:.75rem; }
    .invoice-item { display:grid; grid-template-columns:1fr 110px 38px; gap:.6rem; background:#f8fafc; padding:.65rem .65rem .65rem .85rem; border:1px solid #e2e8f0; border-radius:14px; align-items:center; position:relative; }
    .invoice-item input { background:transparent; border:0; padding:.55rem .6rem; border-radius:8px; font-size:.85rem; }
    .invoice-item input:focus { outline:none; background:#fff; box-shadow:0 0 0 3px rgba(79,172,254,.15); }
    .remove-item { background:#fff; border:2px solid #e2e8f0; width:34px; height:34px; display:flex; align-items:center; justify-content:center; border-radius:10px; cursor:pointer; color:#dc2626; font-size:1rem; transition:.25s; }
    .remove-item:hover { background:#fee2e2; border-color:#fecaca; }
    .empty-line-hint { font-size:.75rem; color:#64748b; background:#f1f5f9; padding:.6rem .9rem; border-radius:10px; display:none; }

        .notes-inline { margin-top:1rem; display:flex; flex-direction:column; gap:.4rem; }
        .notes-inline textarea { border:2px solid #e2e8f0; background:#f8fafc; padding:.75rem .85rem; border-radius:12px; min-height:90px; font-size:.85rem; resize:vertical; }
        .notes-inline textarea:focus { outline:none; border-color:#4facfe; background:#fff; box-shadow:0 0 0 3px rgba(79,172,254,.15); }

        .summary-bar { display:flex; flex-wrap:wrap; gap:1.75rem; justify-content:space-between; align-items:stretch; padding:1.25rem 1.4rem 1.4rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:18px; }
        .left-col { flex:1 1 380px; display:flex; flex-direction:column; }
        .totals { display:grid; gap:.4rem; min-width:220px; }
    .totals .row { display:flex; justify-content:space-between; font-size:.8rem; color:#475569; }
    .totals .row.total { font-size:1rem; font-weight:700; color:#1e293b; border-top:1px dashed #cbd5e1; padding-top:.5rem; margin-top:.2rem; }
    .actions { display:flex; gap:.75rem; align-items:center; }
    .btn-primary-lg { background:linear-gradient(135deg,#4facfe,#00c6ff); color:#fff; border:none; padding:.95rem 1.75rem; border-radius:14px; font-size:.95rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:.5rem; box-shadow:0 6px 16px -4px rgba(0,150,255,.4); position:relative; overflow:hidden; }
    .btn-primary-lg:hover { transform:translateY(-2px); }
    .btn-primary-lg:active { transform:translateY(0); }
    .btn-primary-lg.loading { pointer-events:none; opacity:.7; }

    .invoice-side { display:grid; gap:1.5rem; }
    .panel { background:#fff; border-radius:22px; padding:1.5rem 1.5rem 1.75rem; box-shadow:0 10px 30px -8px rgba(0,0,0,.12); }
    .panel.tight { padding:1.25rem 1.25rem 1.35rem; }
    .panel h3 { margin:0 0 .75rem; font-size:1rem; letter-spacing:.5px; }
    .panel ul { list-style:none; padding:0; margin:0; display:grid; gap:.55rem; }
    .panel li { position:relative; padding-left:1.05rem; font-size:.75rem; color:#475569; line-height:1.1rem; }
    .panel li:before { content:'›'; position:absolute; left:0; top:0; color:#4facfe; font-weight:700; }
    .small { font-size:.65rem; color:#64748b; }

    .visually-hidden { position:absolute !important; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0 0 0 0); border:0; }

        @media (max-width:640px){
        .invoice-main { padding:0 1.25rem 2.5rem; }
        .invoice-form, .panel { padding:1.5rem 1.25rem 1.75rem; border-radius:18px; }
        .summary-bar { flex-direction:column; align-items:stretch; }
        .actions { flex-direction:column; width:100%; }
        .btn-primary-lg, .btn-secondary-lite { width:100%; justify-content:center; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const addBtn = document.getElementById('addLineBtn');
    const list = document.getElementById('lineItems');
    const emptyHint = document.getElementById('emptyHint');
    const subtotalDisplay = document.getElementById('subtotalDisplay');
    const taxDisplay = document.getElementById('taxDisplay');
    const totalDisplay = document.getElementById('totalDisplay');
    const hiddenJson = document.getElementById('line_items_json');
    const hiddenDesc = document.getElementById('descriptionHidden');
    const totalAmount = document.getElementById('total_amount');
    const form = document.getElementById('invoiceForm');
    const submitBtn = document.getElementById('submitBtn');

        const TAX_RATE = 0; // Set >0 (e.g., 0.10) for GST if needed.
        const LOCALE = 'en-AU';
        const CURRENCY = 'AUD';
    let items = [];

    function render() {
        list.innerHTML = '';
        items.forEach((it, idx) => {
            const row = document.createElement('div');
            row.className = 'invoice-item';
            row.innerHTML = `\n        <input type="text" placeholder="Service / Procedure" value="${it.label}" aria-label="Item label">\n        <input type="number" step="0.01" min="0.01" value="${it.amount}" aria-label="Item amount">\n        <button type="button" class="remove-item" aria-label="Remove">×</button>`;
            const [labelInput, amountInput, removeBtn] = row.querySelectorAll('input,button');
            labelInput.addEventListener('input', () => { it.label = labelInput.value; syncDescription(); });
            amountInput.addEventListener('input', () => { it.amount = parseFloat(amountInput.value)||0; updateTotals(); });
            removeBtn.addEventListener('click', () => { items.splice(idx,1); render(); });
            list.appendChild(row);
        });
        emptyHint.style.display = items.length ? 'none':'block';
        updateTotals();
    }

    function updateTotals() {
        const subtotal = items.reduce((s,i)=> s + (i.amount>0?i.amount:0),0);
        const tax = subtotal * TAX_RATE;
        const total = subtotal + tax;
        subtotalDisplay.textContent = subtotal.toLocaleString(LOCALE,{style:'currency',currency:CURRENCY});
        taxDisplay.textContent = tax.toLocaleString(LOCALE,{style:'currency',currency:CURRENCY});
        totalDisplay.textContent = total.toLocaleString(LOCALE,{style:'currency',currency:CURRENCY});
        totalAmount.value = total.toFixed(2);
        syncDescription();
    }

    function syncDescription() {
        hiddenJson.value = JSON.stringify(items.filter(i=>i.label && i.amount>0));
        if (items.length) {
            const lines = items.filter(i=>i.label && i.amount>0).map(i=>`- ${i.label} (A$${i.amount.toFixed(2)})`);
            hiddenDesc.value = lines.join('\n');
        }
    }

    addBtn.addEventListener('click', () => { items.push({label:'', amount:0}); render(); });

    form.addEventListener('submit', e => {
        if (!form.reportValidity()) return; // native validation
        if (!items.length && !hiddenDesc.value.trim()) {
            alert('Add at least one line item or description.');
            e.preventDefault();
            return;
        }
        submitBtn.classList.add('loading');
        submitBtn.textContent = 'Saving...';
    });

    // Repopulate from posted values if validation failed server-side
    <?php if (!empty($_POST['line_items_json'])): ?>
        try { const prev = JSON.parse(<?php echo json_encode($_POST['line_items_json']); ?>); if (Array.isArray(prev)) { items = prev; render(); } } catch(e){}
    <?php else: ?>
        render();
    <?php endif; ?>
});
</script>

<?php include BASE_PATH . '/templates/footer.php'; ?>
