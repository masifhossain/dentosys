<?php
/*****************************************************************
 * pages/patients/add.php
 * ---------------------------------------------------------------
 * Add a new patient record.
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2 levels
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Handle form submission ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and escape inputs
    $fn   = $conn->real_escape_string(trim($_POST['first_name']));
    $ln   = $conn->real_escape_string(trim($_POST['last_name']));
    $dob  = $conn->real_escape_string($_POST['dob']);
    $eml  = $conn->real_escape_string(trim($_POST['email']));
    $ph   = $conn->real_escape_string(trim($_POST['phone']));
    $addr = $conn->real_escape_string(trim($_POST['address']));
    $createAccount = isset($_POST['create_account']) && $_POST['create_account'] === '1';

    // Basic validation
    if ($fn === '' || $ln === '') {
        flash('First and last name are required.');
    } else {
        $stmt = $conn->prepare(
          "INSERT INTO Patient (first_name, last_name, dob, email, phone, address, created_at)
           VALUES (?,?,?,?,?,?, NOW())"
        );
        $stmt->bind_param('ssssss', $fn, $ln, $dob, $eml, $ph, $addr);

        if ($stmt->execute()) {
            $patientId = $conn->insert_id;
            
            // Log patient creation
            log_data_change('create', 'Patient', $patientId, "Patient created: $fn $ln");
            
            $successMessage = 'Patient added successfully.';
            
            // Auto-create user account if email provided and checkbox checked
            if ($createAccount && !empty($eml) && filter_var($eml, FILTER_VALIDATE_EMAIL)) {
                $userId = create_patient_user_account($eml, $fn, $ln, $patientId);
                
                if ($userId) {
                    $tempPassword = $_SESSION['temp_passwords'][$eml] ?? 'N/A';
                    $successMessage .= "<br><br><strong>🔐 Patient Account Created!</strong><br>";
                    $successMessage .= "📧 Email: <strong>$eml</strong><br>";
                    $successMessage .= "🔑 Temporary Password: <strong>$tempPassword</strong><br>";
                    $successMessage .= "<small>⚠️ Please inform the patient of their login credentials. They can change their password after first login.</small>";
                } else {
                    $successMessage .= "<br><br><strong>⚠️ Note:</strong> Patient account creation failed. You can try creating it manually later.";
                }
            } else if (!empty($eml) && !$createAccount) {
                $successMessage .= "<br><br><strong>💡 Tip:</strong> You can create a patient portal account for this patient by editing their record and checking 'Create Account'.";
            }
            
            flash($successMessage, 'success');
            redirect('list.php');
        } else {
            flash('DB error: '.$conn->error);
        }
    }
}

/* ───────── HTML ───────── */
include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main class="patient-add-main">
  <div class="page-intro">
    <div class="intro-icon">🧾</div>
    <div>
      <h1 class="page-title">Add New Patient</h1>
      <p class="page-sub">Create a new patient record and capture core demographic info.</p>
    </div>
  </div>

  <?php $flash = get_flash(); if ($flash): ?>
    <div class="flash-banner flash-<?=$_SESSION['flash_type'] ?? 'info';?>"><?php echo $flash; ?></div>
  <?php endif; ?>

  <div class="patient-card-wrapper">
    <form method="post" id="addPatientForm" novalidate>
      <fieldset class="form-grid">
        <legend class="visually-hidden">Patient Details</legend>

        <div class="form-group required">
          <label for="first_name">First Name</label>
          <input type="text" name="first_name" id="first_name" required autocomplete="given-name" placeholder="e.g. John">
          <small class="field-hint">Patient's legal first name.</small>
        </div>

        <div class="form-group required">
          <label for="last_name">Last Name</label>
          <input type="text" name="last_name" id="last_name" required autocomplete="family-name" placeholder="e.g. Doe">
          <small class="field-hint">Patient's legal surname.</small>
        </div>

        <div class="form-group required">
          <label for="dob">Date of Birth</label>
          <input type="date" name="dob" id="dob" required>
          <small class="field-hint">Format: YYYY-MM-DD</small>
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" name="email" id="email" autocomplete="email" placeholder="name@example.com">
          <small class="field-hint">Required for patient portal access</small>
        </div>

        <div class="form-group">
          <label for="phone">Phone</label>
          <input type="tel" name="phone" id="phone" autocomplete="tel" placeholder="(555) 000-1234" pattern="[0-9+()\-\s]{7,}">
        </div>

        <div class="form-group full">
          <label for="address">Address</label>
          <textarea name="address" id="address" rows="3" placeholder="Street, City, State / Region"></textarea>
        </div>

        <div class="form-group full">
          <div class="checkbox-group">
            <input type="checkbox" name="create_account" id="create_account" value="1" checked>
            <label for="create_account" class="checkbox-label">
              🔐 Create Patient Portal Account
              <small class="checkbox-hint">Automatically create login credentials for this patient. Requires email address.</small>
            </label>
          </div>
        </div>
      </fieldset>

      <div class="form-actions">
        <button type="submit" class="btn-primary" id="saveBtn">💾 Save Patient</button>
        <a href="list.php" class="btn-secondary">✖ Cancel</a>
      </div>
    </form>

    <aside class="side-panel">
      <h3>🛈 Data Quality Tips</h3>
      <ul>
        <li>Verify spelling of names from ID documents.</li>
        <li>Use a primary contact number the patient answers.</li>
        <li>Capture full address for billing & insurance.</li>
        <li>Date of birth impacts age-based treatment plans.</li>
      </ul>
      <div class="divider"></div>
      <h3>🔒 Privacy</h3>
      <p class="small-text">Only authorized staff can view or edit this information. All access is logged.</p>
    </aside>
  </div>
