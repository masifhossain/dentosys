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

// Only Admins and Receptionists may approve appointments
if (is_dentist()) {
    flash('You do not have permission to approve appointments. Please contact reception staff.');
    redirect('calendar.php');
}

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
$whereClauses = ["a.status = 'Pending'"];

// Apply role-based filtering for dentists
if (is_dentist()) {
    $current_dentist_id = get_current_dentist_id();
    if ($current_dentist_id) {
        $whereClauses[] = "a.dentist_id = $current_dentist_id";
    } else {
        // If dentist not found, show no appointments
        $whereClauses[] = "1 = 0";
    }
}

$whereClause = implode(' AND ', $whereClauses);

$sql = "SELECT a.appointment_id,
               DATE_FORMAT(a.appointment_dt,'%Y-%m-%d %H:%i') AS appt_time,
               a.notes, p.patient_id,
               CONCAT(p.first_name,' ',p.last_name) AS patient
        FROM Appointment a
        JOIN Patient p ON p.patient_id = a.patient_id
        WHERE $whereClause
        ORDER BY a.appointment_dt";
$pending = $conn->query($sql);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main class="pending-main">
  <div class="page-intro">
    <div class="icon">⚠️</div>
    <div>
      <h1>Pending Appointments</h1>
      <p class="sub">Review and approve or cancel upcoming requests.</p>
    </div>
    <div class="actions"><a href="calendar.php" class="btn-secondary">← Calendar</a></div>
  </div>
  <?= get_flash(); ?>
  <?php if ($pending->num_rows === 0): ?>
    <div class="empty-state">
      <div class="emoji">🎉</div>
      <h2>No Pending Appointments</h2>
      <p>All appointment requests have been processed.</p>
      <a href="calendar.php" class="btn-primary">Go to Calendar</a>
    </div>
  <?php else: ?>
    <form method="post" id="bulkForm" class="bulk-form" onsubmit="return false;">
      <div class="bulk-actions-bar">
        <div class="left">
          <label class="select-all"><input type="checkbox" id="selectAll"> Select All (<?= $pending->num_rows; ?>)</label>
        </div>
        <div class="right">
          <button type="button" class="btn-primary sm" onclick="bulkAction('approve')">Approve Selected</button>
          <button type="button" class="btn-secondary sm" onclick="bulkAction('cancel')">Cancel Selected</button>
        </div>
      </div>
      <div class="pending-grid">
        <?php while ($a = $pending->fetch_assoc()): ?>
          <div class="pending-card" data-id="<?= $a['appointment_id']; ?>">
            <div class="card-head">
              <div class="time"><?= $a['appt_time']; ?></div>
              <label class="chk"><input type="checkbox" name="ids[]" value="<?= $a['appointment_id']; ?>"></label>
            </div>
            <div class="patient">👤 <?= htmlspecialchars($a['patient']); ?></div>
            <?php if($a['notes']): ?><div class="notes">📝 <?= htmlspecialchars($a['notes']); ?></div><?php endif; ?>
            <div class="card-actions">
              <a href="?action=approve&id=<?= $a['appointment_id']; ?>" class="mini-btn ok" onclick="return confirm('Approve this appointment?');">Approve</a>
              <a href="?action=cancel&id=<?= $a['appointment_id']; ?>" class="mini-btn cancel" onclick="return confirm('Cancel this appointment?');">Cancel</a>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </form>
  <?php endif; ?>
</main>

