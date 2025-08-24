<?php
/*****************************************************************
 * pages/communications/feedback.php
 * ---------------------------------------------------------------
 * Patient feedback inbox – list, filter, mark reviewed.
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // …/pages/communications/ -> up 2
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* Access: Admin (1), Dentist (2), Receptionist (3) */
$allowed = [1, 2, 3];
if (!in_array($_SESSION['role'] ?? 0, $allowed, true)) {
    flash('Access denied.');
    redirect('/dentosys/index.php');
}

/* Mark as reviewed */
if (isset($_GET['review'], $_GET['id'])) {
  // Only admins can change feedback status
  if (!is_admin()) {
    flash('Access denied. View-only access.', 'error');
    redirect('feedback.php');
  }
    $id = intval($_GET['id']);
    $conn->query(
        "UPDATE Feedback SET status='Reviewed' WHERE feedback_id = $id LIMIT 1"
    );
    flash('Feedback marked as reviewed.');
    redirect('feedback.php');
}

/* Filters */
$where = '';
if (!empty($_GET['status']) && in_array($_GET['status'], ['New','Reviewed'])) {
    $st   = $conn->real_escape_string($_GET['status']);
    $where = "WHERE f.status = '$st'";
}

/* Fetch feedback */
$sql = "SELECT f.*, CONCAT(p.first_name,' ',p.last_name) AS patient
        FROM Feedback f
        JOIN Patient p ON p.patient_id = f.patient_id
        $where
        ORDER BY f.created_at DESC";
$feedback = $conn->query($sql);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>

<style>
.feedback-main {
    padding: 0 2rem 3rem;
    background: linear-gradient(135deg, #eff6ff 0%, #e0f2fe 100%);
    min-height: 100vh;
}
.feedback-header {
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    margin: 0 -2rem 2rem;
    padding: 2rem 2rem 2.5rem;
    color: white;
    border-radius: 0 0 24px 24px;
    box-shadow: 0 8px 32px -8px rgba(14, 165, 233, 0.3);
}
.feedback-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 0 0 0.25rem;
    font-size: 2rem;
    font-weight: 700;
}
.feedback-subtitle { margin: 0; opacity: .9; }
.filters-card, .table-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
}
.filters-card { margin-bottom: 1.5rem; }
.filters-form { display: flex; gap: .75rem; align-items: end; flex-wrap: wrap; }
.form-group { display: flex; flex-direction: column; gap: .5rem; }
.form-label { font-weight: 600; color: #374151; font-size: .875rem; }
.form-select, .btn { padding: .65rem .9rem; border-radius: 8px; font-weight: 600; }
.form-select { border: 2px solid #e2e8f0; background: #fff; }
.btn { display: inline-flex; align-items: center; gap: .5rem; border: none; cursor: pointer; }
.btn-primary { background: linear-gradient(135deg, #0ea5e9, #0284c7); color: #fff; }
.btn-secondary { background: #f8fafc; color: #475569; border: 2px solid #e2e8f0; text-decoration: none; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 20px -8px rgba(14,165,233,.5);} 
.btn-secondary:hover { background: #f1f5f9; border-color: #cbd5e1; }
.modern-table { width: 100%; border-collapse: collapse; }
.modern-table th { background: #f8fafc; padding: 1rem; text-align: left; font-weight: 700; color: #374151; border-bottom: 2px solid #e2e8f0; }
.modern-table td { padding: 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
.rating { color: #f59e0b; font-weight: 700; }
.status-badge { display: inline-block; padding: .25rem .6rem; border-radius: 999px; font-size: .75rem; font-weight: 700; }
.status-new { background: #fee2e2; color: #991b1b; }
.status-reviewed { background: #dcfce7; color: #166534; }
.actions { display: flex; gap: .5rem; }
.empty-state { text-align: center; padding: 2rem; color: #64748b; }
@media(max-width:768px){ .feedback-main{padding:0 1rem 2rem;} .feedback-header{margin:0 -1rem 1.5rem; padding:1.5rem;} .modern-table{font-size:.9rem;} }
</style>

<main class="feedback-main">
  <div class="feedback-header">
    <h1 class="feedback-title">💬 Patient Feedback</h1>
    <p class="feedback-subtitle">Review and manage patient feedback efficiently</p>
  </div>

  <?= get_flash(); ?>

  <div class="filters-card">
    <form method="get" class="filters-form">
      <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="">All statuses</option>
          <?php foreach (['New','Reviewed'] as $s): ?>
            <option value="<?= $s; ?>" <?= (!empty($_GET['status']) && $_GET['status']===$s) ? 'selected' : ''; ?>><?= $s; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="actions">
        <button type="submit" class="btn btn-primary">🔎 Apply</button>
        <a class="btn btn-secondary" href="feedback.php">🔄 Reset</a>
      </div>
    </form>
  </div>

  <div class="table-card">
    <?php if ($feedback->num_rows === 0): ?>
      <div class="empty-state">📭 No feedback found.</div>
    <?php else: ?>
      <table class="modern-table">
        <thead>
          <tr>
            <th style="width:70px;">#</th>
            <th style="width:160px;">Date</th>
            <th style="width:220px;">Patient</th>
            <th style="width:120px;">Rating</th>
            <th>Comment</th>
            <th style="width:140px;">Status</th>
            <th style="width:160px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php $i=1; while ($fb = $feedback->fetch_assoc()): ?>
          <tr>
            <td><?= $i++; ?></td>
            <td><?= htmlspecialchars($fb['created_at']); ?></td>
            <td><?= htmlspecialchars($fb['patient']); ?></td>
            <td class="rating"><?= intval($fb['rating']); ?> ⭐</td>
            <td><?= nl2br(htmlspecialchars($fb['comment'])); ?></td>
            <td>
              <?php if ($fb['status'] === 'New'): ?>
                <span class="status-badge status-new">New</span>
              <?php else: ?>
                <span class="status-badge status-reviewed">Reviewed</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($fb['status'] === 'New' && is_admin()): ?>
                <a class="btn btn-primary" href="?review=1&id=<?= $fb['feedback_id']; ?>" onclick="return confirm('Mark as reviewed?');">Mark Reviewed</a>
              <?php else: ?>
                <span style="color:#94a3b8;">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>
