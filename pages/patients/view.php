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

$stmt = $conn->prepare("SELECT * FROM patient WHERE patient_id = ? LIMIT 1");
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
// Additional detail queries
$recentAppointments = $conn->query("SELECT appointment_id, DATE_FORMAT(appointment_dt,'%Y-%m-%d %H:%i') as atime, status FROM appointment WHERE patient_id=$id ORDER BY appointment_dt DESC LIMIT 5");
$unpaidInvoices = $conn->query("SELECT invoice_id, total_amount, status FROM invoice WHERE patient_id=$id AND status='Unpaid' ORDER BY invoice_id DESC LIMIT 5");
$recentRx = $conn->query("SELECT prescription_id, medication_name, prescribed_date FROM Prescriptions WHERE patient_id=$id ORDER BY prescribed_date DESC LIMIT 5");

$age = (!empty($patient['dob']) && $patient['dob'] !== '0000-00-00') ? (int)date_diff(date_create($patient['dob']), date_create('today'))->y : null;
?>
<main class="patient-view-main">
  <div class="page-intro">
    <div class="avatar"><?= strtoupper(substr($patient['first_name'],0,1).substr($patient['last_name'],0,1)); ?></div>
    <div>
      <h1><?= htmlspecialchars($patient['first_name'].' '.$patient['last_name']); ?></h1>
      <p class="sub">Patient ID #<?= $patient['patient_id']; ?><?= $age !== null ? ' • '.$age.' yrs' : ''; ?><?= !empty($patient['dob']) ? ' • DOB '.date('M j, Y', strtotime($patient['dob'])) : ''; ?></p>
    </div>
    <div class="actions">
      <a href="edit.php?id=<?= $id; ?>" class="btn-secondary">✏️ Edit</a>
      <a href="list.php" class="btn-secondary alt">← Back</a>
      <?php if (!is_dentist()): ?>
      <a href="../appointments/book.php?patient_id=<?= $id; ?>" class="btn-primary">📅 Book</a>
      <?php else: ?>
      <a href="../records/add_prescription.php?patient_id=<?= $id; ?>" class="btn-primary">💊 Prescribe</a>
      <?php endif; ?>
    </div>
  </div>
  <?= get_flash(); ?>

  <div class="dashboard-cards">
    <div class="kpi">
      <div class="icon">📅</div>
      <div class="value"><?= $appts; ?></div>
      <div class="label">Upcoming</div>
    </div>
    <div class="kpi">
      <div class="icon">💸</div>
      <div class="value"><?= $unpaid; ?></div>
      <div class="label">Unpaid Invoices</div>
    </div>
    <div class="kpi">
      <div class="icon">🩺</div>
      <div class="value"><?= $records; ?></div>
      <div class="label">Treatments</div>
    </div>
  </div>

  <div class="layout">
    <section class="card info-card">
      <h2>👤 Profile Details</h2>
      <div class="info-grid">
        <div><span class="lbl">Full Name</span><span class="val"><?= htmlspecialchars($patient['first_name'].' '.$patient['last_name']); ?></span></div>
        <div><span class="lbl">Email</span><span class="val"><?= $patient['email'] ? htmlspecialchars($patient['email']) : '—'; ?></span></div>
        <div><span class="lbl">Phone</span><span class="val"><?= $patient['phone'] ? htmlspecialchars($patient['phone']) : '—'; ?></span></div>
        <div><span class="lbl">DOB</span><span class="val"><?= !empty($patient['dob']) ? htmlspecialchars($patient['dob']) : '—'; ?></span></div>
        <div class="full"><span class="lbl">Address</span><span class="val multiline"><?= $patient['address']? nl2br(htmlspecialchars($patient['address'])):'—'; ?></span></div>
      </div>
      <div class="inline-actions">
        <a href="edit.php?id=<?= $id; ?>" class="mini-btn">Edit Details</a>
        <a href="../billing/create_invoice.php?patient_id=<?= $id; ?>" class="mini-btn">New Invoice</a>
        <a href="../records/add_prescription.php?patient_id=<?= $id; ?>" class="mini-btn">New Rx</a>
      </div>
    </section>

    <section class="card activity-card">
      <h2>🗂️ Recent Activity</h2>
      <div class="activity-group">
        <h3>Appointments</h3>
        <?php if ($recentAppointments && $recentAppointments->num_rows): ?>
          <ul class="list">
            <?php while($a=$recentAppointments->fetch_assoc()): ?>
              <li><span><?= htmlspecialchars($a['atime']); ?></span><span class="badge <?= strtolower($a['status']); ?>"><?= htmlspecialchars($a['status']); ?></span></li>
            <?php endwhile; ?>
          </ul>
        <?php else: ?><div class="empty">No appointments yet.</div><?php endif; ?>
      </div>
      <div class="activity-group">
        <h3>Unpaid Invoices</h3>
        <?php if ($unpaidInvoices && $unpaidInvoices->num_rows): ?>
          <ul class="list">
            <?php while($inv=$unpaidInvoices->fetch_assoc()): ?>
              <li><span>#<?= $inv['invoice_id']; ?></span><span>A$<?= number_format($inv['total_amount'],2); ?></span></li>
            <?php endwhile; ?>
          </ul>
        <?php else: ?><div class="empty">None 🎉</div><?php endif; ?>
      </div>
      <div class="activity-group">
        <h3>Prescriptions</h3>
        <?php if ($recentRx && $recentRx->num_rows): ?>
          <ul class="list">
            <?php while($rx=$recentRx->fetch_assoc()): ?>
              <li><span><?= htmlspecialchars($rx['medication_name']); ?></span><span><?= date('M j', strtotime($rx['prescribed_date'])); ?></span></li>
            <?php endwhile; ?>
          </ul>
        <?php else: ?><div class="empty">No prescriptions.</div><?php endif; ?>
      </div>
    </section>

    <aside class="card side">
      <h2>🔗 Quick Links</h2>
      <div class="quick-links">
        <a href="../appointments/calendar.php?patient=<?= $id; ?>">View Appointments →</a>
        <a href="../billing/invoices.php?patient=<?= $id; ?>">View Invoices →</a>
        <a href="../records/list.php?patient=<?= $id; ?>">Clinical Records →</a>
      </div>
      <h2 style="margin-top:1.75rem;">📝 Notes</h2>
      <p class="small muted">Consider adding a notes module to record allergy or preference information.</p>
    </aside>
  </div>
