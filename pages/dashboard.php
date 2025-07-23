<?php
/*****************************************************************
 * pages/dashboard.php
 * ---------------------------------------------------------------
 * Clinic “at-a-glance” dashboard.
 *  • Key metrics (today’s appts, patients, outstanding invoices,
 *    unread feedback)
 *  • List next 5 appointments for today
 *****************************************************************/
require_once dirname(__DIR__) . '/includes/db.php';   // up 1 level
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* --------------------------------------------------------------
 * 1. KPI queries
 * ------------------------------------------------------------ */
$today      = date('Y-m-d');
$apptsToday = $conn->query(
    "SELECT COUNT(*) AS c
     FROM Appointment
     WHERE DATE(appointment_dt) = '$today'
       AND status IN ('Scheduled','Pending','Approved')"
)->fetch_assoc()['c'] ?? 0;

$totalPatients = $conn->query(
    "SELECT COUNT(*) AS c FROM Patient"
)->fetch_assoc()['c'] ?? 0;

$outstanding = $conn->query(
    "SELECT COUNT(*)  AS cnt,
            COALESCE(SUM(total_amount),0) AS amt
     FROM Invoice WHERE status = 'Unpaid'"
)->fetch_assoc();

$feedbackNew = $conn->query(
    "SELECT COUNT(*) AS c FROM Feedback WHERE status = 'New'"
)->fetch_assoc()['c'] ?? 0;

/* --------------------------------------------------------------
 * 2. Next 5 appointments (today)
 * ------------------------------------------------------------ */
$nextAppts = $conn->query(
    "SELECT DATE_FORMAT(a.appointment_dt,'%H:%i') AS atime,
            CONCAT(p.first_name,' ',p.last_name)       AS patient,
            a.status
     FROM Appointment a
     JOIN Patient p ON p.patient_id = a.patient_id
     WHERE DATE(a.appointment_dt) = '$today'
     ORDER BY a.appointment_dt
     LIMIT 5"
);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Dashboard – <?= date('l, d M Y'); ?></h2>

  <!-- KPI cards -->
  <div style="display:flex;gap:18px;flex-wrap:wrap;margin-bottom:20px;">
    <div class="card"><h3>Today’s Appointments</h3><p><?= $apptsToday; ?></p></div>
    <div class="card"><h3>Total Patients</h3><p><?= $totalPatients; ?></p></div>
    <div class="card"><h3>Outstanding&nbsp;Invoices</h3>
        <p><?= $outstanding['cnt']; ?> | $<?= number_format($outstanding['amt'],2); ?></p></div>
    <div class="card"><h3>New Feedback</h3><p><?= $feedbackNew; ?></p></div>
  </div>

  <!-- Upcoming appointments -->
  <h3>Upcoming Appointments (today)</h3>
  <table>
    <thead>
      <tr><th>Time</th><th>Patient</th><th>Status</th></tr>
    </thead>
    <tbody>
      <?php if ($nextAppts->num_rows === 0): ?>
        <tr><td colspan="3">No appointments scheduled for today.</td></tr>
      <?php else: while ($a = $nextAppts->fetch_assoc()): ?>
        <tr>
          <td><?= $a['atime']; ?></td>
          <td><?= htmlspecialchars($a['patient']); ?></td>
          <td><?= $a['status']; ?></td>
        </tr>
      <?php endwhile; endif; ?>
    </tbody>
  </table>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>