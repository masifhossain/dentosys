<?php
/*****************************************************************
 * pages/communications/templates.php
 * ---------------------------------------------------------------
 * Manage message templates (Email, SMS, etc.)
 *   • Admin (role_id = 1) and Dentist (role_id = 2) may access
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* --- Role gate --- */
$allowed = [1, 2];              // Admin & Dentist
if (!in_array((int)($_SESSION['role'] ?? 0), $allowed, true)) {
    flash('Access denied.');
    redirect('/index.php');
}

/* ---------- Save new template ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $channel = $conn->real_escape_string($_POST['channel']);
    $title   = $conn->real_escape_string($_POST['title']);
    $body    = $conn->real_escape_string($_POST['body']);

    if ($title && $body) {
        $stmt = $conn->prepare(
          "INSERT INTO MessageTemplate (channel,title,body) VALUES (?,?,?)"
        );
        $stmt->bind_param('sss', $channel, $title, $body);
        $stmt->execute();
        flash('Template saved.');
    } else {
        flash('Title and body are required.','error');
    }
    redirect('templates.php');
}

/* ---------- Filters ---------- */
$where = [];
if (!empty($_GET['channel'])) {
    $ch = $conn->real_escape_string($_GET['channel']);
    $where[] = "channel = '$ch'";
}
if (!empty($_GET['q'])) {
    $q = $conn->real_escape_string($_GET['q']);
    $where[] = "(title LIKE '%$q%' OR body LIKE '%$q%')";
}
$whereSQL = $where ? 'WHERE '.implode(' AND ', $where) : '';

$templates = $conn->query(
  "SELECT * FROM MessageTemplate $whereSQL ORDER BY template_id DESC"
);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Communication Templates</h2>
  <?= get_flash(); ?>

  <!-- New template -->
  <details style="margin-bottom:14px;">
    <summary style="cursor:pointer;">+ New Template</summary>
    <form method="post" style="margin-top:10px;max-width:600px;">
      <label>Channel:
        <select name="channel" required>
          <?php foreach (['Email','SMS','Letter','Postcard','Flow'] as $c): ?>
            <option value="<?= $c; ?>"><?= $c; ?></option>
          <?php endforeach; ?>
        </select>
      </label><br><br>

      <label>Title:<br>
        <input type="text" name="title" required style="width:100%;">
      </label><br><br>

      <label>Body:<br>
        <textarea name="body" rows="6" required style="width:100%;"></textarea>
      </label><br><br>

      <button type="submit">Save Template</button>
    </form>
  </details>

  <!-- Filter -->
  <form method="get" style="margin-bottom:10px;">
    <select name="channel">
      <option value="">All channels</option>
      <?php foreach (['Email','SMS','Letter','Postcard','Flow'] as $c): ?>
        <option value="<?= $c; ?>"
          <?= (!empty($_GET['channel']) && $_GET['channel']===$c)?'selected':''; ?>>
          <?= $c; ?>
        </option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="q" placeholder="Search…"
           value="<?= htmlspecialchars($_GET['q'] ?? ''); ?>">
    <button type="submit">Filter</button>
    <a class="btn" href="templates.php">Reset</a>
  </form>

  <!-- List -->
  <table>
    <thead><tr><th>ID</th><th>Channel</th><th>Title</th><th>Snippet</th></tr></thead>
    <tbody>
      <?php if ($templates->num_rows === 0): ?>
        <tr><td colspan="4">No templates found.</td></tr>
      <?php else: while ($t=$templates->fetch_assoc()): ?>
        <tr>
          <td><?= $t['template_id']; ?></td>
          <td><?= $t['channel']; ?></td>
          <td><?= htmlspecialchars($t['title']); ?></td>
          <td><?= htmlspecialchars(mb_strimwidth($t['body'],0,60,'…')); ?></td>
        </tr>
      <?php endwhile; endif; ?>
    </tbody>
  </table>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>