<?php
/*****************************************************************
 * pages/appointments/calendar.php
 * ---------------------------------------------------------------
 * Month view of appointments.
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';  // 2 levels up
require_once BASE_PATH . '/includes/functions.php';

require_login();
require_staff(); // Only staff can access appointment calendar

/* ───────── Determine month/year to show ───────── */
$month = isset($_GET['m']) ? max(1, min(12, intval($_GET['m']))) : date('n');
$year  = isset($_GET['y']) ? intval($_GET['y']) : date('Y');

$firstTs      = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth  = (int)date('t', $firstTs);
$startDow     = (int)date('w', $firstTs); // 0 = Sun … 6 = Sat
$monthLabel   = date('F Y', $firstTs);

/* Optional filters */
$dentist_filter = isset($_GET['dentist']) ? intval($_GET['dentist']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

/* ───────── Fetch appointments for this month ───────── */
$start = date('Y-m-01 00:00:00', $firstTs);
$end   = date('Y-m-t 23:59:59', $firstTs);

$whereExtra = [];

// Apply role-based filtering for dentists
if (is_dentist()) {
    $current_dentist_id = get_current_dentist_id();
    if ($current_dentist_id) {
        $whereExtra[] = "a.dentist_id = $current_dentist_id";
    } else {
        // If dentist not found, show no appointments
        $whereExtra[] = "1 = 0";
    }
}

if ($dentist_filter > 0) { $whereExtra[] = "a.dentist_id = $dentist_filter"; }
if ($status_filter !== '' && in_array($status_filter, ['Approved','Pending','Cancelled','Scheduled','Complete'])) { $whereExtra[] = "a.status='".$conn->real_escape_string($status_filter)."'"; }
$whereSql = $whereExtra ? (' AND '.implode(' AND ',$whereExtra)) : '';

$sql = "SELECT a.appointment_id,
        DATE(a.appointment_dt) AS adate,
        DATE_FORMAT(a.appointment_dt,'%H:%i') AS atime,
        a.status,
        a.notes AS purpose,
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
        p.phone,
        COALESCE(ut.email,'Unassigned') AS dentist_name
    FROM Appointment a
    JOIN Patient p ON p.patient_id = a.patient_id
    LEFT JOIN Dentist d ON d.dentist_id = a.dentist_id
    LEFT JOIN UserTbl ut ON ut.user_id = d.user_id
    WHERE a.appointment_dt BETWEEN '$start' AND '$end' $whereSql
    ORDER BY a.appointment_dt";
$appts = [];
$res   = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $appts[$row['adate']][] = $row;           // bucket by date
}

