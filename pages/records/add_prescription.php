<?php
/*****************************************************************
 * pages/records/add_prescription.php
 * ---------------------------------------------------------------
 * Create new prescription for a patient
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

// Only Admins and Dentists can add prescriptions
if (!is_admin() && !is_dentist()) {
  flash('Access denied. Receptionists have view-only access to prescriptions.', 'error');
  redirect('prescriptions.php');
}

/* AJAX: return appointments for patient (JSON) */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'appointments' && isset($_GET['patient_id'])) {
  header('Content-Type: application/json');
  $pid = intval($_GET['patient_id']);
  $data = [];
  if ($pid > 0) {
    $res = $conn->query("SELECT appointment_id, DATE_FORMAT(appointment_dt, '%Y-%m-%d %H:%i') as appt_time FROM Appointment WHERE patient_id = $pid AND status = 'Approved' ORDER BY appointment_dt DESC LIMIT 50");
    while ($row = $res->fetch_assoc()) { $data[] = $row; }
  }
  echo json_encode($data);
  exit;
}

/* ───────── Handle form submission ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = intval($_POST['patient_id'] ?? 0);
  $dentist_id = intval($_POST['dentist_id'] ?? 0);
  $appointment_id = isset($_POST['appointment_id']) && $_POST['appointment_id'] !== '' ? intval($_POST['appointment_id']) : null;
  $medication_name = trim($_POST['medication_name'] ?? '');
  $dosage = trim($_POST['dosage'] ?? '');
  $frequency = trim($_POST['frequency'] ?? '');
  $duration = trim($_POST['duration'] ?? '');
  $instructions = trim($_POST['instructions'] ?? '');
  $prescribed_date = $_POST['prescribed_date'] ?? date('Y-m-d');
  $errors = [];
  if ($patient_id <= 0) $errors[] = 'Patient required';
  if ($dentist_id <= 0) $errors[] = 'Dentist required';
  if ($medication_name === '') $errors[] = 'Medication name required';
  if ($prescribed_date === '') $errors[] = 'Date required';
  if (empty($errors)) {
    $stmt = $conn->prepare("INSERT INTO Prescriptions (patient_id, dentist_id, appointment_id, medication_name, dosage, frequency, duration, instructions, prescribed_date, status) VALUES (?,?,?,?,?,?,?,?,?,'Active')");
    // Need 9 placeholders: patient,dentist,appointment,medication,dosage,frequency,duration,instructions,prescribed_date
    $stmt->bind_param('iiissssss', $patient_id, $dentist_id, $appointment_id, $medication_name, $dosage, $frequency, $duration, $instructions, $prescribed_date);
    if ($stmt->execute()) {
      flash('Prescription added successfully.');
      redirect('prescriptions.php');
    } else {
      flash('Error creating prescription: ' . $conn->error);
    }
  } else {
    foreach ($errors as $e) flash($e);
  }
}

/* ───────── Get data for dropdowns - apply role-based filtering ───────── */
if (is_dentist()) {
    // For dentists, only show patients they have appointments with
    $patient_ids = get_dentist_patient_ids();
    if (empty($patient_ids)) {
        $patients = [];  // No patients if no appointments
    } else {
        $patient_ids_str = implode(',', $patient_ids);
        $patients = $conn->query(
            "SELECT patient_id, CONCAT(first_name, ' ', last_name) AS name
             FROM Patient 
             WHERE patient_id IN ($patient_ids_str)
             ORDER BY last_name, first_name"
        );
    }
    
    // For dentists, only show themselves in dentist dropdown
    $current_dentist_id = get_current_dentist_id();
    if ($current_dentist_id) {
        $dentists = $conn->query(
            "SELECT d.dentist_id,
                    CONCAT(u.email,' (',
                           IFNULL(d.specialty,'General'),
                           ')') AS name
             FROM Dentist d
             JOIN UserTbl u ON u.user_id = d.user_id
             WHERE d.dentist_id = $current_dentist_id
             ORDER BY name"
        );
    } else {
        $dentists = null;
    }
} else {
    $patients = get_patients($conn);
    $dentists = get_dentists($conn);
}

