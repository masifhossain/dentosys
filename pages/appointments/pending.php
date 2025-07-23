<?php
/*****************************************************************
 * pages/appointments/pending.php
 * ---------------------------------------------------------------
 * List of appointments that are still in “Pending” status and
 * quick actions to Approve or Cancel them.
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // 2 levels up
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Optional role check ─────────
   Uncomment if only Admins or Receptionists may approve
   -------------------------------------------------------
if (!is_admin() && $_SESSION['role'] !== 3) {  // 3 = Receptionist
    flash('You do not have permission to approve appointments.');
    redirect('calendar.php');
}
*/

/* ───────── Handle approve / cancel actions ───────── */
if (isset($_GET['action'], $_GET['id'])) {
    $id     = intval($_GET['id']);
    $action = ($_GET['action'] === 'approve') ? 'Approved' : 'Cancelled';

    $stmt = $conn->prepare(
        "UPDATE Appointment SET status=? WHERE appointment_id=? LIMIT 1"
    );
    $stmt->bind_param('si', $action, $id);
    if ($stmt->execute()) {
        flash("Appointment $action.");
    } else {
        flash('DB error: ' . $conn->error);
    }
    redirect('pending.php');
}

/* ───────── Fetch pending list ───────── */
$sql = "SELECT a.appointment_id,
               DATE_FORMAT(a.appointment_dt,'%Y-%m-%d %H:%i') AS appt_time,
               a.notes, p.patient_id,
               CONCAT(p.first_name,' ',p.last_name) AS patient
        FROM Appointment a
        JOIN Patient p ON p.patient_id = a.patient_id
        WHERE a.status = 'Pending'
        ORDER BY a.appointment_dt";
$pending = $conn->query($sql);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Pending Appointment Approvals</h2>
  <?= get_flash(); ?>

  <?php if ($pending->num_rows === 0): ?>
      <p>No pending appointments.</p>
  <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Date&nbsp;/&nbsp;Time</th>
            <th>Patient</th>
            <th>Notes</th>
            <th style="width:120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($a = $pending->fetch_assoc()): ?>
          <tr>
            <td><?= $a['appt_time']; ?></td>
            <td><?= htmlspecialchars($a['patient']); ?></td>
            <td><?= nl2br(htmlspecialchars($a['notes'])); ?></td>
            <td>
              <a class="btn ok"
                 href="?action=approve&id=<?= $a['appointment_id']; ?>"
                 onclick="return confirm('Approve this appointment?');">
                 Approve
              </a>
              <a class="btn cancel"
                 href="?action=cancel&id=<?= $a['appointment_id']; ?>"
                 onclick="return confirm('Cancel this appointment?');">
                 Cancel
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
  <?php endif; ?>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>