<?php
/*****************************************************************
 * pages/records/list.php
 * ---------------------------------------------------------------
 * Lists all treatment notes (clinical records) with optional
 * patient-filter and quick link to “Add Note”.
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2 levels
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* --------------------------------------------------------------
 * 1. Patient dropdown (for filter)
 * ------------------------------------------------------------ */
$patients = get_patients($conn);     // helper in functions.php

/* --------------------------------------------------------------
 * 2. Build WHERE clause if a patient is selected
 * ------------------------------------------------------------ */
$whereSQL = '';
if (!empty($_GET['patient'])) {
    $pid = intval($_GET['patient']);
    $whereSQL = "WHERE a.patient_id = $pid";
}

/* --------------------------------------------------------------
 * 3. Retrieve treatment notes (JOIN with Patient for name)
 * ------------------------------------------------------------ */
$sql = "SELECT t.treatment_id,
               DATE(a.appointment_dt) AS day,
               CONCAT(p.first_name,' ',p.last_name) AS patient,
               t.type, t.description, t.cost
        FROM Treatment t
        JOIN Appointment a ON a.appointment_id = t.appointment_id
        JOIN Patient p     ON p.patient_id      = a.patient_id
        $whereSQL
        ORDER BY a.appointment_dt DESC";
$notes = $conn->query($sql);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Treatment Notes</h2>
  <?= get_flash(); ?>

  <!-- Filter & Add Note -->
  <form method="get" style="margin-bottom:12px;">
    <label>Patient:
      <select name="patient">
        <option value="">-- All --</option>
        <?php while ($p = $patients->fetch_assoc()): ?>
          <option value="<?= $p['patient_id']; ?>"
            <?= (!empty($_GET['patient']) && $_GET['patient'] == $p['patient_id']) ? 'selected' : ''; ?>>
            <?= htmlspecialchars($p['name']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    </label>
    <button type="submit">Filter</button>
    <a class="btn" href="list.php">Reset</a>
    <a class="btn" style="float:right;" href="add_note.php">+ Add Note</a>
  </form>

  <!-- Records table -->
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Date</th>
        <th>Patient</th>
        <th>Type</th>
        <th>Description</th>
        <th style="text-align:right;">Cost&nbsp;($)</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($notes->num_rows === 0): ?>
      <tr><td colspan="6">No treatment notes found.</td></tr>
    <?php else: $i=1; while ($n = $notes->fetch_assoc()): ?>
      <tr>
        <td><?= $i++; ?></td>
        <td><?= $n['day']; ?></td>
        <td><?= htmlspecialchars($n['patient']); ?></td>
        <td><?= htmlspecialchars($n['type']); ?></td>
        <td><?= nl2br(htmlspecialchars($n['description'])); ?></td>
        <td style="text-align:right;"><?= number_format($n['cost'], 2); ?></td>
      </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>