// Dentist list for filter (Dentist table does not store names, use associated user email as identifier)
if (is_dentist()) {
    // For dentists, only show their own entry
    $current_dentist_id = get_current_dentist_id();
    $dentists = $conn->query("SELECT d.dentist_id, COALESCE(ut.email, CONCAT('Dentist #', d.dentist_id)) AS name
                              FROM Dentist d
                              LEFT JOIN UserTbl ut ON ut.user_id = d.user_id
                              WHERE d.dentist_id = $current_dentist_id
                              ORDER BY name");
} else {
    // For admin/reception, show all dentists
    $dentists = $conn->query("SELECT d.dentist_id, COALESCE(ut.email, CONCAT('Dentist #', d.dentist_id)) AS name
                              FROM Dentist d
                              LEFT JOIN UserTbl ut ON ut.user_id = d.user_id
                              ORDER BY name");
}

/* ───────── Prev / Next month links ───────── */
$prevMonth = $month == 1 ? 12 : $month - 1;
$prevYear  = $month == 1 ? $year - 1 : $year;
$nextMonth = $month == 12 ? 1  : $month + 1;
$nextYear  = $month == 12 ? $year + 1 : $year;
?>
<?php include BASE_PATH . '/templates/header.php'; ?>
<?php include BASE_PATH . '/templates/sidebar.php'; ?>

<main class="calendar-main">
    <div class="page-header">
        <div class="header-content">
            <div class="title-section">
                <div class="icon-wrapper">📅</div>
                <div>
                    <h1>Appointment Calendar</h1>
                    <p class="subtitle">Manage your practice schedule</p>
                </div>
            </div>
            <div class="header-actions">
                <?php if (!is_dentist()): ?>
                <a class="btn-primary" href="book.php">
                    <span class="btn-icon">📅</span>
                    Book Appointment
                </a>
                <a class="btn-secondary" href="pending.php">
                    <span class="btn-icon">⚠️</span>
                    Pending (<span id="pendingCount">0</span>)
                </a>
                <?php else: ?>
                <a class="btn-primary" href="/dentosys/pages/records/add_prescription.php">
                    <span class="btn-icon">💊</span>
                    New Prescription
                </a>
                <a class="btn-secondary" href="/dentosys/pages/records/add_note.php">
                    <span class="btn-icon">📝</span>
                    Add Note
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="cal-navigation">
        <div class="nav-controls">
            <a href="?m=<?= $prevMonth; ?>&y=<?= $prevYear; ?>&dentist=<?= $dentist_filter; ?>&status=<?= urlencode($status_filter); ?>" class="nav-btn prev" aria-label="Previous Month">
                <span>‹</span>
            </a>
            <div class="current-month">
                <h2><?= $monthLabel; ?></h2>
                <span class="month-subtitle">Click any day to view details</span>
            </div>
            <a href="?m=<?= $nextMonth; ?>&y=<?= $nextYear; ?>&dentist=<?= $dentist_filter; ?>&status=<?= urlencode($status_filter); ?>" class="nav-btn next" aria-label="Next Month">
                <span>›</span>
            </a>
        </div>
        <div class="quick-nav">
            <a href="?m=<?= date('n'); ?>&y=<?= date('Y'); ?>" class="quick-btn">Today</a>
            <button type="button" class="quick-btn" onclick="window.print()">Print</button>
        </div>
    </div>
    <div class="filters-panel">
        <form method="get" class="filters-form" id="filterForm">
            <input type="hidden" name="m" value="<?= $month; ?>">
            <input type="hidden" name="y" value="<?= $year; ?>">
            
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">
                        <span class="label-icon">👨‍⚕️</span>
                        Dentist
                    </label>
                    <select name="dentist" class="filter-select" onchange="this.form.submit()">
                        <option value="0">All Dentists</option>
                        <?php if($dentists) while($d=$dentists->fetch_assoc()): ?>
                            <option value="<?= $d['dentist_id']; ?>" <?= $dentist_filter==$d['dentist_id']?'selected':''; ?>>
                                <?= htmlspecialchars($d['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">
                        <span class="label-icon">📊</span>
                        Status
                    </label>
                    <select name="status" class="filter-select" onchange="this.form.submit()">
                        <option value="" <?= $status_filter==''?'selected':''; ?>>All Statuses</option>
                        <?php foreach(['Approved','Pending','Scheduled','Cancelled','Complete'] as $st): ?>
                            <option value="<?= $st; ?>" <?= $status_filter==$st?'selected':''; ?>><?= $st; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="button" class="reset-btn" onclick="location.href='?m=<?= $month; ?>&y=<?= $year; ?>'">
                        Reset Filters
                    </button>
                </div>
            </div>
            
            <div class="legend-section">
                <h4 class="legend-title">Status Legend</h4>
                <div class="legend-grid">
                    <span class="legend-item"><i class="dot approved"></i>Approved</span>
                    <span class="legend-item"><i class="dot pending"></i>Pending</span>
                    <span class="legend-item"><i class="dot scheduled"></i>Scheduled</span>
                    <span class="legend-item"><i class="dot cancelled"></i>Cancelled</span>
                    <span class="legend-item"><i class="dot complete"></i>Complete</span>
                </div>
            </div>
        </form>
    </div>

    <table class="calendar modern-calendar">
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
          echo "<div class='day-header'>";
          echo "<span class='day-number'>$d</span>";
          if (isset($appts[$dateStr]) && count($appts[$dateStr]) > 0) {
              echo "<button type=\"button\" class=\"day-view-btn\" onclick=\"openDayModal('$dateStr')\" title=\"View " . count($appts[$dateStr]) . " appointment(s)\">";
              echo "<span class=\"appt-count\">" . count($appts[$dateStr]) . "</span>";
              echo "</button>";
          } else {
              echo "<button type=\"button\" class=\"day-view-btn empty\" onclick=\"openDayModal('$dateStr')\" title=\"No appointments - click to book\">";
              echo "<span class=\"plus-icon\">+</span>";
              echo "</button>";
          }
          echo "</div>";
          echo "<div class='appointments-list'>";

          if (isset($appts[$dateStr])) {
              foreach ($appts[$dateStr] as $a) {
                  /* colour by status */
                  $cls = 'pending';
                  if ($a['status'] === 'Approved') $cls='ok';
                  elseif ($a['status'] === 'Cancelled') $cls='cancel';
                  elseif ($a['status'] === 'Scheduled') $cls='scheduled';
                  elseif ($a['status'] === 'Complete') $cls='complete';
                  $purpose = !empty($a['purpose']) ? ' - ' . substr($a['purpose'], 0, 15) : '';
                  if (strlen($a['purpose'] ?? '') > 15) $purpose .= '...';
                  
               $dataPurpose = htmlspecialchars($a['purpose'] ?? '', ENT_QUOTES);
               $dataPatient = htmlspecialchars($a['patient_name'], ENT_QUOTES);
               $dataDentist = htmlspecialchars($a['dentist_name'] ?? 'Unassigned', ENT_QUOTES);
               $dataStatus  = htmlspecialchars($a['status'], ENT_QUOTES);
               $dataPhone   = htmlspecialchars($a['phone'] ?? 'N/A', ENT_QUOTES);
                  echo "<div class='appt-card $cls' data-date='$dateStr' data-time='{$a['atime']}' data-patient='$dataPatient' data-dentist='$dataDentist' data-status='$dataStatus' data-purpose='$dataPurpose' title='" . htmlspecialchars($a['patient_name']) . " at " . $a['atime'] . 
                       "\nPurpose: " . htmlspecialchars($a['purpose'] ?? 'Not specified') . 
                       "\nDentist: " . htmlspecialchars($a['dentist_name'] ?? 'Not assigned') . 
                       "\nStatus: " . $a['status'] . 
                       "\nPhone: " . htmlspecialchars($a['phone'] ?? 'N/A') . "'>";
                  echo "<div class='appt-time'>{$a['atime']}</div>";
                  echo "<div class='appt-patient'>" . htmlspecialchars(substr($a['patient_name'], 0, 14)) . "</div>";
                  if (!empty($a['purpose'])) {
                      echo "<div class='appt-purpose'>" . htmlspecialchars(substr($a['purpose'], 0, 20)) . "</div>";
                  }
                  echo "</div>";
              }
          }

          echo '</div>'; // Close appointments-list
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
    <div id="dayModal" class="day-modal" hidden aria-hidden="true">
        <div class="modal-overlay" onclick="closeDayModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalDate">Date</h2>
                <button type="button" class="modal-close" onclick="closeDayModal()" aria-label="Close modal">×</button>
            </div>
            <div class="modal-body">
                <div id="modalList" class="modal-appointments"></div>
            </div>
            <div class="modal-footer">
                <?php if (!is_dentist()): ?>
                <a id="modalBookLink" href="#" class="btn-primary">
                    <span class="btn-icon">📅</span>
                    Book on this day
                </a>
                <?php endif; ?>
                <button type="button" class="btn-secondary" onclick="closeDayModal()">Close</button>
            </div>
        </div>
    </div>
</main>

<style>
    /* Main Layout */
    .calendar-main { 
        padding: 0 2rem 3rem; 
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        min-height: 100vh;
    }

    /* Page Header */
    .page-header {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        margin: 0 -2rem 2rem;
        padding: 2rem 2rem 2.5rem;
        color: white;
        border-radius: 0 0 24px 24px;
        box-shadow: 0 8px 32px -8px rgba(79, 70, 229, 0.3);
    }
    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 2rem;
        flex-wrap: wrap;
    }
    .title-section {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .icon-wrapper {
        width: 60px;
        height: 60px;
        background: rgba(255,255,255,0.2);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        backdrop-filter: blur(10px);
    }
    .page-header h1 {
        margin: 0 0 0.25rem;
        font-size: 2.2rem;
        font-weight: 700;
        letter-spacing: -0.025em;
    }
    .subtitle {
        margin: 0;
        opacity: 0.9;
        font-size: 1rem;
    }
    .header-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    /* Navigation */
    .cal-navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: white;
        padding: 1.5rem 2rem;
        border-radius: 20px;
        box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
        border: 1px solid #e2e8f0;
    }
    .nav-controls {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }
    .nav-btn {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        border: none;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px -4px rgba(79, 70, 229, 0.4);
    }
    .nav-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -4px rgba(79, 70, 229, 0.6);
    }
    .current-month h2 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: -0.025em;
    }
    .month-subtitle {
        font-size: 0.875rem;
        color: #64748b;
        margin-top: 0.25rem;
    }
    .quick-nav {
        display: flex;
        gap: 0.5rem;
    }
    .quick-btn {
        padding: 0.5rem 1rem;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.875rem;
        font-weight: 600;
        color: #475569;
        text-decoration: none;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .quick-btn:hover {
        background: #e2e8f0;
        border-color: #cbd5e1;
    }

    /* Filters Panel */
    .filters-panel {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 1.5rem 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    }
    .filters-grid {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 1.5rem;
        align-items: end;
        margin-bottom: 1.5rem;
    }
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .filter-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
    }
    .label-icon {
        font-size: 1rem;
    }
    .filter-select {
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        background: #f9fafb;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        transition: all 0.2s ease;
    }
    .filter-select:focus {
        border-color: #4f46e5;
        background: white;
        outline: none;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }
    .reset-btn {
        padding: 0.75rem 1.25rem;
        background: #f3f4f6;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .reset-btn:hover {
        background: #e5e7eb;
        border-color: #d1d5db;
    }

    /* Legend */
    .legend-section {
        border-top: 1px solid #e5e7eb;
        padding-top: 1.5rem;
    }
    .legend-title {
        margin: 0 0 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .legend-grid {
        display: flex;
        gap: 1.5rem;
        flex-wrap: wrap;
    }
    .legend-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
    }
    .legend-item .dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .legend-item .approved { background: #10b981; }
    .legend-item .pending { background: #f59e0b; }
    .legend-item .scheduled { background: #3b82f6; }
    .legend-item .cancelled { background: #ef4444; }
    .legend-item .complete { background: #8b5cf6; }

    /* Buttons */
    .btn-primary, .btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 2px solid transparent;
    }
    .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        box-shadow: 0 4px 12px -4px rgba(79, 70, 229, 0.4);
    }
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 20px -4px rgba(79, 70, 229, 0.6);
    }
    .btn-secondary {
        background: white;
        color: #374151;
        border-color: #e5e7eb;
        box-shadow: 0 2px 8px -2px rgba(0,0,0,0.1);
    }
    .btn-secondary:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }
    .btn-icon {
        font-size: 1rem;
    }

    /* Calendar Table */
    table.calendar.modern-calendar {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 40px -12px rgba(0,0,0,0.15);
    }
    .modern-calendar th {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        font-weight: 700;
        padding: 1rem 0.5rem;
        font-size: 0.875rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        text-align: center;
    }
    .modern-calendar td {
        border: 1px solid #f1f5f9;
        vertical-align: top;
        min-height: 140px;
        height: 140px;
        width: 14.28%;
        padding: 0;
        background: #fefefe;
        position: relative;
        transition: all 0.2s ease;
    }
    .modern-calendar td:hover {
        background: #f8fafc;
    }
    .modern-calendar td.today {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 2px solid #f59e0b;
        box-shadow: 0 0 20px rgba(245, 158, 11, 0.3);
    }
    .modern-calendar td.empty {
        background: #f8fafc;
        opacity: 0.6;
    }

    /* Day Header */
    .day-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0.75rem;
        background: rgba(248, 250, 252, 0.8);
        border-bottom: 1px solid #e2e8f0;
    }
    .day-number {
        font-weight: 700;
        font-size: 0.875rem;
        color: #1e293b;
    }
    .day-view-btn {
        width: 24px;
        height: 24px;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .day-view-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px -4px rgba(79, 70, 229, 0.6);
    }
    .day-view-btn.empty {
        background: #e2e8f0;
        color: #64748b;
    }
    .day-view-btn.empty:hover {
        background: #cbd5e1;
    }
    .appt-count, .plus-icon {
        font-size: 0.75rem;
        font-weight: 700;
    }

    /* Appointments List */
    .appointments-list {
        padding: 0.5rem;
        height: calc(100% - 40px);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    /* Appointment Cards */
    .appt-card {
        background: #94a3b8;
        color: white;
        padding: 0.4rem 0.5rem;
        border-radius: 8px;
        font-size: 0.7rem;
        line-height: 1.2;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .appt-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        border-color: rgba(255,255,255,0.3);
    }
    .appt-card.ok { background: linear-gradient(135deg, #10b981, #059669); }
    .appt-card.pending { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .appt-card.cancel { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .appt-card.scheduled { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .appt-card.complete { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    
    .appt-time {
        font-weight: 700;
        font-size: 0.75rem;
        margin-bottom: 0.15rem;
    }
    .appt-patient {
        font-weight: 600;
        font-size: 0.65rem;
        opacity: 0.95;
        margin-bottom: 0.1rem;
    }
    .appt-purpose {
        font-size: 0.6rem;
        opacity: 0.85;
        font-style: italic;
    }

    /* Modal */
    .day-modal {
        position: fixed;
        inset: 0;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }
    .day-modal[hidden] {
        display: none !important;
    }
    .modal-overlay {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(4px);
        cursor: pointer;
    }
    .modal-content {
        position: relative;
        background: white;
        width: 100%;
        max-width: 600px;
        border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        animation: modalSlide 0.3s ease-out;
        max-height: 80vh;
        display: flex;
        flex-direction: column;
    }
    @keyframes modalSlide {
        from { transform: translateY(40px) scale(0.95); opacity: 0; }
        to { transform: translateY(0) scale(1); opacity: 1; }
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .modal-header h2 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
    }
    .modal-close {
        width: 40px;
        height: 40px;
        background: #f1f5f9;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 1.25rem;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .modal-close:hover {
        background: #e2e8f0;
        border-color: #cbd5e1;
        color: #475569;
    }
    .modal-body {
        padding: 1.5rem 2rem;
        flex: 1;
        overflow-y: auto;
    }
    .modal-appointments {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .modal-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 1rem;
        border-radius: 16px;
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 0.75rem;
        align-items: center;
        transition: all 0.2s ease;
    }
    .modal-item:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }
    .modal-item .time {
        font-weight: 700;
        font-size: 0.875rem;
        color: #1e293b;
        background: white;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
    }
    .modal-item .patient {
        font-weight: 600;
        font-size: 0.875rem;
        color: #374151;
    }
    .modal-item .dentist {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }
    .modal-item .purpose {
        font-size: 0.75rem;
        color: #6b7280;
        font-style: italic;
        margin-top: 0.25rem;
    }
    .modal-item .status {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        text-align: center;
    }
    .status.approved { background: #dcfce7; color: #166534; }
    .status.pending { background: #fef3c7; color: #92400e; }
    .status.cancelled { background: #fee2e2; color: #991b1b; }
    .status.scheduled { background: #dbeafe; color: #1e40af; }
    .status.complete { background: #e9d5ff; color: #7c2d12; }
    .modal-footer {
        padding: 1.5rem 2rem;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .calendar-main { padding: 0 1rem 2rem; }
        .page-header { margin: 0 -1rem 1.5rem; padding: 1.5rem 1rem 2rem; }
        .header-content { flex-direction: column; align-items: stretch; text-align: center; }
        .cal-navigation { padding: 1rem; }
        .filters-grid { grid-template-columns: 1fr; }
        .filters-panel { padding: 1rem; }
        .modern-calendar td { min-height: 120px; height: 120px; }
        .appt-card { font-size: 0.65rem; padding: 0.3rem 0.4rem; }
        .modal-content { margin: 1rem; max-width: none; }
        .modal-header, .modal-body, .modal-footer { padding: 1rem; }
    }
</style>

<script>
// Modal helpers
function openDayModal(dateStr){
    const modal = document.getElementById('dayModal');
    const list  = document.getElementById('modalList');
    const header= document.getElementById('modalDate');
    const book  = document.getElementById('modalBookLink');
    header.textContent = new Date(dateStr+'T00:00:00').toLocaleDateString(undefined,{weekday:'long',month:'short',day:'numeric',year:'numeric'});
    if (book) { // Only set href if book link exists (not for dentists)
        book.href = 'book.php?date='+dateStr;
    }
    list.innerHTML='';
    const items=document.querySelectorAll('[data-date="'+dateStr+'"]');
    if(!items.length){
        const empty=document.createElement('div');
        empty.className='modal-item';
        empty.innerHTML='<div class="time">—</div><div><div class="patient">No appointments scheduled for this day</div></div><div class="status">—</div>';
        list.appendChild(empty);
    } else {
        items.forEach(i=>{
            const div=document.createElement('div');
            div.className='modal-item';
            div.innerHTML=`<div class="time">${i.dataset.time}</div>
                           <div>
                             <div class="patient">${i.dataset.patient}</div>
                             <div class="dentist">👨‍⚕️ ${i.dataset.dentist}</div>
                             ${i.dataset.purpose ? `<div class="purpose">📝 ${i.dataset.purpose}</div>` : ''}
                           </div>
                           <div class="status ${i.dataset.status.toLowerCase()}">${i.dataset.status}</div>`;
            list.appendChild(div);
        });
    }
    modal.hidden=false;
    modal.setAttribute('aria-hidden','false');
    document.body.style.overflow='hidden';
}
function closeDayModal(){
    const modal=document.getElementById('dayModal');
    modal.hidden=true;
    modal.setAttribute('aria-hidden','true');
    document.body.style.overflow='';
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Overlay click - check for both modal background and overlay
    document.addEventListener('click', e => {
        if (e.target.classList.contains('modal-overlay') || e.target.id === 'dayModal') {
            closeDayModal();
        }
    });
    
    // Escape key
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeDayModal();
        }
    });
    
    // Make individual appointment blocks open the modal for their day
    document.addEventListener('click', e => {
        const appt = e.target.closest('.appt, .appt-card');
        if (appt) {
            const date = appt.getAttribute('data-date');
            if (date) { 
                e.preventDefault();
                openDayModal(date); 
            }
        }
    });
    
    // Close button click handler
    const closeBtn = document.querySelector('.modal-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeDayModal);
    }
});
</script>
<?php include BASE_PATH . '/templates/footer.php'; ?>