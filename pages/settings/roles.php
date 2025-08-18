<?php
/*****************************************************************
 * pages/settings/roles.php
 * ---------------------------------------------------------------
 * Manage user roles & permissions (Admin-only).
 *
 * Table expected:
 *   CREATE TABLE Role (
 *     role_id   INT AUTO_INCREMENT PRIMARY KEY,
 *     role_name VARCHAR(40) UNIQUE NOT NULL
 *   ) ENGINE=InnoDB;
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2 levels
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Admin-only ───────── */
if (!is_admin()) {
    flash('Roles management is restricted to administrators.');
    redirect('/dentosys/index.php');
}

/* --------------------------------------------------------------
 * 1. Add NEW role
 * ------------------------------------------------------------ */
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = $conn->real_escape_string(trim($_POST['new_role']));
    if ($name === '') {
        flash('Role name cannot be empty.');
    } else {
        $stmt = $conn->prepare("INSERT IGNORE INTO Role (role_name) VALUES (?)");
        $stmt->bind_param('s', $name);
        if ($stmt->execute()) {
            if ($stmt->affected_rows) {
                flash('Role added.');
            } else {
                flash('Role already exists.');
            }
        } else {
            flash('DB error: ' . $conn->error);
        }
    }
    redirect('roles.php');
}

/* --------------------------------------------------------------
 * 2. Rename existing role
 * ------------------------------------------------------------ */
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $rid  = intval($_POST['role_id']);
    $name = $conn->real_escape_string(trim($_POST['role_name']));

    if ($name === '') {
        flash('Role name cannot be empty.');
    } else {
        $stmt = $conn->prepare(
          "UPDATE Role SET role_name = ? WHERE role_id = ? LIMIT 1"
        );
        $stmt->bind_param('si', $name, $rid);
        if ($stmt->execute()) {
            flash('Role updated.');
        } else {
            flash('DB error: ' . $conn->error);
        }
    }
    redirect('roles.php');
}

/* --------------------------------------------------------------
 * 3. Delete role (only if no users linked)
 * ------------------------------------------------------------ */
if (isset($_GET['delete'])) {
    $rid = intval($_GET['delete']);

    // Prevent deleting Admin (role_id = 1) or if users still linked
    $protected = ($rid === 1);
    $hasUsers  = $conn->query(
        "SELECT 1 FROM usertbl WHERE role_id = $rid LIMIT 1"
    )->num_rows > 0;

    if ($protected) {
        flash('Cannot delete the Admin role.');
    } elseif ($hasUsers) {
        flash('Role has users assigned; reassign them first.');
    } else {
        $conn->query("DELETE FROM Role WHERE role_id = $rid LIMIT 1");
        flash('Role deleted.');
    }
    redirect('roles.php');
}

/* --------------------------------------------------------------
 * 4. Fetch roles list
 * ------------------------------------------------------------ */
$roles = $conn->query("SELECT * FROM Role ORDER BY role_id");

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>User Roles &amp; Permissions</h2>
  <?= get_flash(); ?>

  <!-- Add new role -->
  <form method="post" style="margin-bottom:18px;">
    <input type="hidden" name="action" value="add">
    <label>
      <strong>New Role:</strong>
      <input type="text" name="new_role" required>
    </label>
    <button type="submit">Add</button>
  </form>

  <!-- Roles table -->
  <table>
    <thead>
      <tr><th>ID</th><th>Name</th><th style="width:140px;">Actions</th></tr>
    </thead>
    <tbody>
    <?php while ($r = $roles->fetch_assoc()): ?>
      <tr>
        <td><?= $r['role_id']; ?></td>
        <td>
          <?php if ($r['role_id'] === 1): ?>
            <!-- Protect admin name from edit in UI -->
            <?= htmlspecialchars($r['role_name']); ?>
          <?php else: ?>
            <!-- Inline edit form -->
            <form method="post" style="display:inline;">
              <input type="hidden" name="action" value="edit">
              <input type="hidden" name="role_id" value="<?= $r['role_id']; ?>">
              <input type="text"   name="role_name"
                     value="<?= htmlspecialchars($r['role_name']); ?>"
                     style="width:140px;">
              <button type="submit" style="padding:2px 6px;">Save</button>
            </form>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($r['role_id'] !== 1): ?>
            <a class="btn cancel"
               href="?delete=<?= $r['role_id']; ?>"
               onclick="return confirm('Delete this role?');">
               Delete
            </a>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>