// Get appointments for selected patient (for AJAX)
$appointments = [];
if (!empty($_GET['patient_id'])) {
    $pid = intval($_GET['patient_id']);
    $appt_query = $conn->query(
        "SELECT appointment_id, DATE_FORMAT(appointment_dt, '%Y-%m-%d %H:%i') as appt_time 
         FROM Appointment 
         WHERE patient_id = $pid AND status = 'Approved' 
         ORDER BY appointment_dt DESC LIMIT 20"
    );
    while ($row = $appt_query->fetch_assoc()) {
        $appointments[] = $row;
    }
}

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main class="prescription-main">
  <div class="page-intro">
    <div class="icon">💊</div>
    <div>
      <h1>Create Prescription</h1>
      <p class="sub">Record a new medication order for a patient.</p>
    </div>
  </div>
  <?= get_flash(); ?>
  <div class="layout">
    <form method="post" id="prescriptionForm" class="rx-form" novalidate>
      <div class="grid-fields">
        <div class="form-group required">
          <label for="patientSelect">Patient</label>
          <select name="patient_id" id="patientSelect" required>
            <option value="">Select patient</option>
            <?php $patients->data_seek(0); while ($p = $patients->fetch_assoc()): ?>
              <option value="<?= $p['patient_id']; ?>" <?= (!empty($_GET['patient_id']) && $_GET['patient_id']==$p['patient_id'])?'selected':''; ?>><?= htmlspecialchars($p['name']); ?></option>
            <?php endwhile; ?>
          </select>
          <small class="hint">Required</small>
        </div>
        <div class="form-group required">
          <label for="dentistSelect">Prescribing Dentist</label>
          <select name="dentist_id" id="dentistSelect" required>
            <option value="">Select dentist</option>
            <?php $dentists->data_seek(0); while ($d = $dentists->fetch_assoc()): ?>
              <option value="<?= $d['dentist_id']; ?>"><?= htmlspecialchars($d['name']); ?></option>
            <?php endwhile; ?>
          </select>
          <small class="hint">Required</small>
        </div>
        <div class="form-group">
          <label for="appointmentSelect">Related Appointment (optional)</label>
          <select name="appointment_id" id="appointmentSelect">
            <option value="">None</option>
            <?php foreach ($appointments as $appt): ?><option value="<?= $appt['appointment_id']; ?>"><?= $appt['appt_time']; ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group required">
          <label for="prescribed_date">Prescribed Date</label>
          <input type="date" name="prescribed_date" id="prescribed_date" value="<?= htmlspecialchars($_POST['prescribed_date'] ?? date('Y-m-d')); ?>" required>
        </div>
        <div class="form-group required full">
          <label for="medication_name">Medication Name</label>
          <input type="text" name="medication_name" id="medication_name" placeholder="e.g., Amoxicillin" value="<?= htmlspecialchars($_POST['medication_name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
          <label for="dosage">Dosage</label>
          <input type="text" name="dosage" id="dosage" placeholder="e.g., 500mg" value="<?= htmlspecialchars($_POST['dosage'] ?? ''); ?>">
          <div class="chips" data-target="dosage">
            <button type="button">250mg</button><button type="button">500mg</button><button type="button">1g</button>
          </div>
        </div>
        <div class="form-group">
          <label for="frequency">Frequency</label>
          <select name="frequency" id="frequency">
            <option value="">Select frequency</option>
            <option>Once daily</option><option>Twice daily</option><option>Three times daily</option><option>Four times daily</option><option>As needed</option><option>Before meals</option><option>After meals</option>
          </select>
          <div class="chips" data-target="frequency" data-type="select">
            <button type="button">Twice daily</button><button type="button">As needed</button><button type="button">After meals</button>
          </div>
        </div>
        <div class="form-group">
          <label for="duration">Duration</label>
          <input type="text" name="duration" id="duration" placeholder="e.g., 7 days" value="<?= htmlspecialchars($_POST['duration'] ?? ''); ?>">
          <div class="chips" data-target="duration">
            <button type="button">5 days</button><button type="button">7 days</button><button type="button">14 days</button>
          </div>
        </div>
        <div class="form-group full">
          <label for="instructions">Special Instructions</label>
          <textarea name="instructions" id="instructions" rows="4" placeholder="Additional instructions for the patient..."><?= htmlspecialchars($_POST['instructions'] ?? ''); ?></textarea>
        </div>
      </div>
      <div class="actions-row">
        <button type="submit" class="btn-primary" id="submitBtn">💊 Add Prescription</button>
        <a href="prescriptions.php" class="btn-secondary">Cancel</a>
      </div>
    </form>
    <aside class="side-panel">
      <div class="panel">
        <h3>🛈 Guidance</h3>
        <ul>
          <li>Confirm allergies before prescribing.</li>
          <li>Use generic names where possible.</li>
          <li>Provide clear dosing intervals.</li>
          <li>Record duration for antibiotics.</li>
        </ul>
      </div>
      <div class="panel tight">
        <h3>🔒 Compliance</h3>
        <p class="small">All prescriptions are logged with user, timestamp and IP for audit purposes.</p>
      </div>
    </aside>
  </div>
