<?php
/*****************************************************************
 * pages/records/add_note.php
 * ---------------------------------------------------------------
 * Add a new treatment note (and optional prescription) for a patient.
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2 levels
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* --------------------------------------------------------------
 * 1. Dropdown data
 * ------------------------------------------------------------ */
$patients = get_patients($conn);
$dentists = get_dentists($conn);

/* --------------------------------------------------------------
 * 2. Handle POST
 * ------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* Sanitize */
    $patient_id = intval($_POST['patient_id']);
    $dentist_id = intval($_POST['dentist_id']);
    $type       = $conn->real_escape_string(trim($_POST['type']));
    $desc       = $conn->real_escape_string(trim($_POST['description']));
    $cost       = floatval($_POST['cost']);
    $presc      = $conn->real_escape_string(trim($_POST['prescription']));

    /* Basic validation */
    if ($patient_id === 0 || $dentist_id === 0 || $type === '') {
        flash('Patient, dentist, and treatment type are required.');
        redirect('add_note.php');
    }

    /* ----------------------------------------------------------
     * 2a. Ensure there is an appointment today linking patient & dentist
     * -------------------------------------------------------- */
    $today = date('Y-m-d');
    $apptQ = $conn->query(
        "SELECT appointment_id FROM Appointment
         WHERE patient_id = $patient_id
           AND dentist_id  = $dentist_id
           AND DATE(appointment_dt) = '$today'
         LIMIT 1"
    );

    if ($row = $apptQ->fetch_assoc()) {
        $appointment_id = $row['appointment_id'];
    } else {
        /* Create a default “Complete” appointment entry */
        $conn->query(
            "INSERT INTO Appointment
             (patient_id, dentist_id, appointment_dt, status, notes)
             VALUES ($patient_id, $dentist_id, NOW(), 'Complete',
                     'Auto-created for treatment note')"
        );
        $appointment_id = $conn->insert_id;
    }

    /* ----------------------------------------------------------
     * 2b. Insert treatment record
     * -------------------------------------------------------- */
    $stmt = $conn->prepare(
      "INSERT INTO Treatment
       (appointment_id, type, description, cost, prescription)
       VALUES (?,?,?,?,?)"
    );
    $stmt->bind_param('issds',
        $appointment_id,    // i
        $type,              // s
        $desc,              // s
        $cost,              // d
        $presc              // s
    );

    if ($stmt->execute()) {
        flash('Treatment note added.');
        redirect('list.php');
    } else {
        flash('Database error: ' . $conn->error);
    }
}

/* --------------------------------------------------------------
 * 3. Page output
 * ------------------------------------------------------------ */
include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Add Treatment Note</h2>
  <?= get_flash(); ?>

  <form method="post" style="max-width:520px">
    <!-- Patient -->
    <label>Patient:<br>
      <select name="patient_id" required style="width:100%;">
        <option value="">Select patient</option>
        <?php while ($p = $patients->fetch_assoc()): ?>
          <option value="<?= $p['patient_id']; ?>">
            <?= htmlspecialchars($p['name']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    </label><br><br>

    <!-- Dentist -->
    <label>Dentist:<br>
      <select name="dentist_id" required style="width:100%;">
        <option value="">Select dentist</option>
        <?php while ($d = $dentists->fetch_assoc()): ?>
          <option value="<?= $d['dentist_id']; ?>">
            <?= htmlspecialchars($d['name']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    </label><br><br>

    <!-- Treatment info -->
    <label>Treatment Type:<br>
      <input type="text" name="type" required style="width:100%;">
    </label><br><br>

    <label>Description:<br>
      <textarea name="description" rows="4" style="width:100%;"></textarea>
    </label><br><br>

    <label>Cost ($):<br>
      <input type="number" step="0.01" min="0" name="cost" value="0" required>
    </label><br><br>

    <label>Prescription (optional):<br>
      <textarea name="prescription" rows="3" style="width:100%;"
                placeholder="Drug name, dosage, instructions…"></textarea>
    </label><br><br>

    <button type="submit">Save Note</button>
    <a href="list.php">Cancel</a>
  </form>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>