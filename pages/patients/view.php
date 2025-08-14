<?php
/*****************************************************************
 * pages/patients/view.php
 * ---------------------------------------------------------------
 * Detailed view of a patient + quick statistics.
 *   URL: view.php?id=123
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Validate ID and fetch record ───────── */
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    flash('Invalid patient ID.');
    redirect('list.php');
}

$stmt = $conn->prepare("SELECT * FROM Patient WHERE patient_id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    flash('Patient not found.');
    redirect('list.php');
}

/* ───────── Simple stats ───────── */
$today = date('Y-m-d 00:00:00');

$appts = $conn->query(
  "SELECT COUNT(*) AS c FROM Appointment
   WHERE patient_id = $id
     AND appointment_dt >= '$today'
     AND status IN ('Scheduled','Pending','Approved')"
)->fetch_assoc()['c'] ?? 0;

$unpaid = $conn->query(
  "SELECT COUNT(*) AS c FROM Invoice
   WHERE patient_id = $id AND status = 'Unpaid'"
)->fetch_assoc()['c'] ?? 0;

$records = $conn->query(
  "SELECT COUNT(*) AS c FROM Treatment t
   JOIN Appointment a ON a.appointment_id = t.appointment_id
   WHERE a.patient_id = $id"
)->fetch_assoc()['c'] ?? 0;

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Patient Profile</h2>
  <?= get_flash(); ?>

  <!-- Basic info -->
  <table style="max-width:600px;">
    <tr><th>Name</th>
        <td><?= htmlspecialchars($patient['first_name'].' '.$patient['last_name']); ?></td></tr>
    <tr><th>DOB</th>
        <td><?= $patient['dob']; ?></td></tr>
    <tr><th>Email</th>
        <td><?= htmlspecialchars($patient['email']); ?></td></tr>
    <tr><th>Phone</th>
        <td><?= htmlspecialchars($patient['phone']); ?></td></tr>
    <tr><th>Address</th>
        <td><?= nl2br(htmlspecialchars($patient['address'])); ?></td></tr>
  </table>

  <p style="margin-top:12px;">
    <a class="btn" href="edit.php?id=<?= $id; ?>">Edit Patient</a>
    <a class="btn" href="list.php">Back to list</a>
  </p>

  <!-- Quick stats -->
  <h3>Quick Stats</h3>
  <ul>
    <li><strong><?= $appts; ?></strong> upcoming appointment(s)</li>
    <li><strong><?= $unpaid; ?></strong> unpaid invoice(s)</li>
    <li><strong><?= $records; ?></strong> treatment record(s)</li>
  </ul>

  <!-- Links to related modules -->
  <p>
    <a href="/pages/appointments/calendar.php?patient=<?= $id; ?>">View appointments</a> |
    <a href="/pages/billing/invoices.php?patient=<?= $id; ?>">View invoices</a> |
    <a href="/pages/records/list.php?patient=<?= $id; ?>">View clinical records</a>
  </p>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>