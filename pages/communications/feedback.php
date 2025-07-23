<?php
/*****************************************************************
 * pages/communications/feedback.php
 * ---------------------------------------------------------------
 * Patient feedback inbox – list, filter, mark reviewed.
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // …/pages/communications/ -> up 2
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── OPTIONAL ROLE CHECK ─────────
   Allow Admin (1), Receptionist (3), or Dentist (2) to view.
*/
$allowed = [1, 2, 3];          // role_id values you want to allow
if (!in_array($_SESSION['role'] ?? 0, $allowed, true)) {
    flash('Access denied.');
    redirect('/dentosys/index.php');
}

/* ───────── Handle “mark reviewed” action ───────── */
if (isset($_GET['review'], $_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query(
        "UPDATE Feedback SET status='Reviewed'
         WHERE feedback_id = $id LIMIT 1"
    );
    flash('Feedback marked as reviewed.');
    redirect('feedback.php');
}

/* ───────── Filter (status) ───────── */
$where = '';
if (!empty($_GET['status']) && in_array($_GET['status'], ['New','Reviewed'])) {
    $st   = $conn->real_escape_string($_GET['status']);
    $where = "WHERE f.status = '$st'";
}

/* ───────── Fetch feedback ───────── */
$sql = "SELECT f.*, CONCAT(p.first_name,' ',p.last_name) AS patient
        FROM Feedback f
        JOIN Patient p ON p.patient_id = f.patient_id
        $where
        ORDER BY f.created_at DESC";
$feedback = $conn->query($sql);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Patient Feedback</h2>
  <?= get_flash(); ?>

  <!-- Filter -->
  <form method="get" style="margin-bottom:10px;">
    <select name="status">
      <option value="">All statuses</option>
      <?php foreach (['New','Reviewed'] as $s): ?>
        <option value="<?= $s; ?>"
          <?= (!empty($_GET['status']) && $_GET['status']===$s) ? 'selected' : ''; ?>>
          <?= $s; ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Apply</button>
    <a class="btn" href="feedback.php">Reset</a>
  </form>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Date</th>
        <th>Patient</th>
        <th>Rating</th>
        <th>Comment</th>
        <th>Status</th>
        <th style="width:120px;">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($feedback->num_rows === 0): ?>
      <tr><td colspan="7">No feedback found.</td></tr>
    <?php else: $i=1; while ($fb = $feedback->fetch_assoc()): ?>
      <tr>
        <td><?= $i++; ?></td>
        <td><?= $fb['created_at']; ?></td>
        <td><?= htmlspecialchars($fb['patient']); ?></td>
        <td><?= $fb['rating']; ?> ⭐</td>
        <td><?= nl2br(htmlspecialchars($fb['comment'])); ?></td>
        <td><?= $fb['status']; ?></td>
        <td>
          <?php if ($fb['status'] === 'New'): ?>
            <a class="btn ok"
               href="?review=1&id=<?= $fb['feedback_id']; ?>"
               onclick="return confirm('Mark as reviewed?');">
               Mark Reviewed
            </a>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>