<style>
  .pending-main { padding:0 2rem 3rem; }
  .page-intro { display:flex; gap:1.1rem; align-items:center; margin:0 0 1.75rem; flex-wrap:wrap; }
  .page-intro .icon { font-size:2.5rem; }
  .page-intro h1 { margin:0 0 .25rem; font-size:2.1rem; letter-spacing:.5px; }
  .page-intro .sub { margin:0; color:#64748b; font-size:.95rem; }
  .actions { margin-left:auto; }
  .btn-primary, .btn-secondary { text-decoration:none; font-weight:600; border-radius:14px; padding:.8rem 1.3rem; font-size:.8rem; display:inline-flex; align-items:center; gap:.4rem; }
  .btn-primary { background:linear-gradient(135deg,#4facfe,#00c6ff); color:#fff; box-shadow:0 6px 16px -4px rgba(0,150,255,.4); }
  .btn-secondary { background:#fff; border:2px solid #e2e8f0; color:#1e293b; }
  .btn-secondary:hover { border-color:#4facfe; color:#2563eb; }
  .empty-state { background:#fff; border-radius:22px; padding:3rem 2.5rem; text-align:center; box-shadow:0 10px 30px -8px rgba(0,0,0,.12); max-width:560px; }
  .empty-state .emoji { font-size:3rem; margin-bottom:1rem; }
  .pending-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:1rem; margin-top:1rem; }
  .pending-card { background:#fff; border:1px solid #e2e8f0; border-radius:18px; padding:1rem 1rem 1.15rem; box-shadow:0 4px 12px -4px rgba(0,0,0,.08); display:flex; flex-direction:column; gap:.55rem; position:relative; }
  .pending-card:hover { border-color:#4facfe; }
  .card-head { display:flex; justify-content:space-between; align-items:center; }
  .card-head .time { font-weight:700; font-size:.85rem; color:#1e293b; }
  .patient { font-size:.75rem; font-weight:600; color:#1e293b; }
  .notes { font-size:.65rem; color:#475569; background:#f1f5f9; padding:.45rem .55rem; border-radius:10px; line-height:1.1rem; }
  .card-actions { display:flex; gap:.45rem; }
  .mini-btn { flex:1; text-align:center; text-decoration:none; font-size:.6rem; padding:.45rem .5rem; font-weight:600; border-radius:10px; border:2px solid #e2e8f0; background:#f8fafc; color:#1e293b; }
  .mini-btn.ok { background:#dcfce7; border-color:#bbf7d0; color:#166534; }
  .mini-btn.cancel { background:#fee2e2; border-color:#fecaca; color:#b91c1c; }
  .mini-btn:hover { filter:brightness(1.05); }
  .bulk-actions-bar { display:flex; justify-content:space-between; align-items:center; background:#fff; padding:.9rem 1.1rem; border:1px solid #e2e8f0; border-radius:18px; box-shadow:0 4px 10px -4px rgba(0,0,0,.08); }
  .bulk-actions-bar .left { font-size:.7rem; font-weight:600; color:#475569; }
  .bulk-actions-bar .right { display:flex; gap:.55rem; }
  .btn-primary.sm, .btn-secondary.sm { padding:.55rem .95rem; font-size:.65rem; }
  #selectAll { transform:translateY(1px); margin-right:.4rem; }
  @media (max-width:640px){ .pending-main { padding:0 1.25rem 2.5rem; } .bulk-actions-bar { flex-direction:column; align-items:stretch; gap:.75rem; } .bulk-actions-bar .right { width:100%; } .btn-primary, .btn-secondary { width:100%; justify-content:center; } }
</style>

<script>
document.getElementById('selectAll')?.addEventListener('change', e=>{
  const checked=e.target.checked; document.querySelectorAll('.pending-card input[type=checkbox]').forEach(cb=>cb.checked=checked);
});
function bulkAction(action){
  const ids=[...document.querySelectorAll('.pending-card input[type=checkbox]:checked')].map(c=>c.value);
  if(!ids.length){ alert('Select at least one appointment.'); return; }
  if(!confirm('Perform '+action+' on '+ids.length+' appointment(s)?')) return;
  // sequential navigation (could be improved to batch endpoint)
  const first=ids.shift(); window.location.href='?action='+action+'&id='+first; // server flashes & reload triggers loop for each selection manually if desired
}
</script>

<?php include BASE_PATH . '/templates/footer.php'; ?>