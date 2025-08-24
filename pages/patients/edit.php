<?php
/*****************************************************************
 * pages/patients/edit.php
 * ---------------------------------------------------------------
 * Edit an existing patient record.
 *   URL: edit.php?id=123
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2 levels
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Check & fetch patient ───────── */
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    flash('Invalid patient ID.');
    redirect('list.php');
}

// Check if dentist can access this patient
require_patient_access($id);

$stmt = $conn->prepare("
    SELECT p.*, u.user_id, u.email as user_email, u.is_active 
    FROM Patient p 
    LEFT JOIN UserTbl u ON p.user_id = u.user_id 
    WHERE p.patient_id = ? LIMIT 1
");
$stmt->bind_param('i', $id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    flash('Patient not found.');
    redirect('list.php');
}

/* ───────── Handle form submission ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fn   = $conn->real_escape_string(trim($_POST['first_name']));
    $ln   = $conn->real_escape_string(trim($_POST['last_name']));
    $dob  = $conn->real_escape_string($_POST['dob']);
    $eml  = $conn->real_escape_string(trim($_POST['email']));
    $ph   = $conn->real_escape_string(trim($_POST['phone']));
    $addr = $conn->real_escape_string(trim($_POST['address']));
    $createAccount = isset($_POST['create_account']) && $_POST['create_account'] === '1';

    if ($fn === '' || $ln === '') {
        flash('First and last name are required.');
    } else {
        $upd = $conn->prepare(
          "UPDATE Patient SET first_name=?, last_name=?, dob=?, email=?, phone=?, address=?
           WHERE patient_id=? LIMIT 1"
        );
        $upd->bind_param('ssssssi', $fn, $ln, $dob, $eml, $ph, $addr, $id);
        if ($upd->execute()) {
            // Log patient update
            log_data_change('update', 'Patient', $id, "Patient updated: $fn $ln");
            
            $successMessage = 'Patient updated successfully.';
            
            // Create user account if requested and patient doesn't have one
            if ($createAccount && !$patient['user_id'] && !empty($eml) && filter_var($eml, FILTER_VALIDATE_EMAIL)) {
                $userId = create_patient_user_account($eml, $fn, $ln, $id);
                
                if ($userId) {
                    $tempPassword = $_SESSION['temp_passwords'][$eml] ?? 'N/A';
                    $successMessage .= "<br><br><strong>🔐 Patient Account Created!</strong><br>";
                    $successMessage .= "📧 Email: <strong>$eml</strong><br>";
                    $successMessage .= "🔑 Temporary Password: <strong>$tempPassword</strong><br>";
                    $successMessage .= "<small>⚠️ Please inform the patient of their login credentials.</small>";
                } else {
                    $successMessage .= "<br><br><strong>⚠️ Note:</strong> Patient account creation failed.";
                }
            }
            
            flash($successMessage, 'success');
            redirect("view.php?id=$id");
        } else {
            flash('DB error: '.$conn->error);
        }
    }
}

/* ───────── HTML ───────── */
include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main class="patient-edit-main">
  <div class="page-intro">
    <div class="icon">🛠️</div>
    <div>
      <h1>Edit Patient</h1>
      <p class="sub">Update demographic and contact information.</p>
    </div>
    <div class="actions">
      <a href="view.php?id=<?= $id; ?>" class="btn-secondary">← Back</a>
    </div>
  </div>
  <?= get_flash(); ?>
  <form method="post" id="editPatientForm" class="edit-form" novalidate>
    <fieldset class="grid-fields">
      <legend class="visually-hidden">Patient details</legend>
      <div class="form-group required">
        <label for="first_name">First Name</label>
        <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($patient['first_name']); ?>" required autocomplete="given-name" placeholder="e.g. John">
      </div>
      <div class="form-group required">
        <label for="last_name">Last Name</label>
        <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($patient['last_name']); ?>" required autocomplete="family-name" placeholder="e.g. Doe">
      </div>
      <div class="form-group required">
        <label for="dob">Date of Birth</label>
        <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($patient['dob']); ?>" required>
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($patient['email']); ?>" autocomplete="email" placeholder="name@example.com">
      </div>
      <div class="form-group">
        <label for="phone">Phone</label>
        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($patient['phone']); ?>" autocomplete="tel" placeholder="(555) 000-1234" pattern="[0-9+()\-\s]{7,}">
      </div>
      <div class="form-group full">
        <label for="address">Address</label>
        <textarea id="address" name="address" rows="3" placeholder="Street, City, State / Region"><?= htmlspecialchars($patient['address']); ?></textarea>
      </div>
      
      <?php if (!$patient['user_id']): ?>
      <div class="form-group full">
        <div class="account-creation-panel">
          <div class="panel-header">
            <span class="panel-icon">🔐</span>
            <div>
              <h4>Patient Portal Access</h4>
              <p>This patient doesn't have a portal account yet.</p>
            </div>
          </div>
          <div class="checkbox-group">
            <input type="checkbox" name="create_account" id="create_account" value="1">
            <label for="create_account" class="checkbox-label">
              Create Patient Portal Account
              <small class="checkbox-hint">Generate login credentials for this patient. Requires email address.</small>
            </label>
          </div>
        </div>
      </div>
      <?php else: ?>
      <div class="form-group full">
        <div class="account-status-panel">
          <span class="status-icon">✅</span>
          <div class="status-info">
            <strong>Portal Account Active</strong>
            <p>This patient can log in at: <code><?= htmlspecialchars($patient['user_email'] ?? $patient['email']); ?></code></p>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </fieldset>
    <div class="form-actions">
      <button type="submit" class="btn-primary" id="saveBtn">💾 Save Changes</button>
      <a href="view.php?id=<?= $id; ?>" class="btn-secondary alt">Cancel</a>
    </div>
  </form>
</main>

<style>
  .patient-edit-main { padding:0 2rem 3rem; }
  .page-intro { display:flex; align-items:center; gap:1.1rem; margin:0 0 1.75rem; flex-wrap:wrap; }
  .page-intro .icon { font-size:2.6rem; filter:drop-shadow(0 4px 8px rgba(0,0,0,.15)); }
  .page-intro h1 { margin:0 0 .25rem; font-size:2.1rem; letter-spacing:.5px; }
  .page-intro .sub { margin:0; color:#64748b; font-size:.95rem; }
  .actions { margin-left:auto; }
  .edit-form { background:#fff; border-radius:22px; padding:2rem 2.25rem 2.35rem; box-shadow:0 10px 30px -8px rgba(0,0,0,.12); position:relative; overflow:hidden; max-width:960px; }
  .edit-form:before { content:""; position:absolute; inset:0; background:radial-gradient(circle at 85% 15%, rgba(0,150,255,.08), transparent 60%); pointer-events:none; }
  fieldset.grid-fields { border:0; margin:0; padding:0; display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1.25rem 1.5rem; }
  .form-group { display:flex; flex-direction:column; gap:.45rem; }
  .form-group.full { grid-column:1/-1; }
  .form-group.required > label:after { content:'*'; color:#e53e3e; margin-left:4px; }
  .form-group label { font-weight:600; font-size:.9rem; color:#1e293b; letter-spacing:.5px; }
  .form-group input, .form-group textarea { border:2px solid #e2e8f0; background:#f8fafc; padding:.85rem .95rem; border-radius:12px; font-size:.95rem; transition:.25s; font-family:inherit; }
  .form-group input:focus, .form-group textarea:focus { outline:none; border-color:#4facfe; background:#fff; box-shadow:0 0 0 3px rgba(79,172,254,.15); }
  .form-group input.error { border-color:#e53e3e; background:#fff5f5; }
  
  .account-creation-panel, .account-status-panel { background:#f8fafc; border:2px solid #e2e8f0; border-radius:12px; padding:1.5rem; }
  .panel-header { display:flex; align-items:flex-start; gap:1rem; margin-bottom:1rem; }
  .panel-icon { font-size:1.5rem; }
  .panel-header h4 { margin:0 0 0.25rem 0; font-size:1rem; color:#1e293b; }
  .panel-header p { margin:0; font-size:0.85rem; color:#64748b; }
  .checkbox-group { display:flex; align-items:flex-start; gap:0.75rem; }
  .checkbox-group input[type="checkbox"] { margin:0; transform:scale(1.25); accent-color:#4facfe; cursor:pointer; }
  .checkbox-label { cursor:pointer; font-weight:500; color:#1e293b; margin:0; display:flex; flex-direction:column; gap:0.25rem; }
  .checkbox-hint { font-size:0.75rem; color:#64748b; font-weight:400; }
  .account-status-panel { display:flex; align-items:center; gap:1rem; background:#f0f9ff; border-color:#93c5fd; }
  .status-icon { font-size:1.5rem; }
  .status-info strong { color:#1e293b; font-size:0.95rem; }
  .status-info p { margin:0.25rem 0 0 0; font-size:0.8rem; color:#64748b; }
  .status-info code { background:#e2e8f0; padding:0.25rem 0.5rem; border-radius:4px; font-size:0.75rem; }
  
  .form-actions { display:flex; gap:.85rem; margin-top:1.75rem; }
  .btn-primary, .btn-secondary { text-decoration:none; font-weight:600; border-radius:14px; padding:.9rem 1.6rem; font-size:.95rem; display:inline-flex; align-items:center; gap:.5rem; }
  .btn-primary { background:linear-gradient(135deg,#4facfe,#00c6ff); color:#fff; box-shadow:0 6px 16px -4px rgba(0,150,255,.4); border:none; cursor:pointer; }
  .btn-primary:hover { transform:translateY(-2px); }
  .btn-secondary { background:#fff; border:2px solid #e2e8f0; color:#1e293b; }
  .btn-secondary.alt { background:#f1f5f9; }
  .btn-secondary:hover { border-color:#4facfe; color:#2563eb; }
  .visually-hidden { position:absolute !important; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0 0 0 0); border:0; }
  @media (max-width:640px){ .patient-edit-main { padding:0 1.25rem 2.5rem; } .edit-form { padding:1.5rem 1.25rem 1.75rem; border-radius:18px; } .form-actions { flex-direction:column; } .btn-primary, .btn-secondary { width:100%; justify-content:center; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('editPatientForm');
  const saveBtn = document.getElementById('saveBtn');
  const required = ['first_name','last_name','dob'];
  const emailField = document.getElementById('email');
  const createAccountCheckbox = document.getElementById('create_account');

  // Handle checkbox dependency on email (if checkbox exists)
  if (createAccountCheckbox) {
    function updateCheckboxState() {
      const hasEmail = emailField.value.trim() !== '';
      if (!hasEmail) {
        createAccountCheckbox.checked = false;
        createAccountCheckbox.disabled = true;
      } else {
        createAccountCheckbox.disabled = false;
      }
    }

    emailField.addEventListener('input', updateCheckboxState);
    updateCheckboxState(); // Initial check
  }

  form.addEventListener('submit', e => {
    let invalid=false; 
    required.forEach(id=>{ 
      const el=document.getElementById(id); 
      if(!el.value.trim()){ el.classList.add('error'); invalid=true; } 
      else el.classList.remove('error'); 
    });

    // Check if create account is checked but no email
    if (createAccountCheckbox && createAccountCheckbox.checked && !emailField.value.trim()) {
      emailField.classList.add('error');
      invalid = true;
      alert('Email is required to create a patient portal account.');
    }

    if(invalid){ e.preventDefault(); return; }
    saveBtn.classList.add('loading'); 
    saveBtn.textContent='Saving...';
  });

  // Auto-clear error styles on input
  document.querySelectorAll('input, textarea').forEach(field => {
    field.addEventListener('input', () => field.classList.remove('error'));
  });
});
</script>

<?php include BASE_PATH . '/templates/footer.php'; ?>