</main>

<style>
  .patient-add-main { padding: 0 2rem 3rem; }
  .page-intro { display:flex; align-items:center; gap:1.25rem; margin:0 0 1.5rem; }
  .intro-icon { font-size:2.75rem; line-height:1; filter:drop-shadow(0 4px 8px rgba(0,0,0,0.15)); }
  .page-title { font-size:2.1rem; margin:0 0 .25rem; letter-spacing:.5px; }
  .page-sub { margin:0; color:#64748b; font-size:.95rem; }

  .flash-banner { padding:1rem 1.25rem; border-radius:12px; margin:0 0 1.5rem; font-weight:500; }
  .flash-success { background:linear-gradient(135deg,#48bb78,#38a169); color:#fff; }
  .flash-error { background:linear-gradient(135deg,#f56565,#e53e3e); color:#fff; }
  .flash-info { background:linear-gradient(135deg,#4299e1,#3182ce); color:#fff; }

  .patient-card-wrapper { display:grid; grid-template-columns: minmax(0,1fr) 300px; gap:2rem; align-items:start; }
  @media (max-width: 1000px) { .patient-card-wrapper { grid-template-columns:1fr; } .side-panel { order:-1; } }

  form#addPatientForm { background:#ffffff; border-radius:22px; padding:2rem 2.25rem 2.25rem; box-shadow:0 10px 30px -8px rgba(0,0,0,0.12); position:relative; overflow:hidden; }
  form#addPatientForm:before { content:''; position:absolute; inset:0; background:radial-gradient(circle at 85% 15%, rgba(0,150,255,0.08), transparent 60%); pointer-events:none; }

  .form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1.25rem 1.5rem; margin:0 0 .75rem; padding:0; border:0; }
  .form-grid legend { font-size:0; }
  .form-group { display:flex; flex-direction:column; gap:.4rem; }
  .form-group.full { grid-column:1 / -1; }
  .form-group.required > label:after { content:'*'; color:#e53e3e; margin-left:4px; }

  .form-group label { font-weight:600; font-size:.9rem; letter-spacing:.5px; color:#1e293b; }
  .form-group input, .form-group textarea { border:2px solid #e2e8f0; background:#f8fafc; padding:.85rem .95rem; border-radius:12px; font-size:.95rem; transition:.25s; font-family:inherit; }
  .form-group input:focus, .form-group textarea:focus { outline:none; border-color:#4facfe; background:#fff; box-shadow:0 0 0 3px rgba(79,172,254,0.15); }
  .form-group input.error { border-color:#e53e3e; background:#fff5f5; }
  .form-group small.field-hint { color:#64748b; font-size:.7rem; letter-spacing:.5px; }

  .checkbox-group { display:flex; align-items:flex-start; gap:0.75rem; padding:1rem; background:#f8fafc; border:2px solid #e2e8f0; border-radius:12px; transition:.25s; }
  .checkbox-group:hover { border-color:#4facfe; background:#f0f9ff; }
  .checkbox-group input[type="checkbox"] { margin:0; transform:scale(1.25); accent-color:#4facfe; cursor:pointer; }
  .checkbox-label { cursor:pointer; font-weight:500; color:#1e293b; margin:0; display:flex; flex-direction:column; gap:0.25rem; }
  .checkbox-hint { font-size:0.75rem; color:#64748b; font-weight:400; }

  .form-actions { display:flex; gap:.85rem; margin-top:1.25rem; }
  .btn-primary { background:linear-gradient(135deg,#4facfe,#00c6ff); color:#fff; border:none; padding:.95rem 1.75rem; border-radius:14px; font-size:.95rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:.5rem; box-shadow:0 6px 16px -4px rgba(0,150,255,.4); position:relative; overflow:hidden; }
  .btn-primary:before { content:''; position:absolute; inset:0; background:linear-gradient(120deg,rgba(255,255,255,.35),rgba(255,255,255,0)); opacity:0; transition:.4s; }
  .btn-primary:hover:before { opacity:1; }
  .btn-primary:hover { transform:translateY(-2px); }
  .btn-primary:active { transform:translateY(0); }
  .btn-primary.loading { pointer-events:none; opacity:.7; }
  .btn-secondary { background:#fff; border:2px solid #e2e8f0; color:#1e293b; padding:.95rem 1.5rem; border-radius:14px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:.4rem; transition:.25s; }
  .btn-secondary:hover { border-color:#4facfe; color:#2563eb; }

  .side-panel { background:#ffffff; border-radius:22px; padding:1.75rem 1.5rem 2rem; box-shadow:0 10px 30px -8px rgba(0,0,0,0.12); position:relative; }
  .side-panel h3 { margin:0 0 .75rem; font-size:1rem; letter-spacing:.5px; display:flex; align-items:center; gap:.4rem; }
  .side-panel ul { list-style:none; padding:0; margin:0 0 1rem; display:grid; gap:.55rem; }
  .side-panel li { position:relative; padding-left:1.1rem; font-size:.8rem; color:#475569; line-height:1.25rem; }
  .side-panel li:before { content:'›'; position:absolute; left:0; top:0; color:#4facfe; font-weight:700; }
  .divider { height:1px; background:linear-gradient(to right,#e2e8f0,transparent); margin:1rem 0 1.25rem; }
  .small-text { font-size:.72rem; color:#64748b; line-height:1.1rem; }

  .visually-hidden { position:absolute !important; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0 0 0 0); border:0; }

  @media (max-width:640px){
    .patient-add-main { padding:0 1.25rem 2.5rem; }
    form#addPatientForm, .side-panel { padding:1.5rem 1.25rem 1.75rem; border-radius:18px; }
    .form-grid { gap:1rem 1.1rem; }
    .btn-primary, .btn-secondary { width:100%; justify-content:center; }
    .form-actions { flex-direction:column; }
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('addPatientForm');
  const saveBtn = document.getElementById('saveBtn');
  const requiredFields = ['first_name','last_name','dob'];
  const emailField = document.getElementById('email');
  const createAccountCheckbox = document.getElementById('create_account');

  // Handle checkbox dependency on email
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

  form.addEventListener('submit', (e)=>{
    let invalid = false;
    requiredFields.forEach(id => {
      const el = document.getElementById(id);
      if(!el.value.trim()) { el.classList.add('error'); invalid = true; }
      else { el.classList.remove('error'); }
    });

    // Check if create account is checked but no email
    if (createAccountCheckbox.checked && !emailField.value.trim()) {
      emailField.classList.add('error');
      invalid = true;
      alert('Email is required to create a patient portal account.');
    }

    if(invalid) { e.preventDefault(); return; }
    saveBtn.classList.add('loading');
    saveBtn.textContent = 'Saving...';
  });

  // Auto-clear error styles on input
  document.querySelectorAll('input, textarea').forEach(field => {
    field.addEventListener('input', () => field.classList.remove('error'));
  });
});
</script>
<?php include BASE_PATH . '/templates/footer.php'; ?>