</main>

<style>
  .prescription-main { padding:0 2rem 3rem; }
  .page-intro { display:flex; gap:1.1rem; align-items:center; margin:0 0 1.75rem; }
  .page-intro .icon { font-size:2.75rem; filter:drop-shadow(0 4px 8px rgba(0,0,0,.15)); }
  .page-intro h1 { margin:0 0 .25rem; font-size:2.1rem; letter-spacing:.5px; }
  .page-intro .sub { margin:0; color:#64748b; font-size:.95rem; }
  .layout { display:grid; grid-template-columns:minmax(0,1fr) 310px; gap:2rem; align-items:start; }
  @media (max-width:1100px){ .layout { grid-template-columns:1fr; } .side-panel { order:-1; } }
  .rx-form { background:#fff; border-radius:22px; padding:2rem 2.25rem 2.3rem; box-shadow:0 10px 30px -8px rgba(0,0,0,.12); position:relative; overflow:hidden; }
  .rx-form:before { content:""; position:absolute; inset:0; background:radial-gradient(circle at 85% 15%, rgba(0,150,255,.09), transparent 60%); pointer-events:none; }
  .grid-fields { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1.2rem 1.5rem; }
  .form-group { display:flex; flex-direction:column; gap:.45rem; }
  .form-group.full { grid-column:1/-1; }
  .form-group.required label:after { content:'*'; color:#e53e3e; margin-left:4px; }
  .form-group label { font-weight:600; font-size:.9rem; color:#1e293b; letter-spacing:.5px; }
  .form-group select, .form-group input, .form-group textarea { border:2px solid #e2e8f0; background:#f8fafc; padding:.85rem .95rem; border-radius:12px; font-size:.95rem; transition:.25s; font-family:inherit; }
  .form-group select:focus, .form-group input:focus, .form-group textarea:focus { outline:none; border-color:#4facfe; background:#fff; box-shadow:0 0 0 3px rgba(79,172,254,.15); }
  .form-group textarea { resize:vertical; min-height:120px; }
  .hint { color:#64748b; font-size:.65rem; letter-spacing:.5px; }
  .chips { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.15rem; }
  .chips button { background:#f1f5f9; border:1px solid #cbd5e1; padding:.3rem .55rem; font-size:.65rem; border-radius:8px; cursor:pointer; font-weight:600; color:#475569; transition:.2s; }
  .chips button:hover { background:#e2e8f0; }
  .actions-row { display:flex; gap:.85rem; margin-top:1.5rem; }
  .btn-primary { background:linear-gradient(135deg,#4facfe,#00c6ff); color:#fff; border:none; padding:.9rem 1.6rem; border-radius:14px; font-size:.9rem; font-weight:600; cursor:pointer; box-shadow:0 6px 16px -4px rgba(0,150,255,.4); }
  .btn-primary:hover { transform:translateY(-2px); }
  .btn-secondary { background:#fff; border:2px solid #e2e8f0; color:#1e293b; padding:.9rem 1.4rem; border-radius:14px; font-weight:600; text-decoration:none; }
  .btn-secondary:hover { border-color:#4facfe; color:#2563eb; }
  .side-panel { display:grid; gap:1.5rem; }
  .panel { background:#fff; border-radius:22px; padding:1.5rem 1.5rem 1.8rem; box-shadow:0 10px 30px -8px rgba(0,0,0,.12); }
  .panel.tight { padding:1.25rem 1.25rem 1.4rem; }
  .panel h3 { margin:0 0 .7rem; font-size:1rem; letter-spacing:.5px; }
  .panel ul { list-style:none; padding:0; margin:0; display:grid; gap:.55rem; }
  .panel li { position:relative; padding-left:1.05rem; font-size:.75rem; color:#475569; line-height:1.1rem; }
  .panel li:before { content:'›'; position:absolute; left:0; top:0; color:#4facfe; font-weight:700; }
  .small { font-size:.65rem; color:#64748b; line-height:1.05rem; }
  @media (max-width:640px){
    .prescription-main { padding:0 1.25rem 2.5rem; }
    .rx-form, .panel { padding:1.5rem 1.25rem 1.8rem; border-radius:18px; }
    .actions-row { flex-direction:column; }
    .btn-primary, .btn-secondary { width:100%; text-align:center; }
  }
</style>

<script>
// Appointment dynamic load & chips interaction
document.addEventListener('DOMContentLoaded', () => {
  const patientSelect = document.getElementById('patientSelect');
  const appointmentSelect = document.getElementById('appointmentSelect');
  const form = document.getElementById('prescriptionForm');
  const requiredIds = ['patientSelect','dentistSelect','medication_name'];
  const submitBtn = document.getElementById('submitBtn');

  patientSelect.addEventListener('change', () => {
    const pid = patientSelect.value;
    appointmentSelect.innerHTML = '<option value="">Loading...</option>';
    if (!pid) { appointmentSelect.innerHTML = '<option value="">None</option>'; return; }
    fetch(`add_prescription.php?ajax=appointments&patient_id=${pid}`)
      .then(r=>r.json())
      .then(list => {
        appointmentSelect.innerHTML = '<option value="">None</option>' + list.map(a=>`<option value="${a.appointment_id}">${a.appt_time}</option>`).join('');
      })
      .catch(()=>{ appointmentSelect.innerHTML = '<option value="">None</option>'; });
  });

  form.addEventListener('submit', e => {
    let invalid = false;
    requiredIds.forEach(id => { const el=document.getElementById(id); if(!el.value.trim()){ el.classList.add('error'); invalid=true;} else el.classList.remove('error'); });
    if (invalid) { e.preventDefault(); alert('Please complete required fields.'); return; }
    submitBtn.classList.add('loading'); submitBtn.textContent='Saving...';
  });

  document.querySelectorAll('.chips').forEach(group => {
    group.addEventListener('click', e => {
      if(e.target.tagName!== 'BUTTON') return; e.preventDefault();
      const target = group.dataset.target; const type = group.dataset.type;
      const input = document.getElementById(target);
      if(!input) return; const val = e.target.textContent.trim();
      if (type==='select' && input.tagName==='SELECT') {
        Array.from(input.options).forEach(o=>{ if(o.text===val) input.value=o.value; });
      } else {
        input.value = val;
      }
    });
  });
});
</script>

<script>
// Load appointments when patient is selected
document.getElementById('patientSelect').addEventListener('change', function() {
    const patientId = this.value;
    const appointmentSelect = document.getElementById('appointmentSelect');
    
    // Clear existing options
    appointmentSelect.innerHTML = '<option value="">Loading...</option>';
    
    if (patientId) {
        // Reload page with patient_id parameter to get appointments
        window.location.href = `add_prescription.php?patient_id=${patientId}`;
    } else {
        appointmentSelect.innerHTML = '<option value="">None</option>';
    }
});

// Form validation
document.getElementById('prescriptionForm').addEventListener('submit', function(e) {
    const medicationName = document.querySelector('input[name="medication_name"]').value.trim();
    const patientId = document.querySelector('select[name="patient_id"]').value;
    const dentistId = document.querySelector('select[name="dentist_id"]').value;
    
    if (!medicationName || !patientId || !dentistId) {
        e.preventDefault();
        alert('Please fill in all required fields.');
    }
});
</script>

<?php include BASE_PATH . '/templates/footer.php'; ?>
