<?php
/*****************************************************************
 * pages/appointments/book.php
 * ---------------------------------------------------------------
 * Create a new appointment.
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // 2 levels up
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Dropdown data ───────── */
$patients = get_patients($conn);
$dentists = get_dentists($conn);

/* ───────── Handle form POST ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $dentist_id = intval($_POST['dentist_id']);
    $dt         = $conn->real_escape_string($_POST['appt_dt']);  // yyyy-mm-ddTHH:MM
    $status     = 'Pending';
    $notes      = $conn->real_escape_string($_POST['notes']);

    $sql = "INSERT INTO Appointment
            (patient_id, dentist_id, appointment_dt, status, notes)
            VALUES ($patient_id, $dentist_id, '$dt', '$status', '$notes')";

    if ($conn->query($sql)) {
        flash('Appointment booked successfully.');
        redirect('calendar.php');
    } else {
        flash('Error: ' . $conn->error);
    }
}

/* ───────── HTML Output ───────── */
include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Book Appointment</h2>

  <?= get_flash(); ?>

  <form method="post" style="max-width:420px">
    <!-- Patient dropdown -->
    <label>Patient:<br>
      <select name="patient_id" required>
        <option value="">Select Patient</option>
        <?php while ($p = $patients->fetch_assoc()): ?>
          <option value="<?= $p['patient_id']; ?>">
            <?= htmlspecialchars($p['name']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    </label><br><br>

    <!-- Dentist dropdown -->
    <label>Dentist:<br>
      <select name="dentist_id" required>
        <option value="">Select Dentist</option>
        <?php while ($d = $dentists->fetch_assoc()): ?>
          <option value="<?= $d['dentist_id']; ?>">
            <?= htmlspecialchars($d['name']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    </label><br><br>

    <!-- Date/time -->
    <label>Date &amp; Time:<br>
      <input type="datetime-local" name="appt_dt" required>
    </label><br><br>

    <!-- Notes -->
    <label>Notes:<br>
      <textarea name="notes" rows="4" style="width:100%;"></textarea>
    </label><br><br>

    <button type="submit">Create Appointment</button>
    <a href="calendar.php">Cancel</a>
  </form>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>