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

$sql = "SELECT a.appointment_id,
               DATE(a.appointment_dt) AS adate,
               DATE_FORMAT(a.appointment_dt,'%H:%i') AS atime,
               a.status,
               a.notes AS purpose,
               CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
               p.phone,
               CONCAT(d.first_name, ' ', d.last_name) AS dentist_name
        FROM Appointment a
        JOIN Patient p ON p.patient_id = a.patient_id
        LEFT JOIN Dentist den ON den.dentist_id = a.dentist_id
        LEFT JOIN UserTbl u ON u.user_id = den.user_id
        LEFT JOIN (SELECT user_id, 
                         SUBSTRING_INDEX(email, '@', 1) as first_name,
                         'Dr.' as last_name
                   FROM UserTbl) d ON d.user_id = u.user_id
        WHERE a.appointment_dt BETWEEN '$start' AND '$end'
        ORDER BY a.appointment_dt";
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
          $isToday = ($dateStr === date('Y-m-d'));
          $todayClass = $isToday ? ' today' : '';
          
          echo "<td class='calendar-day$todayClass'>";
          echo "<div class='day'>$d</div>";

          if (isset($appts[$dateStr])) {
              foreach ($appts[$dateStr] as $a) {
                  /* colour by status */
                  $cls = ($a['status'] === 'Approved') ? 'ok' : (($a['status'] === 'Cancelled') ? 'cancel' : 'pending');
                  $purpose = !empty($a['purpose']) ? ' - ' . substr($a['purpose'], 0, 15) : '';
                  if (strlen($a['purpose'] ?? '') > 15) $purpose .= '...';
                  
                  echo "<div class='appt $cls' title='" . htmlspecialchars($a['patient_name']) . " at " . $a['atime'] . 
                       "\nPurpose: " . htmlspecialchars($a['purpose'] ?? 'Not specified') . 
                       "\nDentist: " . htmlspecialchars($a['dentist_name'] ?? 'Not assigned') . 
                       "\nStatus: " . $a['status'] . 
                       "\nPhone: " . htmlspecialchars($a['phone'] ?? 'N/A') . "'>";
                  echo "<div class='appt-time'>{$a['atime']}</div>";
                  echo "<div class='appt-patient'>" . htmlspecialchars(substr($a['patient_name'], 0, 12)) . "</div>";
                  if (strlen($a['patient_name']) > 12) {
                      echo "<div class='appt-patient'>...</div>";
                  }
                  echo "</div>";
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

<style>
.calendar {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    overflow: hidden;
}

.calendar th {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    padding: 15px 8px;
    text-align: center;
    font-weight: bold;
    border: none;
}

.calendar td {
    border: 1px solid #e1e5e9;
    vertical-align: top;
    min-height: 120px;
    height: 120px;
    width: 14.28%;
    padding: 5px;
    position: relative;
    background: #fafbfc;
}

.calendar td.empty {
    background: #f8f9fa;
}

.day {
    font-weight: bold;
    font-size: 16px;
    color: #2c3e50;
    margin-bottom: 5px;
    text-align: left;
}

.appt {
    background: #95a5a6;
    color: white;
    padding: 3px 6px;
    margin: 2px 0;
    border-radius: 4px;
    font-size: 11px;
    line-height: 1.2;
    cursor: pointer;
    transition: all 0.2s ease;
    overflow: hidden;
}

.appt:hover {
    transform: scale(1.05);
    z-index: 10;
    position: relative;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.appt.ok {
    background: linear-gradient(135deg, #27ae60, #229954);
}

.appt.pending {
    background: linear-gradient(135deg, #f39c12, #e67e22);
}

.appt.cancel {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.appt-time {
    font-weight: bold;
    font-size: 12px;
    margin-bottom: 1px;
}

.appt-patient {
    font-size: 10px;
    opacity: 0.9;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Today highlighting */
.calendar td.today {
    background: linear-gradient(135deg, #fff3cd, #ffeeba);
    border: 2px solid #ffc107;
    animation: todayGlow 3s ease-in-out infinite;
    position: relative;
}

.calendar td.today::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, #ffc107, #ffeb3b, #ffc107, #ffeb3b);
    z-index: -1;
    border-radius: 4px;
    opacity: 0.7;
}

@keyframes todayGlow {
    0%, 100% {
        box-shadow: 0 0 5px rgba(255, 193, 7, 0.5);
    }
    50% {
        box-shadow: 0 0 20px rgba(255, 193, 7, 0.8), 0 0 30px rgba(255, 193, 7, 0.4);
    }
}

.calendar td.today .day {
    color: #856404;
    font-weight: 900;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
}

/* Responsive design */
@media (max-width: 768px) {
    .calendar {
        font-size: 12px;
    }
    
    .calendar td {
        min-height: 80px;
        height: 80px;
        padding: 3px;
    }
    
    .appt {
        font-size: 9px;
        padding: 2px 4px;
    }
    
    .appt-time {
        font-size: 10px;
    }
    
    .appt-patient {
        font-size: 8px;
    }
}

/* Print styles */
@media print {
    .calendar {
        box-shadow: none;
    }
    
    .appt {
        background: #333 !important;
        color: white !important;
    }
}
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>