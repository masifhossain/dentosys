<?php
/*****************************************************************
 * pages/appointments/calendar.php
 * ---------------------------------------------------------------
 * Month view of appointments.
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';  // 2 levels up
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Determine month/year to show ───────── */
$month = isset($_GET['m']) ? max(1, min(12, intval($_GET['m']))) : date('n');
$year  = isset($_GET['y']) ? intval($_GET['y']) : date('Y');

$firstTs      = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth  = (int)date('t', $firstTs);
$startDow     = (int)date('w', $firstTs); // 0 = Sun … 6 = Sat
$monthLabel   = date('F Y', $firstTs);

/* ───────── Fetch appointments for this month ───────── */
$start = date('Y-m-01 00:00:00', $firstTs);
$end   = date('Y-m-t 23:59:59', $firstTs);

$sql = "SELECT appointment_id,
               DATE(appointment_dt) AS adate,
               DATE_FORMAT(appointment_dt,'%H:%i') AS atime,
               status
        FROM Appointment
        WHERE appointment_dt BETWEEN '$start' AND '$end'";
$appts = [];
$res   = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $appts[$row['adate']][] = $row;           // bucket by date
}

/* ───────── Prev / Next month links ───────── */
$prevMonth = $month == 1 ? 12 : $month - 1;
$prevYear  = $month == 1 ? $year - 1 : $year;
$nextMonth = $month == 12 ? 1  : $month + 1;
$nextYear  = $month == 12 ? $year + 1 : $year;
?>
<?php include BASE_PATH . '/templates/header.php'; ?>
<?php include BASE_PATH . '/templates/sidebar.php'; ?>

<main>
  <h2>
    <a href="?m=<?= $prevMonth; ?>&y=<?= $prevYear; ?>">&laquo;</a>
    <?= $monthLabel; ?>
    <a href="?m=<?= $nextMonth; ?>&y=<?= $nextYear; ?>">&raquo;</a>
  </h2>

  <a class="btn" href="book.php">+ Book Appointment</a>
  &nbsp;
  <a class="btn" href="pending.php">Pending Approvals</a>

  <table class="calendar">
    <tr>
      <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d) echo "<th>$d</th>"; ?>
    </tr>
    <tr>
      <?php
      /* blank cells before 1st day */
      for ($i = 0; $i < $startDow; $i++) echo '<td class="empty"></td>';

      /* days of month */
      for ($d = 1; $d <= $daysInMonth; $d++) {

          $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
          echo '<td>';
          echo "<div class='day'>$d</div>";

          if (isset($appts[$dateStr])) {
              foreach ($appts[$dateStr] as $a) {
                  /* colour by status */
                  $cls = ($a['status'] === 'Approved') ? 'ok' : (($a['status'] === 'Cancelled') ? 'cancel' : 'pending');
                  echo "<div class='appt $cls'>{$a['atime']}</div>";
              }
          }

          echo '</td>';

          /* wrap row at Saturday */
          if (($startDow + $d) % 7 === 0 && $d !== $daysInMonth) {
              echo "</tr><tr>";
          }
      }

      /* blank cells after last day */
      $cells = ($startDow + $daysInMonth) % 7;
      if ($cells) for ($i = 0; $i < 7 - $cells; $i++) echo '<td class="empty"></td>';
      ?>
    </tr>
  </table>
</main>

<?php include BASE_PATH . '/templates/footer.php'; ?>