</main>

<style>
  .patient-view-main { padding:0 2rem 3rem; }
  .page-intro { display:flex; align-items:center; gap:1.25rem; margin:0 0 1.75rem; flex-wrap:wrap; }
  .page-intro h1 { margin:0 0 .3rem; font-size:2.1rem; letter-spacing:.5px; }
  .page-intro .sub { margin:0; color:#64748b; font-size:.95rem; }
  .avatar { width:70px; height:70px; border-radius:22px; background:linear-gradient(135deg,#4facfe,#00c6ff); display:flex; align-items:center; justify-content:center; font-size:1.75rem; font-weight:700; color:#fff; box-shadow:0 10px 24px -6px rgba(0,150,255,.45); }
  .actions { margin-left:auto; display:flex; gap:.6rem; flex-wrap:wrap; }
  .btn-primary, .btn-secondary { text-decoration:none; font-weight:600; border-radius:14px; padding:.75rem 1.35rem; font-size:.85rem; display:inline-flex; align-items:center; gap:.4rem; }
  .btn-primary { background:linear-gradient(135deg,#4facfe,#00c6ff); color:#fff; box-shadow:0 6px 16px -4px rgba(0,150,255,.4); }
  .btn-secondary { background:#fff; border:2px solid #e2e8f0; color:#1e293b; }
  .btn-secondary.alt { background:#f1f5f9; }
  .btn-secondary:hover { border-color:#4facfe; color:#2563eb; }
  .btn-primary:hover { transform:translateY(-2px); }

  .dashboard-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:1rem; margin:0 0 2rem; }
  .kpi { background:#fff; border-radius:18px; padding:1.1rem 1.1rem 1.25rem; text-align:center; box-shadow:0 4px 12px -3px rgba(0,0,0,.08); position:relative; }
  .kpi .icon { font-size:1.8rem; margin-bottom:.35rem; }
  .kpi .value { font-size:1.9rem; font-weight:700; line-height:1; color:#1e293b; }
  .kpi .label { font-size:.65rem; letter-spacing:.5px; text-transform:uppercase; font-weight:600; color:#64748b; margin-top:.25rem; }

  .layout { display:grid; grid-template-columns: minmax(0,2fr) minmax(0,2fr) 340px; gap:2rem; align-items:start; }
  @media (max-width:1400px){ .layout { grid-template-columns: minmax(0,1.5fr) minmax(0,1.5fr) 320px; } }
  @media (max-width:1150px){ .layout { grid-template-columns:1fr 1fr; } .layout .side { grid-column:1/-1; } }
  @media (max-width:800px){ .layout { grid-template-columns:1fr; } }

  .card { background:#fff; border-radius:22px; padding:1.75rem 1.8rem 2rem; box-shadow:0 10px 30px -8px rgba(0,0,0,.12); position:relative; overflow:hidden; }
  .card:before { content:""; position:absolute; inset:0; background:radial-gradient(circle at 90% 15%, rgba(0,150,255,.06), transparent 60%); pointer-events:none; }
  .card h2 { margin:0 0 1rem; font-size:1.15rem; letter-spacing:.5px; }
  .info-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem 1.3rem; }
  .info-grid .lbl { display:block; font-size:.6rem; letter-spacing:.55px; text-transform:uppercase; font-weight:700; color:#64748b; margin-bottom:.25rem; }
  .info-grid .val { display:block; font-size:.9rem; font-weight:600; color:#1e293b; }
  .info-grid .val.multiline { white-space:pre-line; }
  .info-grid .full { grid-column:1/-1; }
  .inline-actions { margin-top:1.25rem; display:flex; flex-wrap:wrap; gap:.5rem; }
  .inline-actions .mini-btn { background:#f1f5f9; border:2px solid #e2e8f0; padding:.45rem .85rem; border-radius:10px; font-size:.65rem; font-weight:600; text-decoration:none; color:#1e293b; }
  .inline-actions .mini-btn:hover { border-color:#4facfe; color:#2563eb; }

  .activity-card .activity-group + .activity-group { margin-top:1.25rem; }
  .activity-group h3 { margin:.2rem 0 .6rem; font-size:.75rem; letter-spacing:.5px; text-transform:uppercase; color:#475569; }
  .activity-group .list { list-style:none; padding:0; margin:0; display:grid; gap:.4rem; }
  .activity-group .list li { display:flex; justify-content:space-between; align-items:center; background:#f8fafc; padding:.55rem .75rem; border:1px solid #e2e8f0; border-radius:10px; font-size:.7rem; font-weight:500; }
  .activity-group .empty { font-size:.65rem; color:#64748b; background:#f1f5f9; padding:.55rem .75rem; border-radius:10px; }
  .badge.approved, .badge.scheduled, .badge.pending { font-size:.55rem; padding:.25rem .45rem; border-radius:8px; background:#e0f2fe; color:#0369a1; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
  .badge.completed { background:#dcfce7; color:#166534; }

  .side .quick-links { display:grid; gap:.55rem; }
  .side .quick-links a { text-decoration:none; background:#f8fafc; border:1px solid #e2e8f0; padding:.6rem .85rem; border-radius:10px; font-size:.7rem; font-weight:600; color:#1e293b; display:flex; justify-content:space-between; }
  .side .quick-links a:hover { border-color:#4facfe; color:#2563eb; }
  .small.muted { font-size:.65rem; color:#64748b; line-height:1.1rem; }

  @media (max-width:640px){
    .patient-view-main { padding:0 1.25rem 2.5rem; }
    .page-intro { flex-direction:column; align-items:flex-start; }
    .actions { width:100%; justify-content:flex-start; }
    .inline-actions { flex-direction:column; }
    .inline-actions .mini-btn { width:100%; text-align:center; }
  }
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>