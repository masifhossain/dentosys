<?php
/*****************************************************************
 * pages/records/add_prescription.php
 * ---------------------------------------------------------------
 * Create new prescription for a patient
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Handle form submission ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $dentist_id = intval($_POST['dentist_id']);
    $appointment_id = !empty($_POST['appointment_id']) ? intval($_POST['appointment_id']) : NULL;
    $medication_name = $conn->real_escape_string($_POST['medication_name']);
    $dosage = $conn->real_escape_string($_POST['dosage']);
    $frequency = $conn->real_escape_string($_POST['frequency']);
    $duration = $conn->real_escape_string($_POST['duration']);
    $instructions = $conn->real_escape_string($_POST['instructions']);
    $prescribed_date = $conn->real_escape_string($_POST['prescribed_date']);

    $appointment_sql = $appointment_id ? "$appointment_id" : "NULL";
    
    $sql = "INSERT INTO Prescription 
            (patient_id, dentist_id, appointment_id, medication_name, dosage, 
             frequency, duration, instructions, prescribed_date, status)
            VALUES 
            ($patient_id, $dentist_id, $appointment_sql, '$medication_name', '$dosage', 
             '$frequency', '$duration', '$instructions', '$prescribed_date', 'Active')";

    if ($conn->query($sql)) {
        flash('Prescription added successfully.');
        redirect('prescriptions.php');
    } else {
        flash('Error: ' . $conn->error, 'error');
    }
}

/* ───────── Get data for dropdowns ───────── */
$patients = get_patients($conn);
$dentists = get_dentists($conn);

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
<main>
  <h2>Add New Prescription</h2>
  <?= get_flash(); ?>

  <div class="card" style="max-width: 800px;">
    <form method="post" id="prescriptionForm">
      <div class="grid grid-cols-2 gap-4">
        <!-- Patient Selection -->
        <div class="form-group">
          <label class="form-label">Patient *</label>
          <select name="patient_id" id="patientSelect" required class="form-select">
            <option value="">Select Patient</option>
            <?php while ($p = $patients->fetch_assoc()): ?>
              <option value="<?= $p['patient_id']; ?>"
                <?= (!empty($_GET['patient_id']) && $_GET['patient_id'] == $p['patient_id']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($p['name']); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <!-- Dentist Selection -->
        <div class="form-group">
          <label class="form-label">Prescribing Dentist *</label>
          <select name="dentist_id" required class="form-select">
            <option value="">Select Dentist</option>
            <?php while ($d = $dentists->fetch_assoc()): ?>
              <option value="<?= $d['dentist_id']; ?>">
                <?= htmlspecialchars($d['name']); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <!-- Related Appointment (Optional) -->
        <div class="form-group">
          <label class="form-label">Related Appointment (Optional)</label>
          <select name="appointment_id" id="appointmentSelect" class="form-select">
            <option value="">None</option>
            <?php foreach ($appointments as $appt): ?>
              <option value="<?= $appt['appointment_id']; ?>">
                <?= $appt['appt_time']; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Prescribed Date -->
        <div class="form-group">
          <label class="form-label">Prescribed Date *</label>
          <input type="date" name="prescribed_date" value="<?= date('Y-m-d'); ?>" required class="form-input">
        </div>

        <!-- Medication Name -->
        <div class="form-group">
          <label class="form-label">Medication Name *</label>
          <input type="text" name="medication_name" required class="form-input" 
                 placeholder="e.g., Amoxicillin">
        </div>

        <!-- Dosage -->
        <div class="form-group">
          <label class="form-label">Dosage</label>
          <input type="text" name="dosage" class="form-input" 
                 placeholder="e.g., 500mg">
        </div>

        <!-- Frequency -->
        <div class="form-group">
          <label class="form-label">Frequency</label>
          <select name="frequency" class="form-select">
            <option value="">Select frequency</option>
            <option value="Once daily">Once daily</option>
            <option value="Twice daily">Twice daily</option>
            <option value="Three times daily">Three times daily</option>
            <option value="Four times daily">Four times daily</option>
            <option value="As needed">As needed</option>
            <option value="Before meals">Before meals</option>
            <option value="After meals">After meals</option>
          </select>
        </div>

        <!-- Duration -->
        <div class="form-group">
          <label class="form-label">Duration</label>
          <input type="text" name="duration" class="form-input" 
                 placeholder="e.g., 7 days, 2 weeks">
        </div>
      </div>

      <!-- Special Instructions -->
      <div class="form-group">
        <label class="form-label">Special Instructions</label>
        <textarea name="instructions" rows="4" class="form-textarea" 
                  placeholder="Additional instructions for the patient..."></textarea>
      </div>

      <div style="margin-top: 30px;">
        <button type="submit" class="btn btn-primary">Add Prescription</button>
        <a href="prescriptions.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</main>

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
